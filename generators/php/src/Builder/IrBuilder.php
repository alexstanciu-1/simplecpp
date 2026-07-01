<?php
declare(strict_types=1);

namespace Scpp\S2S\Builder;

use Scpp\S2S\IR\ClassDecl;
use Scpp\S2S\IR\ConstantDecl;
use Scpp\S2S\IR\FunctionDecl;
use Scpp\S2S\IR\MethodDecl;
use Scpp\S2S\IR\NamespaceBlock;
use Scpp\S2S\IR\ParamDecl;
use Scpp\S2S\IR\PropertyDecl;
use Scpp\S2S\IR\PhpFile;
use Scpp\S2S\IR\Statement;
use Scpp\S2S\IR\UseDecl;
use Scpp\S2S\Metadata\ArgNormalizationCommentParser;
use Scpp\S2S\Loader\ParsedInput;
use Scpp\S2S\Support\AstKind;
use Scpp\S2S\Support\BuildException;

/**
 * Normalizes php-ast JSON into the smaller IR consumed by the generator. This is the main implementation of the lowering rules captured in rules_catalog.md.
 *
 * Relationship to specs:
 * - this type exists to keep the implementation aligned with generators/php/specs/rules.md and rules_catalog.md
 * - the implementation favors explicit normalized data over ad-hoc AST access during emission
 */
final class IrBuilder
{
	/** @var array<string, string> */
	private array $typeCommentsByKey = [];
	/** @var array<int, string> */
	private array $returnAnnotationsByLine = [];
	private ArgNormalizationCommentParser $argNormalizationCommentParser;


	public function __construct(?ArgNormalizationCommentParser $argNormalizationCommentParser = null)
	{
		$this->argNormalizationCommentParser = $argNormalizationCommentParser ?? new ArgNormalizationCommentParser();
	}

	public function build(ParsedInput $input): PhpFile
	{
		$root = $input->ast;
		if (!is_object($root) || ($root->kind ?? null) !== AstKind::STMT_LIST) {
			throw new BuildException('Unsupported AST root shape.');
		}

		$this->typeCommentsByKey = [];
		$this->returnAnnotationsByLine = [];
		foreach ($input->annotations as $annotation) {
			$name = $annotation['name'] ?? null;
			if (in_array($annotation['kind'], ['local', 'property', 'param'], true)) {
				if (!is_string($name) || $name === '') {
					continue;
				}
				$this->typeCommentsByKey[$annotation['line'] . ':' . $name] = $annotation['type'];
				continue;
			}
			if (in_array($annotation['kind'], ['function_return', 'method_return', 'closure_return'], true) && is_int($annotation['line']) && $annotation['line'] > 0) {
				$this->returnAnnotationsByLine[$annotation['line']] = $annotation['type'];
			}
		}

		$top = $this->collectBlock($root->children ?? [], null);

		return new PhpFile(
			path: $input->path,
			namespaces: $top['namespaces'],
			rootUses: $top['uses'],
			constants: $top['constants'],
			classes: $top['classes'],
			functions: $top['functions'],
			prologueIncludes: $top['prologueIncludes'],
			rootStatements: $top['statements'],
			localTypeCommentsByKey: $this->typeCommentsByKey,
			scannerAnnotations: $input->annotations,
			buildErrors: $top['errors'],
		);
	}

