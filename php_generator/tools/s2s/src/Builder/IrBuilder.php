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
use Scpp\S2S\Loader\ParsedInput;
use Scpp\S2S\Support\AstKind;

final class IrBuilder
{
	/**
	 * @param array<int, array{name:string,type:string,line:int}> $typeComments
	 */
	public function build(ParsedInput $input, array $typeComments): PhpFile
	{
		$root = $input->ast;
		if (!is_array($root) || ($root['kind'] ?? null) !== AstKind::STMT_LIST) {
			throw new \RuntimeException('Unsupported AST root shape.');
		}

		$typeMap = [];
		foreach ($typeComments as $comment) {
			$typeMap[$comment['line'] . ':' . $comment['name']] = $comment['type'];
		}

		$top = $this->collectBlock($root['children'] ?? [], null);

		return new PhpFile(
			path: $input->path,
			namespaces: $top['namespaces'],
			rootUses: $top['uses'],
			constants: $top['constants'],
			classes: $top['classes'],
			functions: $top['functions'],
			rootStatements: $top['statements'],
			localTypeCommentsByKey: $typeMap,
		);
	}

	/**
	 * @param array<int, mixed> $nodes
	 * @return array{namespaces:list<NamespaceBlock>,uses:list<UseDecl>,constants:list<ConstantDecl>,classes:list<ClassDecl>,functions:list<FunctionDecl>,statements:list<Statement>}
	 */
	private function collectBlock(array $nodes, ?string $currentNamespace): array
	{
		$rootNamespaces = [];
		$uses = [];
		$constants = [];
		$classes = [];
		$functions = [];
		$statements = [];
		$namespaceBuckets = [];
		$activeNamespace = $currentNamespace;

		foreach ($nodes as $node) {
			if (!is_array($node)) {
				continue;
			}

			$kind = $node['kind'] ?? null;
			if ($kind === AstKind::NAMESPACE) {
				$children = $node['children'] ?? [];
				$name = (string) ($children['name'] ?? '');
				$fullName = $this->combineNamespace($currentNamespace, $name);
				$stmtsNode = $children['stmts'] ?? null;

				if (is_array($stmtsNode) && isset($stmtsNode['children'])) {
					$collected = $this->collectBlock($stmtsNode['children'] ?? [], $fullName);
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
					foreach ($this->buildConstants($node) as $constant) {
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
				foreach ($this->buildConstants($node) as $constant) {
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
			'statements' => $statements,
		];
	}

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

	private function buildClass(array $node): ClassDecl
	{
		$children = $node['children'] ?? [];
		$properties = [];
		$methods = [];
		foreach (($children['stmts']['children'] ?? []) as $member) {
			if (!is_array($member)) {
				continue;
			}
			if (($member['kind'] ?? null) === AstKind::METHOD) {
				$methods[] = $this->buildMethod($member);
				continue;
			}
			if (($member['kind'] ?? null) === AstKind::PROP_DECL) {
				foreach (($member['children']['props']['children'] ?? []) as $prop) {
					if (!is_array($prop) || ($prop['kind'] ?? null) !== AstKind::PROP_ELEM) {
						continue;
					}
					$properties[] = new PropertyDecl(
						name: (string) ($prop['children']['name'] ?? ''),
						type: $this->readTypeName($member['children']['type'] ?? null),
					);
				}
			}
		}

		return new ClassDecl(
			name: (string) ($children['name'] ?? 'Anonymous'),
			properties: $properties,
			methods: $methods,
		);
	}

	private function buildFunction(array $node): FunctionDecl
	{
		$children = $node['children'] ?? [];

		return new FunctionDecl(
			name: (string) ($children['name'] ?? ''),
			params: $this->buildParams($children['params']['children'] ?? []),
			returnType: $this->readTypeName($children['returnType'] ?? null),
			returnsByReference: (($node['flags'] ?? 0) & AstKind::RETURN_REF) !== 0,
			statements: $this->buildStatements($children['stmts']['children'] ?? []),
		);
	}

	private function buildMethod(array $node): MethodDecl
	{
		$children = $node['children'] ?? [];

		return new MethodDecl(
			name: (string) ($children['name'] ?? ''),
			params: $this->buildParams($children['params']['children'] ?? []),
			returnType: $this->readTypeName($children['returnType'] ?? null),
			returnsByReference: (($node['flags'] ?? 0) & AstKind::RETURN_REF) !== 0,
			isStatic: (($node['flags'] ?? 0) & AstKind::STATIC) !== 0,
			statements: $this->buildStatements($children['stmts']['children'] ?? []),
		);
	}

	/** @param array<int, mixed> $nodes @return list<ParamDecl> */
	private function buildParams(array $nodes): array
	{
		$params = [];
		foreach ($nodes as $node) {
			if (!is_array($node) || ($node['kind'] ?? null) !== AstKind::PARAM) {
				continue;
			}
			$children = $node['children'] ?? [];
			$params[] = new ParamDecl(
				name: (string) ($children['name'] ?? ''),
				type: $this->readTypeName($children['type'] ?? null),
				isReference: (($node['flags'] ?? 0) & AstKind::PARAM_REF) !== 0,
				default: $children['default'] ?? null,
			);
		}
		return $params;
	}


	/** @return list<ConstantDecl> */
	private function buildConstants(array $node): array
	{
		$out = [];
		foreach (($node['children'] ?? []) as $child) {
			if (!is_array($child) || ($child['kind'] ?? null) !== AstKind::CONST_ELEM) {
				continue;
			}
			$out[] = new ConstantDecl(
				name: (string) ($child['children']['name'] ?? ''),
				value: $child['children']['value'] ?? null,
			);
		}
		return $out;
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
	private function buildUses(array $node): array
	{
		$kind = $node['kind'] ?? null;
		if ($kind === AstKind::GROUP_USE) {
			return $this->buildGroupUse($node);
		}
		if ($kind === AstKind::USE) {
			return $this->buildFlatUse($node);
		}
		return [];
	}

	/** @return list<UseDecl> */
	private function buildFlatUse(array $node): array
	{
		$uses = [];
		$flags = (int) ($node['flags'] ?? 0);
		$kind = $this->mapUseKind($flags);
		foreach ($this->extractUseElements($node['children'] ?? []) as $element) {
			$children = $element['children'] ?? [];
			$uses[] = new UseDecl(
				kind: $kind,
				name: $this->readNameString($children['name'] ?? null),
				alias: $this->readAliasString($children['alias'] ?? null),
				line: (int) ($element['lineno'] ?? $node['lineno'] ?? 0),
			);
		}
		return $uses;
	}

	/** @return list<UseDecl> */
	private function buildGroupUse(array $node): array
	{
		$children = $node['children'] ?? [];
		$prefix = $this->readNameString($children['prefix'] ?? null);
		$kind = $this->mapUseKind((int) ($node['flags'] ?? 0));
		$uses = [];
		foreach ($this->extractUseElements(($children['uses']['children'] ?? $children['uses'] ?? [])) as $element) {
			$elemChildren = $element['children'] ?? [];
			$name = $this->readNameString($elemChildren['name'] ?? null);
			$fullName = $prefix !== '' && $name !== '' ? $prefix . '\\' . $name : ($prefix !== '' ? $prefix : $name);
			$uses[] = new UseDecl(
				kind: $kind,
				name: $fullName,
				alias: $this->readAliasString($elemChildren['alias'] ?? null),
				line: (int) ($element['lineno'] ?? $node['lineno'] ?? 0),
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
			if (is_array($child) && ($child['kind'] ?? null) === AstKind::USE_ELEM) {
				$out[] = $child;
			}
		}
		return $out;
	}

	private function mapUseKind(int $flags): string
	{
		return match ($flags) {
			AstKind::USE_FUNCTION => 'function',
			AstKind::USE_CONST => 'const',
			default => 'normal',
		};
	}

	private function readNameString(mixed $node): string
	{
		if (is_string($node)) {
			return ltrim($node, '\\');
		}
		if (!is_array($node)) {
			return '';
		}
		if (($node['kind'] ?? null) === AstKind::NAME) {
			return ltrim((string) ($node['children']['name'] ?? ''), '\\');
		}
		if (($node['kind'] ?? null) === AstKind::NULLABLE_TYPE) {
			return $this->readNameString($node['children']['type'] ?? null);
		}
		return ltrim((string) ($node['children']['name'] ?? ''), '\\');
	}

	private function readAliasString(mixed $alias): ?string
	{
		if ($alias === null) {
			return null;
		}
		$trimmed = trim((string) $alias);
		return $trimmed !== '' ? $trimmed : null;
	}

	private function buildStatement(array $node): ?Statement
	{
		$kind = $node['kind'] ?? null;
		$line = (int) ($node['lineno'] ?? 0);

		if ($kind === AstKind::ASSIGN) {
			return new Statement('assign', $node['children'] ?? [], $line);
		}

		if ($kind === AstKind::ASSIGN_REF) {
			return new Statement('assign_ref', $node['children'] ?? [], $line);
		}

		if ($kind === AstKind::STATIC_VAR) {
			return new Statement('static_var', $node['children'] ?? [], $line);
		}

		if ($kind === AstKind::RETURN) {
			return new Statement('return', $node['children']['expr'] ?? null, $line);
		}

		if ($kind === AstKind::AST_ECHO) {
			// The current php-ast exporter already splits `echo a, b` into sibling AST_ECHO nodes.
			// Preserve the exporter shape and store the single operand only.
			return new Statement('echo', $node['children']['expr'] ?? null, $line);
		}

		if ($kind === AstKind::AST_UNSET) {
			// The current php-ast exporter already splits `unset($a, $b)` into sibling AST_UNSET nodes.
			// Preserve the exporter shape and store the single target only.
			return new Statement('unset', $node['children']['var'] ?? null, $line);
		}

		if ($kind === AstKind::CALL || $kind === AstKind::STATIC_CALL || $kind === AstKind::METHOD_CALL) {
			return new Statement('expr', $node, $line);
		}

		return null;
	}

	/** @return list<mixed> */
	private function extractVariadicPayload(array $node): array
	{
		$children = $node['children'] ?? [];

		if (array_key_exists('expr', $children)) {
			$expr = $children['expr'];
			if (is_array($expr) && array_key_exists('children', $expr) && is_array($expr['children'])) {
				return array_values($expr['children']);
			}
			return [$expr];
		}

		if (array_key_exists('var', $children)) {
			$var = $children['var'];
			if (is_array($var) && array_key_exists('children', $var) && is_array($var['children'])) {
				return array_values($var['children']);
			}
			return [$var];
		}

		if (is_array($children)) {
			return array_values($children);
		}

		return [];
	}

	private function readTypeName(mixed $typeNode): ?string
	{
		if (!is_array($typeNode)) {
			return null;
		}

		$flags = (int) ($typeNode['flags'] ?? 0);
		$kind = (int) ($typeNode['kind'] ?? 0);

		if ($kind === AstKind::NULLABLE_TYPE) {
			$inner = $this->readTypeName($typeNode['children']['type'] ?? null);
			return $inner !== null ? '?' . ltrim($inner, '?') : null;
		}

		if ($kind === AstKind::NAME) {
			$name = (string) ($typeNode['children']['name'] ?? '');
			return $name !== '' ? $name : null;
		}

		return match ($flags) {
			AstKind::TYPE_VOID => 'void',
			AstKind::TYPE_BOOL => 'bool',
			AstKind::TYPE_LONG => 'int',
			AstKind::TYPE_DOUBLE => 'float',
			AstKind::TYPE_STRING => 'string',
			default => null,
		};
	}
}