	/**
	 * @param array<int, mixed> $nodes
	 * @return array{namespaces:list<NamespaceBlock>,uses:list<UseDecl>,constants:list<ConstantDecl>,classes:list<ClassDecl>,functions:list<FunctionDecl>,prologueIncludes:list<string>,statements:list<Statement>,errors:list<string>}
	 */
	private function collectBlock(array $nodes, ?string $currentNamespace): array
	{
		$rootNamespaces = [];
		$uses = [];
		$constants = [];
		$classes = [];
		$functions = [];
		$prologueIncludes = [];
		$statements = [];
		$errors = [];
		$namespaceBuckets = [];
		$activeNamespace = $currentNamespace;
		$allowPrologueRequireOnce = $currentNamespace === null;
		$prologueClosed = false;

		foreach ($nodes as $node) {
			if (!is_object($node)) {
				continue;
			}

			$kind = $node->kind ?? null;

			if ($allowPrologueRequireOnce && !$prologueClosed && $kind === AstKind::DECLARE) {
				continue;
			}

			if ($kind === AstKind::INCLUDE_OR_EVAL) {
				if ($allowPrologueRequireOnce && !$prologueClosed && $activeNamespace === null) {
					$includePath = $this->buildRequireOnceIncludePath($node, (int) ($node->lineno ?? 0), $errors);
					if ($includePath !== null) {
						$prologueIncludes[] = $includePath;
					}
					continue;
				}

				$errors[] = 'Prism++ supports require_once only as a static compile-time include with a literal path in the file prologue at line ' . (int) ($node->lineno ?? 0) . '.';
				continue;
			}
			if ($allowPrologueRequireOnce && !$prologueClosed) {
				$prologueClosed = true;
			}

			if ($kind === AstKind::NAMESPACE) {
				$children = $node->children ?? [];
				$name = (string) ($children['name'] ?? '');
				$fullName = $this->combineNamespace($currentNamespace, $name);
				$stmtsNode = $children['stmts'] ?? null;

				if (is_object($stmtsNode) && isset($stmtsNode->children) && is_array($stmtsNode->children)) {
					$collected = $this->collectBlock($stmtsNode->children ?? [], $fullName);
					foreach ($collected['errors'] as $error) {
						$errors[] = $error;
					}
					if ($collected['uses'] !== [] || $collected['constants'] !== [] || $collected['classes'] !== [] || $collected['functions'] !== [] || $collected['statements'] !== []) {
						$rootNamespaces[] = new NamespaceBlock(
							name: $fullName,
							uses: $collected['uses'],
							constants: $collected['constants'],
							classes: $collected['classes'],
							functions: $collected['functions'],
							statements: $collected['statements'],
						);
					}
					foreach ($collected['namespaces'] as $nested) {
						$rootNamespaces[] = $nested;
					}
				} else {
					$activeNamespace = $fullName;
				}
				continue;
			}

			if ($activeNamespace !== null) {
				$bucket = $namespaceBuckets[$activeNamespace] ?? [
					'uses' => [],
					'constants' => [],
					'classes' => [],
					'functions' => [],
					'statements' => [],
				];
				if ($kind === AstKind::USE || $kind === AstKind::GROUP_USE) {
					foreach ($this->buildUses($node) as $use) {
						$bucket['uses'][] = $use;
					}
				} elseif ($kind === AstKind::CONST_DECL) {
					foreach ($this->buildConstants($node, $this->hasLibExportTag($node->children['docComment'] ?? null)) as $constant) {
						$bucket['constants'][] = $constant;
					}
				} elseif ($kind === AstKind::CLASS_) {
					$bucket['classes'][] = $this->buildClass($node);
				} elseif ($kind === AstKind::FUNC_DECL) {
					$bucket['functions'][] = $this->buildFunction($node);
				} else {
					$statement = $this->buildStatement($node);
					if ($statement !== null) {
						$bucket['statements'][] = $statement;
					}
				}
				$namespaceBuckets[$activeNamespace] = $bucket;
				continue;
			}

			if ($kind === AstKind::USE || $kind === AstKind::GROUP_USE) {
				foreach ($this->buildUses($node) as $use) {
					$uses[] = $use;
				}
				continue;
			}

			if ($kind === AstKind::CONST_DECL) {
				foreach ($this->buildConstants($node, $this->hasLibExportTag($node->children['docComment'] ?? null)) as $constant) {
					$constants[] = $constant;
				}
				continue;
			}

			if ($kind === AstKind::CLASS_) {
				$classes[] = $this->buildClass($node);
				continue;
			}

			if ($kind === AstKind::FUNC_DECL) {
				$functions[] = $this->buildFunction($node);
				continue;
			}

			$statement = $this->buildStatement($node);
			if ($statement !== null) {
				$statements[] = $statement;
			}
		}

		foreach ($namespaceBuckets as $name => $bucket) {
			$rootNamespaces[] = new NamespaceBlock(
				name: $name,
				uses: $bucket['uses'],
				constants: $bucket['constants'],
				classes: $bucket['classes'],
				functions: $bucket['functions'],
				statements: $bucket['statements'],
			);
		}

		return [
			'namespaces' => $rootNamespaces,
			'uses' => $uses,
			'constants' => $constants,
			'classes' => $classes,
			'functions' => $functions,
			'prologueIncludes' => $prologueIncludes,
			'statements' => $statements,
			'errors' => $errors,
		];
	}

	/** @param list<string> $errors */
	private function buildRequireOnceIncludePath(mixed $node, int $line, array &$errors): ?string
	{
		$flags = (int) ($node->flags ?? 0);
		if ($flags !== AstKind::EXEC_REQUIRE_ONCE) {
			$errors[] = 'Prism++ supports require_once only as a static compile-time include with a literal path at line ' . $line . '.';
			return null;
		}

		$expr = $node->children['expr'] ?? null;
		if (!is_string($expr) || trim($expr) === '') {
			$errors[] = 'Prism++ supports require_once only as a static compile-time include with a literal path at line ' . $line . '.';
			return null;
		}

		return preg_replace('/\.php$/', '.hpp', $expr) ?? $expr;
	}

	/**

	 * Combines nested namespace fragments into one canonical PHP namespace string.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function combineNamespace(?string $prefix, string $name): string
	{
		if ($prefix === null || $prefix === '') {
			return $name;
		}
		if ($name === '') {
			return $prefix;
		}
		return $prefix . '\\' . $name;
	}

	/**

	 * Builds one IR class declaration from the exported php-ast class node.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function buildClass(mixed $node): ClassDecl
	{
		$children = $node->children ?? [];
		$properties = [];
		$constants = [];
		$methods = [];
		$enumCases = [];
		foreach (($children['stmts']->children ?? []) as $member) {
			if (!is_object($member)) {
				continue;
			}
			if (($member->kind ?? null) === AstKind::METHOD) {
				$methods[] = $this->buildMethod($member);
				continue;
			}
			if (($member->kind ?? null) === AstKind::ENUM_CASE) {
				$enumCases[] = new ConstantDecl(
					name: (string) ($member->children['name'] ?? ''),
					value: $member->children['expr'] ?? null,
					line: (int) ($member->lineno ?? 0),
				);
				continue;
			}
			if (($member->kind ?? null) === AstKind::CLASS_CONST_DECL) {
				$constants = array_merge($constants, $this->buildConstants($member->children['const'] ?? null, false, (int) ($member->flags ?? 0)));
				continue;
			}
			if (($member->kind ?? null) === AstKind::PROP_DECL) {
				$isStatic = (((int) ($member->flags ?? 0)) & AstKind::STATIC) !== 0;
				foreach (($member->children['props']->children ?? []) as $prop) {
					if (!is_object($prop) || ($prop->kind ?? null) !== AstKind::PROP_ELEM) {
						continue;
					}
					$default = $prop->children['default'] ?? null;
					$propertyName = (string) ($prop->children['name'] ?? '');
					$propertyLine = (int) ($prop->lineno ?? $member->lineno ?? 0);
					$properties[] = new PropertyDecl(
						name: $propertyName,
						nativeType: $this->readTypeName($member->children['type'] ?? null),
						docType: $this->resolveDocTypeComment($propertyLine, $propertyName),
						default: $default,
						hasDefault: $default !== null,
						isStatic: $isStatic,
						visibility: $this->readMemberVisibility((int) ($member->flags ?? 0)),
						line: $propertyLine,
					);
				}
			}
		}

		$interfaces = [];
		foreach (($children['implements']->children ?? []) as $interfaceNode) {
			$name = $this->readNameString($interfaceNode);
			if ($name !== '') {
				$interfaces[] = $name;
			}
		}

		return new ClassDecl(
			name: (string) ($children['name'] ?? 'Anonymous'),
			properties: $properties,
			constants: $constants,
			methods: $methods,
			line: (int) ($node->lineno ?? 0),
			parentClass: ($name = $this->readNameString($children['extends'] ?? null)) !== '' ? $name : null,
			interfaces: $interfaces,
			isInterface: (((int) ($node->flags ?? 0)) & AstKind::CLASS_INTERFACE) !== 0,
			isAbstract: (((int) ($node->flags ?? 0)) & AstKind::CLASS_ABSTRACT) !== 0,
			isEnum: $enumCases !== [],
			enumBackingType: $this->readEnumBackingType($children['type'] ?? null, $children['docComment'] ?? null),
			enumCases: $enumCases,
			isStruct: $this->hasStructTag($children['docComment'] ?? null),
			isLibExport: $this->hasLibExportTag($children['docComment'] ?? null),
		);
	}

	/**

	 * Builds one IR function declaration from the exported function node.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function buildFunction(mixed $node): FunctionDecl
	{
		$children = $node->children ?? [];

		$params = $this->buildParams($children['params']->children ?? []);
		$argRuleParse = $this->parseArgNormalizationRules($children['docComment'] ?? null, 'function ' . (string) ($children['name'] ?? ''));

		return new FunctionDecl(
			name: (string) ($children['name'] ?? ''),
			params: $params,
			returnType: $this->resolveFunctionLikeReturnType($node),
			returnsByReference: (($node->flags ?? 0) & AstKind::RETURN_REF) !== 0,
			statements: $this->buildStatements($children['stmts']->children ?? []),
			line: (int) ($node->lineno ?? 0),
			argNormalizationRules: $this->validateArgNormalizationRules($argRuleParse['rules'], $params, 'function ' . (string) ($children['name'] ?? '')),
			isLibExport: $this->hasLibExportTag($children['docComment'] ?? null),
			isAsync: $this->hasAsyncTag($children['docComment'] ?? null),
		);
	}

	/**

	 * Builds one IR method declaration from the exported method node.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function buildMethod(mixed $node): MethodDecl
	{
		$children = $node->children ?? [];

		$params = $this->buildParams($children['params']->children ?? []);
		$owner = 'method ' . (string) ($children['name'] ?? '');

		return new MethodDecl(
			name: (string) ($children['name'] ?? ''),
			params: $params,
			returnType: $this->resolveFunctionLikeReturnType($node),
			returnsByReference: (($node->flags ?? 0) & AstKind::RETURN_REF) !== 0,
			isStatic: (($node->flags ?? 0) & AstKind::STATIC) !== 0,
			statements: $this->buildStatements($children['stmts']->children ?? []),
			line: (int) ($node->lineno ?? 0),
			visibility: $this->readMemberVisibility((int) ($node->flags ?? 0)),
			argNormalizationRules: $this->validateArgNormalizationRules($this->parseArgNormalizationRules($children['docComment'] ?? null, $owner)['rules'], $params, $owner),
			isAsync: $this->hasAsyncTag($children['docComment'] ?? null),
		);
	}

	/**
	 * @return array{rules:list<\Scpp\S2S\IR\ArgNormalizationRule>,errors:list<string>}
	 */
	private function parseArgNormalizationRules(mixed $docComment, string $owner): array
	{
		$parsed = $this->argNormalizationCommentParser->parse($docComment, $owner);
		foreach ($parsed['errors'] as $error) {
			$this->errors[] = $error;
		}
		return $parsed;
	}

	private function hasLibExportTag(mixed $docComment): bool
	{
		if (!is_string($docComment) || trim($docComment) === '') {
			return false;
		}
		return preg_match('/@lib-export\b/', $docComment) === 1;
	}

	private function hasStructTag(mixed $docComment): bool
	{
		if (!is_string($docComment) || trim($docComment) === '') {
			return false;
		}
		return preg_match('/@scpp-struct\b/', $docComment) === 1;
	}

	private function readEnumBackingType(mixed $typeNode, mixed $docComment): ?string
	{
		if (is_string($docComment) && preg_match('/@scpp-enum-backing\s+([A-Za-z_][A-Za-z0-9_]*)\b/', $docComment, $matches) === 1) {
			return $matches[1];
		}
		return $this->readTypeName($typeNode);
	}

	private function hasAsyncTag(mixed $docComment): bool
	{
		if (!is_string($docComment) || trim($docComment) === '') {
			return false;
		}
		return preg_match('/@async\b/', $docComment) === 1;
	}

	/**
	 * @param list<\Scpp\S2S\IR\ArgNormalizationRule> $rules
	 * @param list<ParamDecl> $params
	 * @return list<\Scpp\S2S\IR\ArgNormalizationRule>
	 */
	private function validateArgNormalizationRules(array $rules, array $params, string $owner): array
	{
		if ($rules === []) {
			return [];
		}

		$paramMap = [];
		foreach ($params as $param) {
			$paramMap[$param->name] = $param;
		}

		$seen = [];
		$out = [];
		foreach ($rules as $rule) {
			$param = $paramMap[$rule->paramName] ?? null;
			if (!$param instanceof ParamDecl) {
				$this->errors[] = 'Unknown @arg normalization parameter $' . $rule->paramName . ' in ' . $owner . '.';
				continue;
			}
			if (!in_array($rule->sourceType, $param->unionTypes, true)) {
				$this->errors[] = 'Unsupported @arg normalization source type ' . $rule->sourceType . ' for ' . $owner . '::$' . $param->name . '. The type must be present in the declared union.';
				continue;
			}
			$key = $param->name . '|' . $rule->sourceType;
			if (isset($seen[$key])) {
				$this->errors[] = 'Duplicate @arg normalization source type ' . $rule->sourceType . ' for ' . $owner . '::$' . $param->name . '.';
				continue;
			}
			$seen[$key] = true;
			$out[] = $rule;
		}

		return $out;
	}

	/** @param array<int, mixed> $nodes @return list<ParamDecl> */
	private function buildParams(array $nodes): array
	{
		$params = [];
		foreach ($nodes as $node) {
			if (!is_object($node) || ($node->kind ?? null) !== AstKind::PARAM) {
				continue;
			}
			$children = $node->children ?? [];
			$paramName = (string) ($children['name'] ?? '');
			$paramLine = (int) ($node->lineno ?? 0);
			$params[] = new ParamDecl(
				name: $paramName,
				nativeType: $this->readTypeName($children['type'] ?? null),
				docType: $this->resolveDocTypeComment($paramLine, $paramName),
				isReference: (($node->flags ?? 0) & AstKind::PARAM_REF) !== 0,
				isVariadic: (($node->flags ?? 0) & AstKind::STATIC) !== 0,
				default: $children['default'] ?? null,
				line: $paramLine,
			);
		}
		return $params;
	}

	private function lookupTypeComment(int $line, string $name): ?string
	{
		$key = $line . ':' . $name;
		return $this->typeCommentsByKey[$key] ?? null;
	}

	private function resolveFunctionLikeReturnType(object $node): ?string
	{
		$native = $this->readTypeName($node->children['returnType'] ?? null);
		if ($native !== null) {
			return $native;
		}

		$line = (int) ($node->lineno ?? 0);
		return $line > 0 ? ($this->returnAnnotationsByLine[$line] ?? null) : null;
	}

	private function resolveDocTypeComment(int $line, string $name): ?string
	{
		return $this->lookupTypeComment($line, $name);
	}


	/** @return list<ConstantDecl> */
	private function buildConstants(mixed $node, bool $isLibExport = false, int $visibilityFlags = 0): array
	{
		$out = [];
		foreach (($node->children ?? []) as $child) {
			if (!is_object($child) || ($child->kind ?? null) !== AstKind::CONST_ELEM) {
				continue;
			}
			$out[] = new ConstantDecl(
				name: (string) ($child->children['name'] ?? ''),
				value: $child->children['value'] ?? null,
				isLibExport: $isLibExport || $this->hasLibExportTag($child->children['docComment'] ?? null),
				visibility: $this->readMemberVisibility($visibilityFlags !== 0 ? $visibilityFlags : (int) ($node->flags ?? 0)),
				line: (int) ($child->lineno ?? $node->lineno ?? 0),
			);
		}
		return $out;
	}

	private function readMemberVisibility(int $flags): string
	{
		if (($flags & 4) !== 0) {
			return 'private';
		}
		if (($flags & 2) !== 0) {
			return 'protected';
		}
		return 'public';
	}

	/** @param array<int, mixed> $nodes @return list<Statement> */
	private function buildStatements(array $nodes): array
	{
		$out = [];
		foreach ($nodes as $node) {
			$stmt = $this->buildStatement($node);
			if ($stmt !== null) {
				$out[] = $stmt;
			}
		}
		return $out;
	}

	/** @return list<UseDecl> */
	private function buildUses(mixed $node): array
	{
		$kind = $node->kind ?? null;
		if ($kind === AstKind::GROUP_USE) {
			return $this->buildGroupUse($node);
		}
		if ($kind === AstKind::USE) {
			return $this->buildFlatUse($node);
		}
		return [];
	}

	/** @return list<UseDecl> */
	private function buildFlatUse(mixed $node): array
	{
		$uses = [];
		$flags = (int) ($node->flags ?? 0);
		$kind = $this->mapUseKind($flags);
		foreach ($this->extractUseElements($node->children ?? []) as $element) {
			$children = $element->children ?? [];
			$uses[] = new UseDecl(
				kind: $kind,
				name: $this->readNameString($children['name'] ?? null),
				alias: $this->readAliasString($children['alias'] ?? null),
				line: (int) ($element->lineno ?? $node->lineno ?? 0),
			);
		}
		return $uses;
	}

	/** @return list<UseDecl> */
	private function buildGroupUse(mixed $node): array
	{
		$children = $node->children ?? [];
		$prefix = $this->readNameString($children['prefix'] ?? null);
		$kind = $this->mapUseKind((int) ($node->flags ?? 0));
		$uses = [];
		foreach ($this->extractUseElements(($children['uses']->children ?? $children['uses'] ?? [])) as $element) {
			$elemChildren = $element->children ?? [];
			$name = $this->readNameString($elemChildren['name'] ?? null);
			$fullName = $prefix !== '' && $name !== '' ? $prefix . '\\' . $name : ($prefix !== '' ? $prefix : $name);
			$uses[] = new UseDecl(
				kind: $kind,
				name: $fullName,
				alias: $this->readAliasString($elemChildren['alias'] ?? null),
				line: (int) ($element->lineno ?? $node->lineno ?? 0),
				isGrouped: true,
			);
		}
		return $uses;
	}

	/** @param array<int|string,mixed> $children @return list<array<string,mixed>> */
	private function extractUseElements(array $children): array
	{
		$out = [];
		foreach ($children as $child) {
			if (is_object($child) && ($child->kind ?? null) === AstKind::USE_ELEM) {
				$out[] = $child;
			}
		}
		return $out;
	}

	/**

	 * Maps php-ast use flags into the generator-facing textual kind.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function mapUseKind(int $flags): string
	{
		return match ($flags) {
			AstKind::USE_FUNCTION => 'function',
			AstKind::USE_CONST => 'const',
			default => 'normal',
		};
	}

	/**

	 * Reads a PHP name node into its textual form without applying resolution rules yet.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function readNameString(mixed $node): string
	{
		if (is_string($node)) {
			return ltrim($node, '\\');
		}
		if (!is_object($node)) {
			return '';
		}
		if (($node->kind ?? null) === AstKind::NAME) {
			return ltrim((string) ($node->children['name'] ?? ''), '\\');
		}
		if (($node->kind ?? null) === AstKind::NULLABLE_TYPE) {
			return $this->readNameString($node->children['type'] ?? null);
		}
		return ltrim((string) ($node->children['name'] ?? ''), '\\');
	}

	/**

	 * Reads an optional alias name from exported use-node data.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function readAliasString(mixed $alias): ?string
	{
		if ($alias === null) {
			return null;
		}
		$trimmed = trim((string) $alias);
		return $trimmed !== '' ? $trimmed : null;
	}

	/**

	 * Builds one lowered statement node for the executable subset currently supported by the generator.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function buildStatement(mixed $node): ?Statement
	{
		$kind = $node->kind ?? null;
		$line = (int) ($node->lineno ?? 0);

		if ($kind === AstKind::ASSIGN) {
			return new Statement('assign', $node->children ?? [], $line);
		}

		if ($kind === AstKind::ASSIGN_REF) {
			return new Statement('assign_ref', $node->children ?? [], $line);
		}

		if ($kind === AstKind::ASSIGN_OP) {
			$payload = $node->children ?? [];
			$payload['flags'] = (int) ($node->flags ?? 0);
			return new Statement('assign_op', $payload, $line);
		}

		if ($kind === AstKind::STATIC_VAR) {
			return new Statement('static_var', $node->children ?? [], $line);
		}

		if ($kind === AstKind::RETURN) {
			return new Statement('return', $node->children['expr'] ?? null, $line);
		}

		if ($kind === AstKind::THROW) {
			return new Statement('throw', $node->children['expr'] ?? null, $line);
		}

		if ($kind === AstKind::TRY) {
			$catches = [];
			foreach (($node->children['catches']->children ?? []) as $catchNode) {
				if (!is_object($catchNode) || ($catchNode->kind ?? null) !== AstKind::CATCH) {
					continue;
				}
				$classNode = $catchNode->children['class'] ?? null;
				$classKinds = is_object($classNode) && isset($classNode->children) && is_array($classNode->children)
					? array_values($classNode->children)
					: [$classNode];
				$catches[] = [
					'classes' => $classKinds,
					'var' => $catchNode->children['var'] ?? null,
					'stmts' => $this->buildStatements($catchNode->children['stmts']->children ?? []),
					'line' => (int) ($catchNode->lineno ?? $line),
				];
			}
			return new Statement('try', [
				'try' => $this->buildStatements($node->children['try']->children ?? []),
				'catches' => $catches,
				'finally' => $this->buildStatements($node->children['finally']->children ?? []),
			], $line);
		}

		if ($kind === AstKind::AST_ECHO) {
			// The current php-ast exporter already splits `echo a, b` into sibling AST_ECHO nodes.
			// Preserve the exporter shape and store the single operand only.
			return new Statement('echo', $node->children['expr'] ?? null, $line);
		}

		if ($kind === AstKind::AST_UNSET) {
			// The current php-ast exporter already splits `unset($a, $b)` into sibling AST_UNSET nodes.
			// Preserve the exporter shape and store the single target only.
			return new Statement('unset', $node->children['var'] ?? null, $line);
		}

		if ($kind === AstKind::IF) {
			$branches = [];
			foreach (($node->children ?? []) as $branchNode) {
				if (!is_object($branchNode) || ($branchNode->kind ?? null) !== AstKind::IF_ELEM) {
					continue;
				}
				$branches[] = [
					'cond' => $branchNode->children['cond'] ?? null,
					'stmts' => $this->buildStatements($branchNode->children['stmts']->children ?? []),
					'line' => (int) ($branchNode->lineno ?? $line),
				];
			}
			return new Statement('if', $branches, $line);
		}

		if ($kind === AstKind::WHILE) {
			return new Statement('while', [
				'cond' => $node->children['cond'] ?? null,
				'stmts' => $this->buildStatements($node->children['stmts']->children ?? []),
			], $line);
		}

		if ($kind === AstKind::DO_WHILE) {
			return new Statement('do_while', [
				'cond' => $node->children['cond'] ?? null,
				'stmts' => $this->buildStatements($node->children['stmts']->children ?? []),
			], $line);
		}

		if ($kind === AstKind::FOR) {
			return new Statement('for', [
				'init' => array_values($node->children['init']->children ?? []),
				'cond' => array_values($node->children['cond']->children ?? []),
				'loop' => array_values($node->children['loop']->children ?? []),
				'stmts' => $this->buildStatements($node->children['stmts']->children ?? []),
			], $line);
		}

		if ($kind === AstKind::FOREACH) {
			$valueNode = $node->children['value'] ?? null;
			$byRef = false;

			if (is_object($valueNode) && (($valueNode->kind ?? null) === AstKind::REF)) {
				$byRef = true;
				$valueNode = $valueNode->children['var'] ?? null;
			}

			return new Statement('foreach', [
				'expr' => $node->children['expr'] ?? null,
				'value' => $valueNode,
				'key' => $node->children['key'] ?? null,
				'stmts' => $this->buildStatements($node->children['stmts']->children ?? []),
				'by_ref' => $byRef,
			], $line);
		}

		if ($kind === AstKind::SWITCH) {
			$cases = [];
			foreach (($node->children['stmts']->children ?? []) as $caseNode) {
				if (!is_object($caseNode) || ($caseNode->kind ?? null) !== AstKind::SWITCH_CASE) {
					continue;
				}
				// Preserve each exported switch case as explicit IR so the generator can comment and emit each case block deterministically.
				$cases[] = [
					'cond' => $caseNode->children['cond'] ?? null,
					'stmts' => $this->buildStatements($caseNode->children['stmts']->children ?? []),
				];
			}
			return new Statement('switch', [
				'cond' => $node->children['cond'] ?? null,
				'cases' => $cases,
			], $line);
		}

		if ($kind === AstKind::BREAK) {
			return new Statement('break', $node->children['depth'] ?? null, $line);
		}

		if ($kind === AstKind::CONTINUE) {
			return new Statement('continue', $node->children['depth'] ?? null, $line);
		}

		if ($kind === AstKind::INCLUDE_OR_EVAL) {
			return new Statement('include_or_eval', [
				'expr' => $node->children['expr'] ?? null,
				'flags' => (int) ($node->flags ?? 0),
			], $line);
		}

		if ($kind === AstKind::VAR) {
			$name = (string) (($node->children ?? [])['name'] ?? '');
			$typed = $name !== '' ? $this->lookupTypeComment($line, $name) : null;
			if ($typed !== null) {
				return new Statement('declare_local', [
					'name' => $name,
					'type' => $typed,
				], $line);
			}
		}

		if ($kind === AstKind::CALL || $kind === AstKind::STATIC_CALL || $kind === AstKind::METHOD_CALL || $kind === AstKind::PRE_INC || $kind === AstKind::PRE_DEC || $kind === AstKind::POST_INC || $kind === AstKind::POST_DEC) {
			return new Statement('expr', $node, $line);
		}

		return null;
	}

	/** @return list<mixed> */
	private function extractVariadicPayload(mixed $node): array
	{
		$children = $node->children ?? [];

		if (array_key_exists('expr', $children)) {
			$expr = $children['expr'];
			if (is_object($expr) && isset($expr->children) && is_array($expr->children)) {
				return array_values($expr->children);
			}
			return [$expr];
		}

		if (array_key_exists('var', $children)) {
			$var = $children['var'];
			if (is_object($var) && isset($var->children) && is_array($var->children)) {
				return array_values($var->children);
			}
			return [$var];
		}

		if (is_array($children)) {
			return array_values($children);
		}

		return [];
	}

	/**

	 * Reads a declared PHP type node into the canonical textual type used by the mapper.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function readTypeName(mixed $typeNode): ?string
	{
		if (!is_object($typeNode)) {
			return null;
		}

		$flags = (int) ($typeNode->flags ?? 0);
		$kind = (int) ($typeNode->kind ?? 0);

		if ($kind === AstKind::NULLABLE_TYPE) {
			$inner = $this->readTypeName($typeNode->children['type'] ?? null);
			return $inner !== null ? '?' . ltrim($inner, '?') : null;
		}

		if ($kind === AstKind::NAME) {
			$name = (string) ($typeNode->children['name'] ?? '');
			return $name !== '' ? $name : null;
		}

		if (isset($typeNode->children) && is_array($typeNode->children) && $typeNode->children !== []) {
			$parts = [];
			foreach ($typeNode->children as $child) {
				$part = $this->readTypeName($child);
				if ($part !== null && $part !== '') {
					$parts[] = $part;
				}
			}
			if ($parts !== []) {
				return implode('|', $parts);
			}
		}

		return match ($flags) {
			AstKind::TYPE_VOID => 'void',
			AstKind::TYPE_BOOL => 'bool',
			AstKind::TYPE_LONG => 'int',
			AstKind::TYPE_DOUBLE => 'float',
			AstKind::TYPE_STRING => 'string',
			AstKind::TYPE_ARRAY => 'array',
			AstKind::TYPE_MIXED => 'mixed',
			default => null,
		};
	}
}
