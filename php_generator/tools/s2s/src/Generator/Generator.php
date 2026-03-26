<?php
declare(strict_types=1);

namespace Scpp\S2S\Generator;

use Scpp\S2S\Emit\CppFile;
use Scpp\S2S\IR\ClassDecl;
use Scpp\S2S\IR\ConstantDecl;
use Scpp\S2S\IR\FunctionDecl;
use Scpp\S2S\IR\MethodDecl;
use Scpp\S2S\IR\ParamDecl;
use Scpp\S2S\IR\PhpFile;
use Scpp\S2S\IR\PropertyDecl;
use Scpp\S2S\IR\Statement;
use Scpp\S2S\IR\UseDecl;
use Scpp\S2S\Lowering\TypeMapper;
use Scpp\S2S\Support\AstKind;

/**
 * Emits Simple C++ declarations and statements from the IR. This file is where the catalog rules are turned into concrete header/source text.
 *
 * Relationship to specs:
 * - this type exists to keep the implementation aligned with php_generator/specs/rules.md and rules_catalog.md
 * - the implementation favors explicit normalized data over ad-hoc AST access during emission
 */
final class Generator
{
	/** @var array<string, bool> */
	private array $declaredLocals = [];
	/** @var list<string> */
	private array $errors = [];
	/** @var array<string, string> */
	private array $localTypeComments = [];
	/** @var array<string, string> */
	private array $declaredLocalTypes = [];
	/** @var array<string, bool> */
	private array $predefinedConstants = [];
	private NameRegistry $nameRegistry;
	/** @var array<string, FunctionDecl> */
	private array $functionDecls = [];
	/** @var array<string, MethodDecl> */
	private array $methodDecls = [];
	private ?string $currentReturnType = null;
	private ?string $currentClassName = null;
	private ?string $currentParentClass = null;
	private int $tempCounter = 0;

	/**

	 * Stores collaborators and default state for this phase object.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	public function __construct(
		private readonly TypeMapper $typeMapper = new TypeMapper(),
	) {
		$this->predefinedConstants = $this->loadPredefinedConstants();
		$this->nameRegistry = new NameRegistry();
	}

	/**

	 * Generates the header/source pair for one lowered PHP file and accumulates generator diagnostics.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	public function generate(PhpFile $file): CppFile
	{
		$this->declaredLocals = [];
		$this->errors = [];
		$this->localTypeComments = $file->localTypeCommentsByKey;
		$this->declaredLocalTypes = [];
		$this->tempCounter = 0;
		$this->nameRegistry = NameRegistry::fromPhpFile($file);
		$this->functionDecls = $this->collectFunctionDecls($file);
		$this->methodDecls = $this->collectMethodDecls($file);
		$this->validatePhpFile($file);

		$baseName = pathinfo($file->path, PATHINFO_FILENAME);
		$header = ['#pragma once', '', '#include <scpp/runtime.hpp>', ''];
		$source = ['#include "' . $baseName . '.hpp"', ''];

		$hasRootNamespaceContent = ($file->rootUses !== [] || $file->constants !== [] || $file->classes !== [] || $file->functions !== [] || $file->rootStatements !== []);
		$rootMainName = $file->rootStatements !== [] ? '__scpp_main' : null;
		if ($hasRootNamespaceContent) {
			$this->emitNamespaceBlock($header, $source, 'scpp', null, $file->rootUses, $file->constants, $file->classes, $file->functions, $file->rootStatements, $rootMainName);
		}

		$namespaceMainTargets = [];
		foreach ($file->namespaces as $namespace) {
			$mainName = $namespace->statements !== [] ? '__scpp_main' : null;
			$this->emitNamespaceBlock(
				$header,
				$source,
				'scpp::' . str_replace('\\', '::', $namespace->name),
				$namespace->name,
				$namespace->uses,
				$namespace->constants,
				$namespace->classes,
				$namespace->functions,
				$namespace->statements,
				$mainName,
			);
			if ($mainName !== null) {
				$namespaceMainTargets[] = '::scpp::' . str_replace('\\', '::', $namespace->name) . '::' . $mainName . '()';
			}
		}

		if ($file->rootStatements !== [] && $namespaceMainTargets !== []) {
			$this->errors[] = 'Root executable statements and namespace executable statements are not mixed in the current pass.';
		}

		if ($file->rootStatements !== []) {
			$source[] = 'int main() {';
			$source[] = $this->indent(1) . 'return scpp::__scpp_main();';
			$source[] = '}';
			$source[] = '';
		} elseif ($namespaceMainTargets !== []) {
			$source[] = 'int main() {';
			$source[] = $this->indent(1) . 'return ' . $namespaceMainTargets[0] . ';';
			$source[] = '}';
			$source[] = '';
		}

		return new CppFile($baseName, $header, $source, $this->errors);
	}


	/** @return array<string, FunctionDecl> */
	private function collectFunctionDecls(PhpFile $file): array
	{
		$out = [];

		foreach ($file->functions as $function) {
			$out[$function->name] = $function;
		}

		foreach ($file->namespaces as $namespace) {
			foreach ($namespace->functions as $function) {
				$out[$namespace->name . '\\' . $function->name] = $function;
			}
		}

		return $out;
	}

	/** @return array<string, MethodDecl> */
	private function collectMethodDecls(PhpFile $file): array
	{
		$out = [];

		foreach ($file->classes as $class) {
			foreach ($class->methods as $method) {
				$out[$class->name . '::' . $method->name] = $method;
			}
		}

		foreach ($file->namespaces as $namespace) {
			foreach ($namespace->classes as $class) {
				$qualifiedClass = $namespace->name . '\\' . $class->name;
				foreach ($class->methods as $method) {
					$out[$qualifiedClass . '::' . $method->name] = $method;
				}
			}
		}

		return $out;
	}

	private function lookupFunctionDeclByCall(mixed $nameExpr, ?string $namespacePhp): ?FunctionDecl
	{
		if (!is_object($nameExpr) || ($nameExpr->kind ?? null) !== AstKind::NAME) {
			return null;
		}

		$phpName = (string) ($nameExpr->children['name'] ?? '');
		$flags = (int) ($nameExpr->flags ?? 0);
		$resolved = $this->nameRegistry->resolveFunction($phpName, $flags, $namespacePhp);
		if ($resolved !== null && isset($this->functionDecls[$resolved])) {
			return $this->functionDecls[$resolved];
		}

		$trimmed = ltrim($phpName, '\\');
		return $this->functionDecls[$trimmed] ?? null;
	}

	private function lookupMethodDeclByStaticCall(mixed $classNode, string $methodName, ?string $namespacePhp): ?MethodDecl
	{
		if (!is_object($classNode) || ($classNode->kind ?? null) !== AstKind::NAME) {
			return null;
		}

		$phpClass = (string) ($classNode->children['name'] ?? '');
		$flags = (int) ($classNode->flags ?? 0);
		$resolvedClass = $this->nameRegistry->resolveClass($phpClass, $flags, $namespacePhp) ?? ltrim($phpClass, '\\');
		return $this->methodDecls[$resolvedClass . '::' . $methodName] ?? null;
	}

	private function lookupMethodDeclByCurrentClass(string $methodName, ?string $namespacePhp): ?MethodDecl
	{
		if ($this->currentClassName === null) {
			return null;
		}

		$qualifiedClass = $namespacePhp !== null && $namespacePhp !== ''
			? $namespacePhp . '\\' . $this->currentClassName
			: $this->currentClassName;

		return $this->methodDecls[$qualifiedClass . '::' . $methodName] ?? $this->methodDecls[$this->currentClassName . '::' . $methodName] ?? null;
	}

	private function renderCallArgsForParams(array $params, array $args, ?string $namespacePhp): string
	{
		$lastParam = $params === [] ? null : $params[array_key_last($params)];
		if (!$lastParam instanceof ParamDecl || !$lastParam->isVariadic) {
			return $this->renderArgs($args, $namespacePhp);
		}

		$fixedCount = count($params) - 1;
		$out = [];
		for ($i = 0; $i < $fixedCount; ++$i) {
			if (array_key_exists($i, $args)) {
				$out[] = $this->renderExpr($args[$i], $namespacePhp);
			}
		}

		$variadicType = $lastParam->type !== null
			? $this->typeMapper->mapDeclaredType($lastParam->type)
			: '/* ERROR missing-variadic-element-type */';

		$packedValues = [];
		for ($i = $fixedCount; $i < count($args); ++$i) {
			$packedValues[] = $this->renderExpr($args[$i], $namespacePhp);
		}

		$out[] = '::scpp::vector_t<' . $variadicType . '>{' . implode(', ', $packedValues) . '}';
		return implode(', ', $out);
	}


	private function validatePhpFile(PhpFile $file): void
	{
		foreach ($file->namespaces as $namespace) {
			foreach ($namespace->statements as $statement) {
				if ($statement->kind === 'static_var') {
					$this->errors[] = 'Namespace-scope static variable is rejected in namespace ' . $namespace->name . ' at line ' . $statement->line . '.';
				}
			}
		}

		$executingNamespaces = [];
		foreach ($file->namespaces as $namespace) {
			if ($namespace->statements !== []) {
				$executingNamespaces[] = $namespace->name;
			}
		}
		$executingNamespaces = array_values(array_unique($executingNamespaces));
		$execCount = count($executingNamespaces);
		for ($i = 0; $i < $execCount; $i++) {
			for ($j = $i + 1; $j < $execCount; $j++) {
				$left = $executingNamespaces[$i];
				$right = $executingNamespaces[$j];
				if (str_starts_with($right, $left . '\\') || str_starts_with($left, $right . '\\')) {
					$this->errors[] = 'Nested parent/child execution conflict is rejected between namespaces ' . $left . ' and ' . $right . '.';
				}
			}
		}

		$this->validateStatementList($file->rootStatements, null);
		foreach ($file->functions as $function) {
			$this->validateFunctionLikeParameters($function->params, 'function ' . $function->name);
			$this->validateStatementList($function->statements, null);
		}
		foreach ($file->classes as $class) {
			foreach ($class->properties as $property) {
				$this->validatePropertyDeclaration($class, $property);
			}
			foreach ($class->methods as $method) {
				$this->validateFunctionLikeParameters($method->params, 'method ' . $class->name . '::' . $method->name);
				$this->validateStatementList($method->statements, null);
			}
		}
		foreach ($file->namespaces as $namespace) {
			$this->validateStatementList($namespace->statements, $namespace->name);
			foreach ($namespace->functions as $function) {
				$this->validateFunctionLikeParameters($function->params, 'function ' . $function->name);
				$this->validateStatementList($function->statements, $namespace->name);
			}
			foreach ($namespace->classes as $class) {
				foreach ($class->properties as $property) {
					$this->validatePropertyDeclaration($class, $property);
				}
				foreach ($class->methods as $method) {
					$this->validateFunctionLikeParameters($method->params, 'method ' . $class->name . '::' . $method->name);
					$this->validateStatementList($method->statements, $namespace->name);
				}
			}
		}
	}

	/** @param list<ParamDecl> $params */
	private function validateFunctionLikeParameters(array $params, string $owner): void
	{
		foreach ($params as $param) {
			if ($param->nativeType !== null && $param->docType !== null) {
				$this->errors[] = 'Conflicting parameter type sources for ' . $owner . '::$' . $param->name . ' at line ' . $param->line . ': use either a native PHP type or a doc-comment type, not both.';
				continue;
			}
			if ($param->type === null) {
				$this->errors[] = 'Missing explicit parameter type for ' . $owner . '::$' . $param->name . ' at line ' . $param->line . '.';
			}
		}
	}

	private function validatePropertyDeclaration(ClassDecl $class, PropertyDecl $property): void
	{
		if ($property->nativeType !== null && $property->docType !== null) {
			$this->errors[] = 'Conflicting property type sources for ' . $class->name . '::$' . $property->name . ' at line ' . $property->line . ': use either a native PHP type or a doc-comment type, not both.';
			return;
		}
		if ($property->type === null && !$property->hasDefault) {
			$this->errors[] = 'Missing explicit property type for ' . $class->name . '::$' . $property->name . ' at line ' . $property->line . '. Add a type or a default value so the generator can infer one.';
		}
	}

	/** @param list<Statement> $statements */
	private function validateStatementList(array $statements, ?string $namespacePhp): void
	{
		$localKinds = [];
		foreach ($statements as $statement) {
			if ($statement->kind === 'assign') {
				$name = $this->extractSimpleVarName($statement->payload['var'] ?? null);
				if ($name !== null) {
					$localKinds[$name] = $this->inferValidationKind($statement->payload['expr'] ?? null, $localKinds);
				}
				$this->validateExprTree($statement->payload['expr'] ?? null, $namespacePhp, $localKinds, $statement->line);
				continue;
			}
			if ($statement->kind === 'assign_op') {
				$this->validateExprTree($statement->payload['expr'] ?? null, $namespacePhp, $localKinds, $statement->line);
				continue;
			}
			if ($statement->kind === 'assign_ref') {
				$name = $this->extractSimpleVarName($statement->payload['var'] ?? null);
				if ($name !== null) {
					$localKinds[$name] = 'unknown';
				}
				$this->validateExprTree($statement->payload['expr'] ?? null, $namespacePhp, $localKinds, $statement->line);
				continue;
			}
			if ($statement->kind === 'expr' || $statement->kind === 'return' || $statement->kind === 'echo' || $statement->kind === 'unset') {
				$this->validateExprTree($statement->payload, $namespacePhp, $localKinds, $statement->line);
				continue;
			}
			if ($statement->kind === 'if') {
				foreach ($statement->payload as $branch) {
					$this->validateExprTree($branch['cond'] ?? null, $namespacePhp, $localKinds, (int) ($branch['line'] ?? $statement->line));
					$this->validateStatementList($branch['stmts'] ?? [], $namespacePhp);
				}
				continue;
			}
			if ($statement->kind === 'while' || $statement->kind === 'do_while') {
				$this->validateExprTree($statement->payload['cond'] ?? null, $namespacePhp, $localKinds, $statement->line);
				$this->validateStatementList($statement->payload['stmts'] ?? [], $namespacePhp);
				continue;
			}
			if ($statement->kind === 'for') {
				foreach (($statement->payload['init'] ?? []) as $expr) {
					$this->validateExprTree($expr, $namespacePhp, $localKinds, $statement->line);
				}
				foreach (($statement->payload['cond'] ?? []) as $expr) {
					$this->validateExprTree($expr, $namespacePhp, $localKinds, $statement->line);
				}
				foreach (($statement->payload['loop'] ?? []) as $expr) {
					$this->validateExprTree($expr, $namespacePhp, $localKinds, $statement->line);
				}
				$this->validateStatementList($statement->payload['stmts'] ?? [], $namespacePhp);
				continue;
			}
			if ($statement->kind === 'foreach') {
				$this->validateExprTree($statement->payload['expr'] ?? null, $namespacePhp, $localKinds, $statement->line);
				$this->validateStatementList($statement->payload['stmts'] ?? [], $namespacePhp);
				continue;
			}
			if ($statement->kind === 'switch') {
				$this->validateExprTree($statement->payload['cond'] ?? null, $namespacePhp, $localKinds, $statement->line);
				foreach (($statement->payload['cases'] ?? []) as $case) {
					$this->validateExprTree($case['cond'] ?? null, $namespacePhp, $localKinds, $statement->line);
					$this->validateStatementList($case['stmts'] ?? [], $namespacePhp);
				}
			}
		}
	}

	/** @param array<string, string> $localKinds */
	private function validateExprTree(mixed $expr, ?string $namespacePhp, array $localKinds, int $line): void
	{
		if (!is_object($expr)) {
			return;
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::NEW) {
			$classNode = $expr->children['class'] ?? null;
			if (is_object($classNode) && (($classNode->kind ?? null) === AstKind::NAME)) {
				$rawName = (string) ($classNode->children['name'] ?? '');
				if ($namespacePhp !== null && $namespacePhp !== '' && $rawName !== '' && !str_starts_with($rawName, '\\') && str_starts_with($rawName, $namespacePhp . '\\')) {
					$this->errors[] = 'Qualified self-reference construction is rejected at line ' . $line . ': use ' . substr($rawName, strlen($namespacePhp) + 1) . ' or \\' . $rawName . '.';
				}
			}
		}

		if ($kind === AstKind::BINARY_OP) {
			$flags = (int) ($expr->flags ?? 0);
			if (in_array($flags, [AstKind::PLUS, AstKind::MINUS, AstKind::MUL, 4, 5], true)) {
				$leftKind = $this->inferValidationKind($expr->children['left'] ?? null, $localKinds);
				$rightKind = $this->inferValidationKind($expr->children['right'] ?? null, $localKinds);
				if ($leftKind === 'string' || $rightKind === 'string') {
					$this->errors[] = 'String used in arithmetic is rejected at line ' . $line . '.';
				}
			}
		}

		foreach ($this->childNodesOf($expr) as $child) {
			$this->validateExprTree($child, $namespacePhp, $localKinds, $line);
		}
	}

	/** @param array<string, string> $localKinds */
	private function inferValidationKind(mixed $expr, array $localKinds): string
	{
		if (is_string($expr)) {
			return 'string';
		}
		if (is_int($expr) || is_float($expr)) {
			return 'number';
		}
		if (!is_object($expr)) {
			return 'unknown';
		}
		$kind = $expr->kind ?? null;
		if ($kind === AstKind::VAR) {
			$name = (string) ($expr->children['name'] ?? '');
			return $localKinds[$name] ?? 'unknown';
		}
		if ($kind === AstKind::CONST) {
			$name = strtolower((string) ($expr->children['name']->children['name'] ?? ''));
			if ($name === 'true' || $name === 'false') {
				return 'bool';
			}
			if ($name === 'null') {
				return 'null';
			}
		}
		if ($kind === AstKind::CAST) {
			$flags = (int) ($expr->flags ?? 0);
			if ($flags === AstKind::TYPE_STRING) {
				return 'string';
			}
			if ($flags === AstKind::TYPE_LONG || $flags === AstKind::TYPE_DOUBLE) {
				return 'number';
			}
		}
		if ($kind === AstKind::ENCAPS_LIST) {
			return 'string';
		}
		if ($kind === AstKind::BINARY_OP) {
			$flags = (int) ($expr->flags ?? 0);
			if ($flags === AstKind::BINARY_CONCAT) {
				return 'string';
			}
			if (in_array($flags, [AstKind::PLUS, AstKind::MINUS, AstKind::MUL, 4, 5], true)) {
				return 'number';
			}
		}
		return 'unknown';
	}

	/** @return list<mixed> */
	private function childNodesOf(mixed $node): array
	{
		if (!is_object($node) || !isset($node->children) || !is_array($node->children)) {
			return [];
		}
		$out = [];
		foreach ($node->children as $child) {
			if (is_object($child)) {
				$out[] = $child;
				continue;
			}
			if (is_array($child)) {
				foreach ($child as $nested) {
					if (is_object($nested)) {
						$out[] = $nested;
					}
				}
			}
		}
		return $out;
	}

	/** @param list<UseDecl> $uses @param list<ConstantDecl> $constants @param list<ClassDecl> $classes @param list<FunctionDecl> $functions @param list<Statement> $statements */
	private function emitNamespaceBlock(array &$header, array &$source, string $namespaceCpp, ?string $namespacePhp, array $uses, array $constants, array $classes, array $functions, array $statements, ?string $syntheticMainName = null): void
	{
		$header[] = 'namespace ' . $namespaceCpp . ' {';
		$header[] = '';
		$source[] = 'namespace ' . $namespaceCpp . ' {';
		$source[] = $this->indent(1) . 'using namespace ::scpp::php;';
		$source[] = '';

		foreach ($uses as $use) {
			$useLine = $this->renderUseDeclaration($use);

			if ($useLine === null) {
				continue;
			}
			foreach (explode("\n", $useLine) as $line) {
				if ($line === '') {
					continue;
				}
				$source[] = $this->indent(1) . $line;
			}
		}
		if ($uses !== []) {
			$source[] = '';
		}
		
		foreach ($constants as $constant) {
			$this->emitConstant($header, $constant, $namespacePhp);
		}
		if ($constants !== []) {
			$header[] = '';
		}

		foreach ($classes as $class) {
			$this->emitClass($header, $source, $class, $namespacePhp);
		}
		foreach ($functions as $function) {
			$this->emitFunction($header, $source, $function, $namespacePhp);
		}
		if ($syntheticMainName !== null) {
			$this->emitNamespaceMain($header, $source, $syntheticMainName, $statements, $namespacePhp);
		}

		$header[] = '}';
		$header[] = '';
		$source[] = '}';
		$source[] = '';
	}

	/** @param list<UseDecl> $uses @return list<string> */
	
	private function renderUseDeclaration(UseDecl $use): ?string
	{
		$name = $use->name;

		if ($name === '') {
			$this->errors[] = 'Empty use import is not supported at line ' . $use->line . '.';
			return null;
		}

		$fq = '::scpp::' . str_replace('\\', '::', ltrim($name, '\\'));

		if ($use->alias === null) {
			return 'using ' . $fq . ';';
		}

		return match ($use->kind) {
			'function' => 'inline constexpr auto ' . $use->alias . ' = ' . $fq . ';',
			'const' => 'inline constexpr auto& ' . $use->alias . ' = ' . $fq . ';',
			default => 'using ' . $use->alias . ' = ' . $fq . ';',
		};
	}

	/**

	 * Emits one lowered constant as an inline namespace-scoped declaration in the header.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function emitConstant(array &$header, ConstantDecl $constant, ?string $namespacePhp): void
	{
		$header[] = 'inline const auto ' . $constant->name . ' = ' . $this->renderExpr($constant->value, $namespacePhp) . ';';
	}

	/**

	 * Emits a class declaration to the header and its method definitions to the source file.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function emitClass(array &$header, array &$source, ClassDecl $class, ?string $namespacePhp): void
	{
		$extends = [];
		if ($class->parentClass !== null) {
			$extends[] = 'public ' . $this->typeMapper->mapClassName($class->parentClass);
		}
		foreach ($class->interfaces as $interface) {
			$extends[] = 'public ' . $this->typeMapper->mapClassName($interface);
		}
		$header[] = 'class ' . $class->name . ($extends !== [] ? ' : ' . implode(', ', $extends) : '') . ' {';
		$header[] = 'public:';
		foreach ($class->properties as $property) {
			$initializer = $property->hasDefault
				? $this->renderInitializerExpr($property->default, $property->type, $namespacePhp)
				: null;
			if ($property->type !== null) {
				$type = $this->typeMapper->mapDeclaredType($property->type);
			} elseif ($initializer !== null) {
				$type = 'decltype(' . $initializer . ')';
			} else {
				$type = '/* ERROR missing-property-type */';
			}
			$line = $type . ' ' . $property->name;
			if ($property->isStatic) {
				$line = 'static ' . $line;
				if ($initializer !== null) {
					$line .= ';';
				}
			} elseif ($initializer !== null) {
				$line .= ' = ' . $initializer;
			}
			$header[] = $this->indent(1) . rtrim($line, ';') . ';';
		}
		foreach ($class->constants as $constant) {
			$header[] = $this->indent(1) . 'static inline const auto ' . $constant->name . ' = ' . $this->renderExpr($constant->value, $namespacePhp) . ';';
		}
		foreach ($class->methods as $method) {
			$header[] = $this->indent(1) . $this->renderMethodDeclaration($method, $class, $namespacePhp) . ';';
		}
		$header[] = '};';
		$header[] = '';

		foreach ($class->properties as $property) {
			if (!$property->isStatic) {
				continue;
			}
			$default = $property->hasDefault
				? $this->renderInitializerExpr($property->default, $property->type, $namespacePhp)
				: null;
			if ($property->type !== null) {
				$type = $this->typeMapper->mapDeclaredType($property->type);
			} elseif ($default !== null) {
				$type = 'decltype(' . $default . ')';
			} else {
				$type = '/* ERROR missing-property-type */';
			}
			$source[] = $type . ' ' . $class->name . '::' . $property->name . ' = ' . ($default ?? ($type . '{}')) . ';';
		}
		if (!$class->isInterface && array_filter($class->properties, static fn ($property): bool => $property->isStatic) !== []) {
			$source[] = '';
		}

		if (!$class->isInterface) {
			$prevClassName = $this->currentClassName;
			$prevParentClass = $this->currentParentClass;
			$this->currentClassName = $class->name;
			$this->currentParentClass = $class->parentClass;
			foreach ($class->methods as $method) {
				if ($this->methodIsAbstract($method, $class)) {
					continue;
				}
				$source[] = $this->renderMethodDefinition($class, $method, $namespacePhp);
				$source[] = '';
			}
			$this->currentClassName = $prevClassName;
			$this->currentParentClass = $prevParentClass;
		}
	}

	/**

	 * Emits one top-level function declaration/definition pair.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function emitFunction(array &$header, array &$source, FunctionDecl $function, ?string $namespacePhp): void
	{
		$header[] = $this->renderFunctionDeclaration($function, $namespacePhp) . ';';
		$header[] = '';
		$source[] = $this->renderFunctionDefinition($function, $namespacePhp);
		$source[] = '';
	}

	/**

	 * Emits the synthetic entry point used for executable namespace/root statements.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function emitNamespaceMain(array &$header, array &$source, string $name, array $statements, ?string $namespacePhp): void
	{
		$header[] = 'int ' . $name . '();';
		$header[] = '';
		$source[] = 'int ' . $name . '() {';
		$this->declaredLocals = [];
		$this->declaredLocalTypes = [];
		$this->currentReturnType = 'int';
		foreach ($this->renderStatementSequence($statements, $namespacePhp) as $line) {
			$source[] = $this->indent(1) . $line;
		}
		$source[] = $this->indent(1) . 'return 0;';
		$source[] = '}';
		$this->currentReturnType = null;
	}

	/**

	 * Renders a method signature using the current type and constructor mapping rules.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderMethodDeclaration(MethodDecl $method, ClassDecl|string|null $classDecl = null, ?string $namespacePhp = null): string
	{
		$className = is_string($classDecl) ? $classDecl : ($classDecl?->name);
		if ($method->name === '__construct' && $className !== null) {
			return $className . '(' . $this->renderParams($method->params, true, $namespacePhp) . ')';
		}
		if ($method->name === '__destruct' && $className !== null) {
			return '~' . $className . '()';
		}
		$prefix = $method->isStatic ? 'static ' : '';
		if (!$method->isStatic && $classDecl instanceof ClassDecl && ($classDecl->isInterface || $classDecl->parentClass !== null || $classDecl->interfaces !== [])) {
			$prefix .= 'virtual ';
		}
		$returnType = $this->typeMapper->mapReturnType($method->returnType, $method->returnsByReference);
		$declaration = $prefix . $returnType . ' ' . $method->name . '(' . $this->renderParams($method->params, true, $namespacePhp) . ')';
		if ($classDecl instanceof ClassDecl && $this->methodIsAbstract($method, $classDecl)) {
			$declaration .= ' = 0';
		}
		return $declaration;
	}

	/**

	 * Renders the out-of-class method definition body into the source file.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	
	private function methodIsAbstract(MethodDecl $method, ClassDecl $class): bool
	{
		return $class->isInterface || ($method->statements === [] && $method->name !== '__construct' && $method->name !== '__destruct');
	}

	private function extractParentConstructorArgs(array $statements): ?array
	{
		$first = $statements[0] ?? null;
		if (!$first instanceof Statement || $first->kind !== 'expr' || !is_array($first->payload)) {
			return null;
		}
		$expr = $first->payload;
		if (($expr->kind ?? null) !== AstKind::STATIC_CALL) {
			return null;
		}
		$classNode = $expr->children['class'] ?? null;
		$method = (string) ($expr->children['method'] ?? '');
		if (!is_object($classNode) || ($classNode->kind ?? null) !== AstKind::NAME) {
			return null;
		}
		$name = strtolower((string) ($classNode->children['name'] ?? ''));
		if ($name !== 'parent' || $method !== '__construct') {
			return null;
		}
		return $expr->children['args']->children ?? [];
	}

private function renderMethodDefinition(ClassDecl $class, MethodDecl $method, ?string $namespacePhp): string
	{
		$this->declaredLocals = [];
		$this->declaredLocalTypes = [];
		foreach ($method->params as $param) {
			$this->declaredLocals[$param->name] = true;
			if ($param->type !== null) {
				$this->declaredLocalTypes[$param->name] = $param->type;
			}
		}
		$className = $class->name;
		$statements = $method->statements;
		$initializer = '';
		if ($method->name === '__construct') {
			$this->currentReturnType = null;
			if ($class->parentClass !== null) {
				$parentArgs = $this->extractParentConstructorArgs($statements);
				if ($parentArgs !== null) {
					$initializer = ' : ' . $this->typeMapper->mapClassName($class->parentClass) . '(' . $this->renderArgs($parentArgs, $namespacePhp) . ')';
					array_shift($statements);
				}
			}
			$signature = $className . '::' . $className . '(' . $this->renderParams($method->params, false, $namespacePhp) . ')' . $initializer;
		} elseif ($method->name === '__destruct') {
			$this->currentReturnType = null;
			$signature = $className . '::~' . $className . '()';
		} else {
			$returnType = $this->typeMapper->mapReturnType($method->returnType, $method->returnsByReference);
			$this->currentReturnType = $returnType;
			$signature = $returnType . ' ' . $className . '::' . $method->name . '(' . $this->renderParams($method->params, false, $namespacePhp) . ')';
		}
		$body = $this->renderBody($statements, $namespacePhp);
		$this->currentReturnType = null;
		return $signature . " {
" . $body . "
}";
	}

	/**

	 * Renders a function signature for the generated header.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderFunctionDeclaration(FunctionDecl $function, ?string $namespacePhp = null): string
	{
		$returnType = $this->typeMapper->mapReturnType($function->returnType, $function->returnsByReference);
		return $returnType . ' ' . $function->name . '(' . $this->renderParams($function->params, true, $namespacePhp) . ')';
	}

	/**

	 * Renders one full function body for the generated source file.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderFunctionDefinition(FunctionDecl $function, ?string $namespacePhp): string
	{
		$this->declaredLocals = [];
		$this->declaredLocalTypes = [];
		foreach ($function->params as $param) {
			$this->declaredLocals[$param->name] = true;
			if ($param->type !== null) {
				$this->declaredLocalTypes[$param->name] = $param->type;
			}
		}
		$returnType = $this->typeMapper->mapReturnType($function->returnType, $function->returnsByReference);
		$this->currentReturnType = $returnType;
		$signature = $returnType . ' ' . $function->name . '(' . $this->renderParams($function->params, false, $namespacePhp) . ')';
		$body = $this->renderBody($function->statements, $namespacePhp);
		$this->currentReturnType = null;
		return $signature . " {\n" . $body . "\n}";
	}

	/**

	 * Renders the lowered parameter list, optionally including default expressions when a declaration requires them.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderParams(array $params, bool $includeDefaults, ?string $namespacePhp): string
	{
		$out = [];
		foreach ($params as $param) {
			if ($param->isVariadic) {
				$elementType = $param->type !== null ? $this->typeMapper->mapDeclaredType($param->type) : '/* ERROR missing-variadic-element-type */';
				$type = 'const vector_t<' . $elementType . '>&';
			} else {
				$type = $param->type !== null ? $this->typeMapper->mapParamType($param->type, $param->isReference) : '/* ERROR missing-parameter-type */';
			}
			$rendered = $type . ' ' . $param->name;
			if (!$param->isVariadic && $includeDefaults && $param->default !== null) {
				$rendered .= ' = ' . $this->renderExpr($param->default, $namespacePhp);
			}
			$out[] = $rendered;
		}
		return implode(', ', $out);
	}

	/**

	 * Renders a list of lowered statements as an indented C++ block body.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderBody(array $statements, ?string $namespacePhp): string
	{
		$lines = [];
		foreach ($this->renderStatementSequence($statements, $namespacePhp) as $line) {
			$lines[] = $this->indent(1) . $line;
		}
		return implode("\n", $lines);
	}

	/**

	 * Renders one lowered statement kind into one or more C++ source lines.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderStatement(Statement $statement, ?string $namespacePhp): array
	{
		if ($statement->kind === 'assign' || $statement->kind === 'assign_ref' || $statement->kind === 'assign_op') {
			$varNode = $statement->payload['var'] ?? null;
			$exprNode = $statement->payload['expr'] ?? null;
			$name = $this->extractSimpleVarName($varNode);
			$key = $statement->line . ':' . ($name ?? '');
			$typed = $name !== null ? ($this->localTypeComments[$key] ?? null) : null;

			if ($statement->kind === 'assign_op') {
				return $this->renderCompoundAssignmentStatement($statement, $varNode, $exprNode, $name, $namespacePhp);
			}

			if ($statement->kind === 'assign' && $name !== null && !isset($this->declaredLocals[$name])) {
				$chainLines = $this->tryRenderDeclarationAssignChain($varNode, $exprNode, $typed, $namespacePhp);
				if ($chainLines !== null) {
					return $chainLines;
				}
			}

			$effectiveTyped = $typed;
			if ($typed !== null) {
				[$effectiveTyped, $validationError] = $this->resolveTypedLocalTypeForAssignment($typed, $statement->kind, $exprNode, $statement->line);
				if ($validationError !== null) {
					$this->errors[] = $validationError;
					return ['// ERROR: ' . $validationError];
				}
				$validationError = $this->validateTypedLocalAssignment($effectiveTyped, $statement->kind, $exprNode, $statement->line);
				if ($validationError !== null) {
					$this->errors[] = $validationError;
					return ['// ERROR: ' . $validationError];
				}
			}

			$expr = $statement->kind === 'assign_ref'
				? $this->renderExpr($exprNode, $namespacePhp)
				: $this->renderInitializerExpr($exprNode, $effectiveTyped, $namespacePhp);
			$typedVectorType = $effectiveTyped !== null ? $this->mapTypedVectorLocalType($effectiveTyped) : null;
			$isTypedEmptyVectorLiteral = $statement->kind === 'assign' && $typedVectorType !== null && $this->isEmptyPositionalArrayLiteral($exprNode);
			if ($exprNode !== null && $this->isNullExpr($exprNode) && $typed === null && $name !== null && !isset($this->declaredLocals[$name])) {
				$this->errors[] = 'Untyped null assignment is rejected at line ' . $statement->line . '.';
				return ['// ERROR: untyped null assignment rejected'];
			}

			if ($statement->kind === 'assign_ref') {
				if ($name !== null && !isset($this->declaredLocals[$name])) {
					$this->declaredLocals[$name] = true;
					if ($effectiveTyped !== null) {
						$this->declaredLocalTypes[$name] = $this->normalizeStoredLocalType($effectiveTyped);
						return [$this->typeMapper->mapTypedLocalType($effectiveTyped) . ' ' . $name . ' = ' . $expr . ';'];
					}
					return ['auto& ' . $name . ' = ' . $expr . ';'];
				}
				$target = $this->renderAssignmentTarget($varNode, $namespacePhp);
				return [$target . ' = ' . $expr . ';'];
			}

			if ($name !== null && !isset($this->declaredLocals[$name])) {
				$this->declaredLocals[$name] = true;
				$inferredType = $effectiveTyped ?? $this->inferExprType($exprNode);
				if ($inferredType !== 'auto') {
					$this->declaredLocalTypes[$name] = $effectiveTyped !== null ? $this->normalizeStoredLocalType($effectiveTyped) : $inferredType;
				}
				if ($effectiveTyped !== null) {
					if ($isTypedEmptyVectorLiteral) {
						return [$typedVectorType . ' ' . $name . ' = {};'];
					}
					return [$this->typeMapper->mapTypedLocalType($effectiveTyped) . ' ' . $name . ' = ' . $expr . ';'];
				}
				return ['auto ' . $name . ' = ' . $expr . ';'];
			}
			if (is_object($varNode) && (($varNode->kind ?? null) === AstKind::DIM)) {
				if (($varNode->children['dim'] ?? null) === null) {
					$base = $this->renderExpr($varNode->children['expr'] ?? null, $namespacePhp);
					$value = $this->renderExpr($exprNode, $namespacePhp);
					$tempName = $this->nextTempName('__append_value');
					return [
						'{',
							'auto ' . $tempName . ' = ' . $value . ';',
							$base . '.push_back(' . $tempName . ');',
						'}',
					];
				}
				$assignment = $this->renderAssignmentExpr($varNode, $exprNode, $namespacePhp);
				return [$assignment . ';'];
			}
			if ($isTypedEmptyVectorLiteral && $name !== null) {
				return [$name . ' = ' . $typedVectorType . '{};'];
			}
			$target = $this->renderAssignmentTarget($varNode, $namespacePhp);
			return [$target . ' = ' . $expr . ';'];
		}

		if ($statement->kind === 'static_var') {
			$varNode = $statement->payload['var'] ?? null;
			$name = (string) (($varNode->children['name'] ?? '') ?: 'tmp');
			$default = $this->renderExpr($statement->payload['default'] ?? null, $namespacePhp);
			$this->declaredLocals[$name] = true;
			return ['static int_t ' . $name . ' = ' . $default . ';'];
		}

		if ($statement->kind === 'return') {
			if ($statement->payload === null) {
				return ['return;'];
			}
			return ['return ' . $this->renderReturnExpr($statement->payload, $namespacePhp) . ';'];
		}

		if ($statement->kind === 'echo') {
			// Keep the single-statement fallback lazy as well so operand evaluation order stays explicit.
			return ['::scpp::php::echo_eval(' . $this->renderEchoThunk($statement->payload, $namespacePhp) . ');'];
		}

		if ($statement->kind === 'unset') {
			// Preserve the exporter shape: one AST_UNSET node becomes one runtime unset call.
			return ['::scpp::php::unset(' . $this->renderExpr($statement->payload, $namespacePhp) . ');'];
		}

		if ($statement->kind === 'if') {
			return $this->renderIfStatement($statement->payload, $namespacePhp);
		}

		if ($statement->kind === 'while') {
			$lines = ['while (' . $this->renderConditionExpr($statement->payload['cond'] ?? null, $namespacePhp) . ') {'];
			foreach ($this->renderNestedStatements($statement->payload['stmts'] ?? [], $namespacePhp) as $line) {
				$lines[] = $line;
			}
			$lines[] = '}';
			return $lines;
		}

		if ($statement->kind === 'do_while') {
			$lines = ['do {'];
			foreach ($this->renderNestedStatements($statement->payload['stmts'] ?? [], $namespacePhp) as $line) {
				$lines[] = $line;
			}
			$lines[] = '} while (' . $this->renderConditionExpr($statement->payload['cond'] ?? null, $namespacePhp) . ');';
			return $lines;
		}

		if ($statement->kind === 'for') {
			$init = $this->renderForInit($statement->payload['init'] ?? [], $namespacePhp);
			$cond = $this->renderForConditionClause($statement->payload['cond'] ?? [], $namespacePhp);
			$loop = $this->renderForClause($statement->payload['loop'] ?? [], $namespacePhp, '');
			$lines = ['for (' . $init . '; ' . $cond . '; ' . $loop . ') {'];
			foreach ($this->renderNestedStatements($statement->payload['stmts'] ?? [], $namespacePhp) as $line) {
				$lines[] = $line;
			}
			$lines[] = '}';
			return $lines;
		}

		if ($statement->kind === 'foreach') {
			return $this->renderForeachStatement($statement, $namespacePhp);
		}

		if ($statement->kind === 'switch') {
			$lines = ['switch (' . $this->renderSwitchExpr($statement->payload['cond'] ?? null, $namespacePhp) . ') {'];
			foreach (($statement->payload['cases'] ?? []) as $case) {
				$caseCond = $case['cond'] ?? null;
				// Each lowered switch case is emitted in source order so generated case/default blocks preserve the catalog shape.
				$lines[] = $caseCond === null
					? $this->indent(1) . 'default:'
					: $this->indent(1) . 'case ' . $this->renderSwitchCaseValue($caseCond) . ':';
				foreach ($this->renderNestedStatements($case['stmts'] ?? [], $namespacePhp) as $line) {
					$lines[] = $line;
				}
			}
			$lines[] = '}';
			return $lines;
		}

		if ($statement->kind === 'break') {
			$depth = $statement->payload;
			if ($depth !== null && $depth !== 1) {
				$this->errors[] = 'break depth > 1 is not supported at line ' . $statement->line . '.';
				return ['// ERROR: unsupported break depth'];
			}
			return ['break;'];
		}

		if ($statement->kind === 'continue') {
			$depth = $statement->payload;
			if ($depth !== null && $depth !== 1) {
				$this->errors[] = 'continue depth > 1 is not supported at line ' . $statement->line . '.';
				return ['// ERROR: unsupported continue depth'];
			}
			return ['continue;'];
		}

		if ($statement->kind === 'expr') {
			return [$this->renderExpr($statement->payload, $namespacePhp) . ';'];
		}

		return ['// Unsupported statement'];
	}

	private function renderForeachStatement(Statement $statement, ?string $namespacePhp): array
	{
		$payload = is_array($statement->payload) ? $statement->payload : [];
		$sourceExpr = $this->renderExpr($payload['expr'] ?? null, $namespacePhp);
		$valueName = $this->extractSimpleVarName($payload['value'] ?? null);
		$keyName = $this->extractSimpleVarName($payload['key'] ?? null);
		$byRef = (bool) ($payload['by_ref'] ?? false);

		if ($valueName === null) {
			$this->errors[] = 'foreach value target must be a simple variable at line ' . $statement->line . '.';
			return ['// ERROR: unsupported foreach value target'];
		}

		if (($payload['key'] ?? null) !== null && $keyName === null) {
			$this->errors[] = 'foreach key target must be a simple variable at line ' . $statement->line . '.';
			return ['// ERROR: unsupported foreach key target'];
		}

		$indexName = '__scpp_foreach_i_' . $statement->line;
		$elementExpr = $sourceExpr . '.at(' . $indexName . ')';
		$valuePrefix = $byRef ? 'auto &' : 'auto ';

		$lines = [
			'for (int_t ' . $indexName . ' = static_cast<int_t>(0); static_cast<bool>(' . $indexName . ' < static_cast<int_t>(' . $sourceExpr . '.size())); ++' . $indexName . ') {',
		];

		$scopedLocals = $this->declaredLocals;

		if ($keyName !== null) {
			// Foreach key/value bindings are always emitted as fresh loop-local variables.
			// We intentionally shadow any outer local with the same PHP name so a by-reference
			// foreach can still lower to a native C++ reference binding on every iteration.
			$lines[] = $this->indent(1) . 'auto ' . $keyName . ' = ' . $indexName . ';';
			$this->declaredLocals[$keyName] = true;
		}

		$lines[] = $this->indent(1) . $valuePrefix . $valueName . ' = ' . $elementExpr . ';';
		$this->declaredLocals[$valueName] = true;

		foreach ($this->renderNestedStatements($payload['stmts'] ?? [], $namespacePhp) as $line) {
			$lines[] = $line;
		}

		$this->declaredLocals = $scopedLocals;
		$lines[] = '}';
		return $lines;
	}

	/** @param list<array{cond:mixed,stmts:list<Statement>,line:int}> $branches @return list<string> */
	private function renderIfStatement(array $branches, ?string $namespacePhp): array
	{
		$lines = [];
		$index = 0;
		foreach ($branches as $branch) {
			$prefix = $index === 0 ? 'if' : (($branch['cond'] ?? null) === null ? 'else' : 'else if');
			if ($prefix === 'else') {
				$lines[] = 'else {';
			} else {
				$lines[] = $prefix . ' (' . $this->renderConditionExpr($branch['cond'] ?? null, $namespacePhp) . ') {';
			}
			foreach ($this->renderNestedStatements($branch['stmts'] ?? [], $namespacePhp) as $line) {
				$lines[] = $line;
			}
			$lines[] = '}';
			$index++;
		}
		return $lines;
	}

	/** @param list<Statement> $statements @return list<string> */
	private function renderNestedStatements(array $statements, ?string $namespacePhp): array
	{
		$lines = [];
		foreach ($this->renderStatementSequence($statements, $namespacePhp) as $line) {
			$lines[] = $this->indent(1) . $line;
		}
		return $lines;
	}

	/** @param list<Statement> $statements */
	private function renderStatementSequence(array $statements, ?string $namespacePhp): array
	{
		$lines = [];
		$count = count($statements);

		for ($i = 0; $i < $count; ++$i) {
			$statement = $statements[$i];
			if ($statement->kind === 'echo') {
				$thunks = [];
				while ($i < $count && $statements[$i]->kind === 'echo') {
					$thunks[] = $this->renderEchoThunk($statements[$i]->payload, $namespacePhp);
					++$i;
				}
				--$i;
				$lines[] = '::scpp::php::echo_eval(' . implode(', ', $thunks) . ');';
				continue;
			}

			foreach ($this->renderStatement($statement, $namespacePhp) as $line) {
				$lines[] = $line;
			}
		}

		return $lines;
	}

	private function renderEchoThunk(mixed $expr, ?string $namespacePhp): string
	{
		return '[&]() -> decltype(auto) { return ' . $this->renderExpr($expr, $namespacePhp) . '; }';
	}

	/** @param list<mixed> $exprs */
	private function renderForInit(array $exprs, ?string $namespacePhp): string
	{
		if ($exprs === []) {
			return '';
		}

		$out = [];
		foreach ($exprs as $expr) {
			if (is_object($expr) && ($expr->kind ?? null) === AstKind::ASSIGN) {
				$varNode = $expr->children['var'] ?? null;
				$name = $this->extractSimpleVarName($varNode);
				if ($name !== null && !isset($this->declaredLocals[$name])) {
					$this->declaredLocals[$name] = true;
					$out[] = 'auto ' . $name . ' = ' . $this->renderExpr($expr->children['expr'] ?? null, $namespacePhp);
					continue;
				}
			}
			$out[] = $this->renderExpr($expr, $namespacePhp);
		}
		return implode(', ', $out);
	}

	/** @param list<mixed> $exprs */
	private function renderForClause(array $exprs, ?string $namespacePhp, string $fallback): string
	{
		if ($exprs === []) {
			return $fallback;
		}
		return implode(', ', array_map(fn (mixed $expr): string => $this->renderExpr($expr, $namespacePhp), $exprs));
	}

	/** @param list<mixed> $exprs */
	private function renderForConditionClause(array $exprs, ?string $namespacePhp): string
	{
		if ($exprs === []) {
			return 'true';
		}
		if (count($exprs) === 1) {
			return $this->renderConditionExpr($exprs[0], $namespacePhp);
		}
		$last = array_pop($exprs);
		$prefix = implode(', ', array_map(fn (mixed $expr): string => $this->renderExpr($expr, $namespacePhp), $exprs));
		return '(' . $prefix . ', ' . $this->renderConditionExpr($last, $namespacePhp) . ')';
	}

	/**

	 * Renders any condition expression with the bool conversion required by the current Simple C++ runtime contract.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderConditionExpr(mixed $expr, ?string $namespacePhp): string
	{
		$rendered = $this->renderExpr($expr, $namespacePhp);
		if ($this->exprProducesBool($expr)) {
			return 'static_cast<bool>(' . $rendered . ')';
		}
		return 'cast<bool>(' . $rendered . ')';
	}

	/**

	 * Best-effort classifier used to avoid redundant bool casts around expressions already known to produce bool_t.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function exprProducesBool(mixed $expr): bool
	{
		if (!is_object($expr)) {
			return false;
		}
		$kind = $expr->kind ?? null;
		if ($kind === AstKind::UNARY_OP) {
			return true;
		}
		if ($kind === AstKind::BINARY_OP) {
			return in_array((int) ($expr->flags ?? 0), [
				AstKind::BINARY_BOOL_AND,
				AstKind::BINARY_BOOL_OR,
				AstKind::BINARY_IS_SMALLER,
				AstKind::BINARY_IS_SMALLER_OR_EQUAL,
				AstKind::BINARY_IS_GREATER,
				AstKind::BINARY_IS_EQUAL,
				AstKind::BINARY_IS_IDENTICAL,
			], true);
		}
		return false;
	}

	/**

	 * Renders the controlling expression of a `switch`, bridging bool-like wrappers to native switch-compatible values when required.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderSwitchExpr(mixed $expr, ?string $namespacePhp): string
	{
		$rendered = $this->renderExpr($expr, $namespacePhp);
		return is_object($expr) ? '(' . $rendered . ').native_value()' : $rendered;
	}

	/**

	 * Renders a switch-case label and rejects unsupported non-literal case forms in the current prototype.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderSwitchCaseValue(mixed $expr): string
	{
		if (is_int($expr) || is_float($expr)) {
			return (string) $expr;
		}
		return '/* unsupported-switch-case */';
	}

	/**

	 * Extracts a plain variable name when an expression is simple enough to become a declaration target.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function extractSimpleVarName(mixed $expr): ?string
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::VAR)) {
			return null;
		}
		$name = (string) ($expr->children['name'] ?? '');
		return $name !== '' ? $name : null;
	}

	/**

	 * Renders the left-hand side of an assignment for the currently supported assignment targets.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderDimAccess(mixed $expr, ?string $namespacePhp): string
	{
		$base = $this->renderExpr($expr->children['expr'] ?? null, $namespacePhp);
		$dimNode = $expr->children['dim'] ?? null;
		if ($dimNode === null) {
			$this->errors[] = 'Append syntax cannot be used as a read expression.';
			return '/* unsupported-append-read */';
		}
		$dim = $this->renderExpr($dimNode, $namespacePhp);
		return $base . '.at(' . $dim . ')';
	}

	private function renderAssignmentExpr(mixed $varNode, mixed $valueNode, ?string $namespacePhp): string
	{
		if (is_object($varNode) && (($varNode->kind ?? null) === AstKind::DIM)) {
			$base = $this->renderExpr($varNode->children['expr'] ?? null, $namespacePhp);
			$value = $this->renderExpr($valueNode, $namespacePhp);
			$dimNode = $varNode->children['dim'] ?? null;
			if ($dimNode === null) {
				// PHP append assignment must evaluate the right-hand side exactly once.
				$tempName = $this->nextTempName('__append_value');
				return '([&]() { auto ' . $tempName . ' = ' . $value . '; ' . $base . '.push_back(' . $tempName . '); return ' . $tempName . '; }())';
			}
			$dim = $this->renderExpr($dimNode, $namespacePhp);
			return '(' . $base . '.at(' . $dim . ') = ' . $value . ')';
		}

		$target = $this->renderAssignmentTarget($varNode, $namespacePhp);
		$value = $this->renderExpr($valueNode, $namespacePhp);
		return '(' . $target . ' = ' . $value . ')';
	}

	private function nextTempName(string $prefix = '__tmp'): string
	{
		$this->tempCounter++;
		return $prefix . '_' . (string) $this->tempCounter;
	}


	private function renderCompoundAssignmentStatement(Statement $statement, mixed $varNode, mixed $exprNode, ?string $name, ?string $namespacePhp): array
	{
		$operator = $this->mapAssignOpFlagToOperator((int) ($statement->payload['flags'] ?? 0));
		if ($operator === null) {
			$error = 'Unsupported compound assignment flag ' . (int) ($statement->payload['flags'] ?? 0) . ' at line ' . $statement->line . '.';
			$this->errors[] = $error;
			return ['// ERROR: ' . $error];
		}

		if ($name !== null && !isset($this->declaredLocals[$name])) {
			$error = 'Compound assignment requires a previously declared variable $' . $name . ' at line ' . $statement->line . '.';
			$this->errors[] = $error;
			return ['// ERROR: ' . $error];
		}

		$target = $this->renderAssignmentTarget($varNode, $namespacePhp);
		if ((int) ($statement->payload['flags'] ?? 0) === AstKind::BINARY_CONCAT) {
			$expr = $this->renderStringOperand($exprNode, $namespacePhp);
			return [$target . ' = (' . $target . ' + ' . $expr . ');'];
		}

		$expr = $this->renderExpr($exprNode, $namespacePhp);
		return [$target . ' ' . $operator . ' ' . $expr . ';'];
	}

	private function mapAssignOpFlagToOperator(int $flag): ?string
	{
		return match ($flag) {
			AstKind::PLUS => '+=',
			AstKind::MINUS => '-=',
			AstKind::MUL => '*=',
			AstKind::DIV => '/=',
			AstKind::MOD => '%=',
			AstKind::SHIFT_LEFT => '<<=',
			AstKind::SHIFT_RIGHT => '>>=',
			AstKind::BITWISE_OR => '|=',
			AstKind::BITWISE_AND => '&=',
			AstKind::BITWISE_XOR => '^=',
			AstKind::BINARY_CONCAT => '+=',
			default => null,
		};
	}

	private function renderAssignmentTarget(mixed $expr, ?string $namespacePhp): string
	{
		if (is_object($expr) && (($expr->kind ?? null) === AstKind::STATIC_PROP)) {
			return $this->renderStaticPropertyAccess($expr, $namespacePhp);
		}

		if (is_object($expr) && (($expr->kind ?? null) === AstKind::DIM)) {
			return $this->renderDimAccess($expr, $namespacePhp);
		}

		return $this->renderExpr($expr, $namespacePhp);
	}

	private function renderMatchExpr(mixed $expr, ?string $namespacePhp): string
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::MATCH)) {
			return '/* unsupported-match */';
		}

		$subjectNode = $expr->children['cond'] ?? null;
		$subjectExpr = $this->renderExpr($subjectNode, $namespacePhp);
		$subjectName = $this->nextTempName('__match_subject');
		$armsNode = $expr->children['stmts'] ?? null;
		$arms = [];
		if (is_object($armsNode) && isset($armsNode->children) && is_array($armsNode->children)) {
			$arms = array_values($armsNode->children);
		}

		$parts = [];
		$parts[] = '([&]() {';
		$parts[] = 'auto ' . $subjectName . ' = ' . $subjectExpr . ';';

		$hasDefaultArm = false;
		foreach ($arms as $arm) {
			if (!is_object($arm) || (($arm->kind ?? null) !== AstKind::MATCH_ARM)) {
				$parts[] = 'throw std::runtime_error("Unsupported match arm shape");';
				continue;
			}

			$armExpr = $this->renderExpr($arm->children['expr'] ?? null, $namespacePhp);
			$condNode = $arm->children['cond'] ?? null;
			$conditions = $this->extractMatchConditions($condNode);
			if ($conditions === []) {
				$hasDefaultArm = true;
				$parts[] = 'return ' . $armExpr . ';';
				continue;
			}

			$checks = [];
			foreach ($conditions as $condition) {
				$checks[] = 'static_cast<bool>(::scpp::php::identical(' . $subjectName . ', ' . $this->renderExpr($condition, $namespacePhp) . '))';
			}
			$parts[] = 'if (' . implode(' || ', $checks) . ') {';
			$parts[] = $this->indent(1) . 'return ' . $armExpr . ';';
			$parts[] = '}';
		}

		if (!$hasDefaultArm) {
			$parts[] = 'throw std::runtime_error("Unhandled match expression");';
		}

		$parts[] = '}())';
		return implode(' ', $parts);
	}

	/** @return list<mixed> */
	private function extractMatchConditions(mixed $condNode): array
	{
		if ($condNode === null) {
			return [];
		}

		if (is_object($condNode) && isset($condNode->children) && is_array($condNode->children)) {
			if (($condNode->kind ?? null) === AstKind::ARG_LIST || array_is_list($condNode->children)) {
				return array_values($condNode->children);
			}
		}

		return [$condNode];
	}

	/**

	 * Turns first assignment of a local into a declaration+assignment when the rules allow it.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function tryRenderDeclarationAssignChain(mixed $varNode, mixed $exprNode, ?string $typed, ?string $namespacePhp): ?array
	{
		$leftName = $this->extractSimpleVarName($varNode);
		if ($leftName === null || !is_object($exprNode) || (($exprNode->kind ?? null) !== AstKind::ASSIGN)) {
			return null;
		}
		$rightVarNode = $exprNode->children['var'] ?? null;
		$rightExprNode = $exprNode->children['expr'] ?? null;
		$rightName = $this->extractSimpleVarName($rightVarNode);
		if ($rightName === null || isset($this->declaredLocals[$rightName])) {
			return null;
		}
		$rightExpr = $this->renderExpr($rightExprNode, $namespacePhp);
		$this->declaredLocals[$rightName] = true;
		$this->declaredLocals[$leftName] = true;
		$leftType = $typed !== null ? $this->typeMapper->mapTypedLocalType($typed) : 'auto';
		return [
			'auto ' . $rightName . ' = ' . $rightExpr . ';',
			$leftType . ' ' . $leftName . ' = ' . $rightName . ';',
		];
	}

	/**
	 * Resolve strict local wrapper shortcuts such as the bare `value` wrapper on `new Box()`.
	 *
	 * @return array{0:string,1:?string}
	 */
	private function resolveTypedLocalTypeForAssignment(string $typedLocalType, string $statementKind, mixed $exprNode, int $line): array
	{
		if ($this->typeMapper->hasInvalidNestedWrapperType($typedLocalType)) {
			return [$typedLocalType, 'Invalid nested wrapper type at line ' . $line . ': ' . $typedLocalType . ' is not allowed.'];
		}

		if (!$this->typeMapper->isBareObjectWrapperShortcut($typedLocalType)) {
			return [$typedLocalType, null];
		}

		if ($statementKind !== 'assign') {
			return [$typedLocalType, 'Bare wrapper local type /** ' . $typedLocalType . ' */ requires a direct assignment from new ClassName(...) at line ' . $line . '.'];
		}

		$className = $this->extractDirectConstructedClassTypeName($exprNode);
		if ($className === null) {
			return [$typedLocalType, 'Bare wrapper local type /** ' . $typedLocalType . ' */ requires a direct assignment from new ClassName(...) at line ' . $line . '.'];
		}

		try {
			return [$this->typeMapper->specializeBareObjectWrapperShortcut($typedLocalType, $className), null];
		} catch (\InvalidArgumentException $exception) {
			return [$typedLocalType, $exception->getMessage() . ' at line ' . $line . '.'];
		}
	}

	private function extractDirectConstructedClassTypeName(mixed $exprNode): ?string
	{
		if (!is_object($exprNode) || (($exprNode->kind ?? null) !== AstKind::NEW)) {
			return null;
		}

		$classNode = $exprNode->children['class'] ?? null;
		if (!is_object($classNode)) {
			return null;
		}

		$name = trim((string) ($classNode->children['name'] ?? ''));
		if ($name === '') {
			return null;
		}

		$lowered = strtolower(ltrim($name, '\\'));
		if (in_array($lowered, ['self', 'parent', 'static'], true)) {
			return null;
		}

		return ltrim($name, '\\');
	}

	private function validateTypedLocalAssignment(string $typedLocalType, string $statementKind, mixed $exprNode, int $line): ?string
	{
		if (!$this->typeMapper->isRefLocalType($typedLocalType)) {
			return null;
		}

		if ($statementKind !== 'assign_ref') {
			return 'ref typed locals must be initialized via reference assignment at line ' . $line . '.';
		}

		return null;
	}

	private function normalizeStoredLocalType(string $typedLocalType): string
	{
		if ($this->typeMapper->isRefLocalType($typedLocalType)) {
			return $this->typeMapper->mapDeclaredType($this->typeMapper->unwrapRefLocalType($typedLocalType));
		}

		return $typedLocalType;
	}

	private function isEmptyPositionalArrayLiteral(mixed $expr): bool
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::ARRAY)) {
			return false;
		}

		$children = $expr->children ?? null;
		return is_array($children) && $children === [];
	}

	private function mapTypedVectorLocalType(string $typedLocalType): ?string
	{
		if (!$this->typeMapper->isVectorType($typedLocalType)) {
			return null;
		}

		return $this->typeMapper->mapTypedLocalType($typedLocalType);
	}

	private function renderInitializerExpr(mixed $expr, ?string $typedLocalType, ?string $namespacePhp): string
	{
		if ($typedLocalType !== null && is_object($expr) && (($expr->kind ?? null) === AstKind::NEW)) {
			$wrapperValidationError = $this->validateTypedWrapperInitializerFromNew($typedLocalType, $expr, $namespacePhp);
			if ($wrapperValidationError !== null) {
				$this->errors[] = $wrapperValidationError;
				return '/* error: ' . $wrapperValidationError . ' */';
			}

			$wrapperInit = $this->renderTypedWrapperInitializerFromNew($typedLocalType, $expr, $namespacePhp);
			if ($wrapperInit !== null) {
				return $wrapperInit;
			}
		}

		if (is_object($expr) && (($expr->kind ?? null) === AstKind::ARRAY)) {
			return $this->renderArrayLiteral($expr, $namespacePhp, $typedLocalType);
		}

		return $this->renderExpr($expr, $namespacePhp);
	}


	private function validateTypedWrapperInitializerFromNew(string $typedLocalType, object $expr, ?string $namespacePhp): ?string
	{
		$declaredInnerType = $this->extractWrappedObjectInnerType($typedLocalType);
		if ($declaredInnerType === null) {
			return null;
		}

		$constructedClassName = $this->extractDirectConstructedClassTypeName($expr);
		if ($constructedClassName === null) {
			return null;
		}

		$declaredClass = $this->typeMapper->mapClassName($declaredInnerType);
		$constructedClass = $this->renderClassName($expr->children['class'] ?? null, $namespacePhp);
		if ($declaredClass === $constructedClass) {
			return null;
		}

		return 'Type mismatch for wrapper-typed local at line ' . (int) ($expr->lineno ?? 0) . ': declared ' . $typedLocalType . ' but assigned new ' . $constructedClassName . '().';
	}

	private function extractWrappedObjectInnerType(string $typedLocalType): ?string
	{
		$normalized = trim($typedLocalType);
		if ($this->typeMapper->isInlineValueType($normalized)) {
			return $this->typeMapper->unwrapInlineValueType($normalized);
		}

		if (preg_match('/^(?:shared|unique)\s*<\s*(.+)\s*>$/', $normalized, $matches) === 1) {
			return trim($matches[1]);
		}

		return null;
	}
	private function renderTypedWrapperInitializerFromNew(string $typedLocalType, object $expr, ?string $namespacePhp): ?string
	{
		$args = $this->renderArgs($expr->children['args']->children ?? [], $namespacePhp);

		if ($this->typeMapper->isInlineValueType($typedLocalType)) {
			$innerType = $this->typeMapper->unwrapInlineValueType($typedLocalType);
			return 'value<' . $innerType . '>(' . $args . ')';
		}

		if (preg_match('/^unique\s*<\s*(.+)\s*>$/', trim($typedLocalType), $matches) === 1) {
			return '::scpp::unique<' . trim($matches[1]) . '>(' . $args . ')';
		}

		return null;
	}

	private function renderArrayLiteral(mixed $expr, ?string $namespacePhp, ?string $typedLocalType = null): string
	{
		$elements = is_object($expr) && isset($expr->children) && is_array($expr->children)
			? array_values($expr->children)
			: [];

		if ($elements === []) {
			$mappedVectorType = $typedLocalType !== null ? $this->mapTypedVectorLocalType($typedLocalType) : null;
			if ($mappedVectorType !== null) {
				return $mappedVectorType . '{}';
			}

			$this->errors[] = 'Empty array literals are not supported yet because the element type cannot be inferred at line ' . (int) ($expr->lineno ?? 0) . '.';
			return '/* unsupported-empty-array-literal */';
		}

		$values = [];
		foreach ($elements as $element) {
			if (!is_object($element) || (($element->kind ?? null) !== AstKind::ARRAY_ELEM)) {
				$this->errors[] = 'Unsupported array literal element shape at line ' . (int) ($expr->lineno ?? 0) . '.';
				return '/* unsupported-array-literal */';
			}

			$key = $element->children['key'] ?? null;
			if ($key !== null) {
				$this->errors[] = 'Keyed array literals are not supported yet at line ' . (int) ($element->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-keyed-array-literal */';
			}

			$valueNode = $element->children['value'] ?? null;
			if ($valueNode === null) {
				$this->errors[] = 'Array unpack and empty array elements are not supported yet at line ' . (int) ($element->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-array-element */';
			}

			$values[] = $this->renderExpr($valueNode, $namespacePhp);
		}

		$elementType = 'decltype(' . $values[0] . ')';
		return '::scpp::vector_t<' . $elementType . '>{' . implode(', ', $values) . '}';
	}

	/**

	 * Renders one expression node from php-ast into the current Simple C++ expression subset.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderExpr(mixed $expr, ?string $namespacePhp): string
	{
		if (is_int($expr)) {
			return 'static_cast<int_t>(' . $expr . ')';
		}
		if (is_float($expr)) {
			return 'static_cast<float_t>(' . $expr . ')';
		}
		if (is_string($expr)) {
			return 'string_t(' . json_encode($expr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ')';
		}
		if (!is_object($expr)) {
			return '/* unsupported-expr */';
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::ARRAY) {
			return $this->renderArrayLiteral($expr, $namespacePhp);
		}
		if ($kind === AstKind::VAR) {
			$name = (string) ($expr->children['name'] ?? 'var');
			return $name === 'this' ? 'this' : $name;
		}
		if ($kind === AstKind::CONST) {
			$name = (string) ($expr->children['name']->children['name'] ?? '');
			$flags = (int) ($expr->children['name']->flags ?? 0);
			return match (strtolower(ltrim($name, '\\'))) {
				'true' => 'static_cast<bool_t>(true)',
				'false' => 'static_cast<bool_t>(false)',
				'null' => 'null',
				default => $this->renderConstantName($name, $flags, $namespacePhp),
			};
		}
		if ($kind === AstKind::BINARY_OP) {
			$leftNode = $expr->children['left'] ?? null;
			$rightNode = $expr->children['right'] ?? null;
			$left = $this->renderExpr($leftNode, $namespacePhp);
			$right = $this->renderExpr($rightNode, $namespacePhp);
			$flags = (int) ($expr->flags ?? 0);

			return match ($flags) {
				AstKind::PLUS => '(' . $left . ' + ' . $right . ')',
				AstKind::MINUS => '(' . $left . ' - ' . $right . ')',
				AstKind::MUL => '(' . $left . ' * ' . $right . ')',
				AstKind::DIV => '(' . $left . ' / ' . $right . ')',
				AstKind::MOD => '(' . $left . ' % ' . $right . ')',
				AstKind::BITWISE_OR => '(' . $left . ' | ' . $right . ')',
				AstKind::BITWISE_AND => '(' . $left . ' & ' . $right . ')',
				AstKind::BITWISE_XOR => '(' . $left . ' ^ ' . $right . ')',
				AstKind::SHIFT_LEFT => '(' . $left . ' << ' . $right . ')',
				AstKind::SHIFT_RIGHT => '(' . $left . ' >> ' . $right . ')',
				AstKind::BINARY_CONCAT => $this->renderStringConcat($leftNode, $rightNode, $namespacePhp),
				AstKind::BINARY_BOOL_AND => '(' . $left . ' && ' . $right . ')',
				AstKind::BINARY_BOOL_OR => '(' . $left . ' || ' . $right . ')',
				AstKind::BINARY_IS_SMALLER => '(' . $left . ' < ' . $right . ')',
				AstKind::BINARY_IS_SMALLER_OR_EQUAL => '(' . $left . ' <= ' . $right . ')',
				AstKind::BINARY_IS_GREATER => '(' . $left . ' > ' . $right . ')',
				AstKind::BINARY_IS_NOT_EQUAL => '(' . $left . ' != ' . $right . ')',
				AstKind::BINARY_IS_EQUAL => '(' . $left . ' == ' . $right . ')',
				AstKind::BINARY_IS_IDENTICAL => '::scpp::php::identical(' . $left . ', ' . $right . ')',
				AstKind::BINARY_IS_NOT_IDENTICAL => '::scpp::php::not_identical(' . $left . ', ' . $right . ')',
				257 => '(' . $left . ' >= ' . $right . ')',
				AstKind::BINARY_COALESCE => $this->renderCoalesceExpr($leftNode, $rightNode, $namespacePhp),
				default => '/* unsupported-binary-op-' . $flags . ' */',
			};
		}
		if ($kind === AstKind::CAST) {
			$inner = $this->renderExpr($expr->children['expr'] ?? null, $namespacePhp);
			$flags = (int) ($expr->flags ?? 0);
			return match ($flags) {
				AstKind::TYPE_STRING => 'cast<string_t>(' . $inner . ')',
				AstKind::TYPE_LONG => 'static_cast<int_t>(' . $inner . ')',
				AstKind::TYPE_DOUBLE => 'static_cast<float_t>(' . $inner . ')',
				AstKind::TYPE_BOOL => 'static_cast<bool_t>(' . $inner . ')',
				default => '/* unsupported-cast */',
			};
		}
		if ($kind === AstKind::ARRAY) {
			return $this->renderArrayLiteral($expr, $namespacePhp);
		}
		if ($kind === AstKind::ENCAPS_LIST) {
			return $this->renderInterpolatedString($expr, $namespacePhp);
		}
		if ($kind === AstKind::DIM) {
			return $this->renderDimAccess($expr, $namespacePhp);
		}
		if ($kind === AstKind::PROP) {
			$base = $this->renderExpr($expr->children['expr'] ?? null, $namespacePhp);
			$prop = (string) ($expr->children['prop'] ?? 'prop');
			return $base === 'this' ? 'this->' . $prop : $base . '->' . $prop;
		}
		if ($kind === AstKind::STATIC_PROP) {
			return $this->renderStaticPropertyAccess($expr, $namespacePhp);
		}
		if ($kind === AstKind::CLASS_CONST) {
			$class = $this->renderClassName($expr->children['class'] ?? null, $namespacePhp);
			$const = (string) ($expr->children['const'] ?? 'CONST');
			return $class . '::' . $const;
		}
		if ($kind === AstKind::NEW) {
			$class = $this->renderClassName($expr->children['class'] ?? null, $namespacePhp);
			return '::scpp::create<' . $class . '>(' . $this->renderArgs($expr->children['args']->children ?? [], $namespacePhp) . ')';
		}
		if ($kind === AstKind::STATIC_CALL) {
			$classNode = $expr->children['class'] ?? null;
			$method = (string) ($expr->children['method'] ?? '');
			$args = $expr->children['args']->children ?? [];
			$class = is_object($classNode) && ($classNode->kind ?? null) === AstKind::VAR
				? '::scpp::class_t<decltype(' . $this->renderExpr($classNode, $namespacePhp) . ')>'
				: $this->renderClassName($classNode, $namespacePhp);
			$methodDecl = $this->lookupMethodDeclByStaticCall($classNode, $method, $namespacePhp);
			$renderedArgs = $methodDecl !== null ? $this->renderCallArgsForParams($methodDecl->params, $args, $namespacePhp) : $this->renderArgs($args, $namespacePhp);
			return $class . '::' . $method . '(' . $renderedArgs . ')';
		}
		if ($kind === AstKind::AST_ISSET) {
			// In this exporter, multi-argument isset() is already normalized into boolean-op trees.
			// AST_ISSET itself carries exactly one operand in `children['var']`.
			return '::scpp::php::isset(' . $this->renderExpr($expr->children['var'] ?? null, $namespacePhp) . ')';
		}
		if ($kind === AstKind::CALL) {
			$nameExpr = $expr->children['expr'] ?? null;
			$name = $this->renderNameExpr($nameExpr, $namespacePhp);
			$args = $expr->children['args']->children ?? [];
			$functionDecl = $this->lookupFunctionDeclByCall($nameExpr, $namespacePhp);
			$renderedArgs = $functionDecl !== null ? $this->renderCallArgsForParams($functionDecl->params, $args, $namespacePhp) : $this->renderArgs($args, $namespacePhp);
			return $name . '(' . $renderedArgs . ')';
		}
		if ($kind === AstKind::METHOD_CALL) {
			$baseExpr = $expr->children['expr'] ?? null;
			$base = $this->renderExpr($baseExpr, $namespacePhp);
			$method = (string) ($expr->children['method'] ?? 'call');
			$args = $expr->children['args']->children ?? [];
			$methodDecl = is_object($baseExpr) && ($baseExpr->kind ?? null) === AstKind::VAR && ($baseExpr->children['name'] ?? null) === 'this'
				? $this->lookupMethodDeclByCurrentClass($method, $namespacePhp)
				: null;
			$renderedArgs = $methodDecl !== null ? $this->renderCallArgsForParams($methodDecl->params, $args, $namespacePhp) : $this->renderArgs($args, $namespacePhp);
			return $base . '->' . $method . '(' . $renderedArgs . ')';
		}
		if ($kind === AstKind::ASSIGN) {
			return $this->renderAssignmentExpr($expr->children['var'] ?? null, $expr->children['expr'] ?? null, $namespacePhp);
		}
		if ($kind === AstKind::UNARY_OP) {
			$inner = $this->renderExpr($expr->children['expr'] ?? null, $namespacePhp);
			$flags = (int) ($expr->flags ?? 0);
			return match ($flags) {
				AstKind::UNARY_BOOL_NOT => '(!' . $inner . ')',
				AstKind::UNARY_BITWISE_NOT => '(~' . $inner . ')',
				AstKind::UNARY_PLUS => '(+' . $inner . ')',
				AstKind::UNARY_MINUS => '(-' . $inner . ')',
				default => '/* unsupported-unary-op-' . $flags . ' */',
			};
		}
		if ($kind === AstKind::PRE_INC) {
			$target = $this->renderAssignmentTarget($expr->children['var'] ?? null, $namespacePhp);
			return '(++' . $target . ')';
		}
		if ($kind === AstKind::PRE_DEC) {
			$target = $this->renderAssignmentTarget($expr->children['var'] ?? null, $namespacePhp);
			return '(--' . $target . ')';
		}
		if ($kind === AstKind::POST_INC) {
			$target = $this->renderAssignmentTarget($expr->children['var'] ?? null, $namespacePhp);
			return '(' . $target . '++)';
		}
		if ($kind === AstKind::POST_DEC) {
			$target = $this->renderAssignmentTarget($expr->children['var'] ?? null, $namespacePhp);
			return '(' . $target . '--)';
		}
		if ($kind === AstKind::CONDITIONAL) {
			$condNode = $expr->children['cond'] ?? null;
			$trueNode = $expr->children['true'] ?? null;
			$falseNode = $expr->children['false'] ?? null;
			$trueExpr = $trueNode === null ? $this->renderExpr($condNode, $namespacePhp) : $this->renderExpr($trueNode, $namespacePhp);
			return '(' . $this->renderConditionExpr($condNode, $namespacePhp) . ' ? ' . $trueExpr . ' : ' . $this->renderExpr($falseNode, $namespacePhp) . ')';
		}
		if ($kind === AstKind::MATCH) {
			return $this->renderMatchExpr($expr, $namespacePhp);
		}

		return '/* unsupported-expr-kind-' . $kind . ' */';
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

	 * Renders `AST_ENCAPS_LIST` interpolation by concatenating stringified fragments in source order.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderInterpolatedString(mixed $expr, ?string $namespacePhp): string
	{
		$parts = [];
		foreach (($expr->children ?? []) as $child) {
			// Interpolation fragments must reuse the ordinary expression renderer for any
			// non-literal AST node found inside AST_ENCAPS_LIST. The interpolation layer only
			// adds string normalization around the rendered expression subtree.
			$parts[] = $this->renderStringOperand($child, $namespacePhp);
		}

		if ($parts === []) {
			return 'string_t("")';
		}

		return '(' . implode(' + ', $parts) . ')';
	}

	/**

	 * Renders PHP string concatenation through explicit string conversion helpers.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderStringConcat(mixed $leftNode, mixed $rightNode, ?string $namespacePhp): string
	{
		return '(' . $this->renderStringOperand($leftNode, $namespacePhp) . ' + ' . $this->renderStringOperand($rightNode, $namespacePhp) . ')';
	}

	/**

	 * Renders one operand that must participate in string concatenation or interpolation.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderStringOperand(mixed $expr, ?string $namespacePhp): string
	{
		if (is_string($expr)) {
			return 'string_t(' . json_encode($expr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ')';
		}

		if (is_int($expr) || is_float($expr)) {
			return 'cast<string_t>(' . $this->renderExpr($expr, $namespacePhp) . ')';
		}

		if (!is_object($expr)) {
			return 'string_t("")';
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::CONST) {
			$name = strtolower((string) ($expr->children['name']->children['name'] ?? ''));
			if ($name === 'null' || $name === 'true' || $name === 'false') {
				return 'cast<string_t>(' . $this->renderExpr($expr, $namespacePhp) . ')';
			}
		}

		$rendered = $this->renderExpr($expr, $namespacePhp);
		if ($kind === AstKind::ENCAPS_LIST) {
			return $rendered;
		}

		return match ($kind) {
			AstKind::VAR,
			AstKind::DIM,
			AstKind::PROP,
			AstKind::METHOD_CALL,
			AstKind::CALL,
			AstKind::STATIC_CALL,
			AstKind::AST_ISSET,
			AstKind::CAST,
			AstKind::BINARY_OP,
			AstKind::ASSIGN,
			AstKind::CLASS_CONST,
			AstKind::STATIC_PROP => 'cast<string_t>(' . $rendered . ')',
			default => 'cast<string_t>(' . $rendered . ')',
		};
	}

	/**

	 * Renders a PHP variable or symbol-name expression into the generated C++ identifier form.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderNameExpr(mixed $expr, ?string $namespacePhp): string
	{
		if (!is_object($expr)) {
			return 'call';
		}
		if (($expr->kind ?? null) === AstKind::NAME) {
			$name = (string) ($expr->children['name'] ?? 'call');
			$flags = (int) ($expr->flags ?? 0);
			return $this->renderSymbolPath($name, $flags, true);
		}
		return $this->renderExpr($expr, $namespacePhp);
	}

	/**

	 * Renders argument lists for exporter-lowered variadic-style payload nodes.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderVariadicArgs(mixed $expr, ?string $namespacePhp): string
	{
		$out = [];
		if (is_object($expr) && isset($expr->children) && is_array($expr->children)) {
			$children = $expr->children;
			$isList = array_is_list($children);
			if ($isList) {
				foreach ($children as $child) {
					$out[] = $this->renderExpr($child, $namespacePhp);
				}
			}
		}
		if ($out === []) {
			$out[] = $this->renderExpr($expr, $namespacePhp);
		}
		return implode(', ', $out);
	}

	/**

	 * Renders a normal call argument list in source order.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderArgs(array $args, ?string $namespacePhp): string
	{
		$out = [];
		foreach ($args as $arg) {
			$out[] = $this->renderExpr($arg, $namespacePhp);
		}
		return implode(', ', $out);
	}

	/**

	 * Renders a class-name expression with namespace resolution applied.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderClassName(mixed $node, ?string $namespacePhp): string
	{
		if (!is_object($node)) {
			return 'Unknown';
		}
		$name = (string) ($node->children['name'] ?? 'Unknown');
		$lowerName = strtolower(ltrim($name, '\\'));
		if ($lowerName === 'self') {
			return $this->currentClassName ?? '/* unsupported-self */';
		}
		if ($lowerName === 'parent') {
			if ($this->currentParentClass === null) {
				$this->errors[] = 'parent:: is not available without a parent class.';
				return '/* unsupported-parent */';
			}
			return $this->typeMapper->mapClassName($this->currentParentClass);
		}
		if ($lowerName === 'static') {
			$this->errors[] = 'static:: is not supported in the current pass.';
			return '/* unsupported-static */';
		}
		$flags = (int) ($node->flags ?? 0);
		return $this->renderSymbolPath($name, $flags, false);
	}

	private function renderStaticPropertyAccess(mixed $expr, ?string $namespacePhp): string
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::STATIC_PROP)) {
			return '/* unsupported-static-prop */';
		}

		$classNode = $expr->children['class'] ?? null;
		$prop = (string) ($expr->children['prop'] ?? 'prop');
		if (!is_object($classNode)) {
			return '/* unsupported-static-prop */';
		}

		if (($classNode->kind ?? null) === AstKind::VAR) {
			return '::scpp::class_t<decltype(' . $this->renderExpr($classNode, $namespacePhp) . ')>::' . $prop;
		}

		if (($classNode->kind ?? null) !== AstKind::NAME) {
			return '/* unsupported-static-prop */';
		}

		$name = (string) ($classNode->children['name'] ?? '');
		if (strtolower(ltrim($name, '\\')) === 'static') {
			$this->errors[] = 'static::$property is not supported in the current pass.';
			return '/* unsupported-static-late-binding */';
		}

		return $this->renderClassName($classNode, $namespacePhp) . '::' . $prop;
	}

	/**

	 * Renders a constant reference using the constant-resolution rules recorded in the name registry.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderConstantName(string $name, int $flags, ?string $namespacePhp): string
	{
		$trimmed = ltrim($name, '\\');
		if ($trimmed === '') {
			return '/* unsupported-const */';
		}

		if (isset($this->predefinedConstants[$trimmed])) {
			return '::scpp::php::' . str_replace('\\', '::', $trimmed);
		}

		return $this->renderSymbolPath($name, $flags, true);
	}



	private function renderSymbolPath(string $name, int $flags, bool $rootBareIdentifiers): string
	{
		$trimmed = ltrim($name, '\\');
		if ($trimmed === '') {
			return 'Unknown';
		}

		$cpp = str_replace('\\', '::', $trimmed);
		if ($flags === 0 || str_starts_with($name, '\\')) {
			return '::scpp::' . $cpp;
		}

		if ($rootBareIdentifiers && !str_contains($trimmed, '\\')) {
			return $trimmed;
		}

		return $cpp;
	}


	private function renderReturnExpr(mixed $expr, ?string $namespacePhp): string
	{
		$expected = $this->currentReturnType;
		if ($expected !== null && str_ends_with($expected, '&')) {
			if (!$this->isLvalueCapableExpr($expr)) {
				$this->errors[] = 'Reference return requires an lvalue-capable expression.';
				return '/* unsupported-ref-return */';
			}
			return $this->renderLvalueExpr($expr, $namespacePhp);
		}

		$rendered = $this->renderExpr($expr, $namespacePhp);
		if ($expected === null) {
			return $rendered;
		}

		$exprType = $this->inferExprType($expr);
		if ($exprType === 'nullable<' . $expected . '>') {
			return 'cast<' . $expected . '>(' . $rendered . ')';
		}

		return $rendered;
	}

	private function isLvalueCapableExpr(mixed $expr): bool
	{
		if (!is_object($expr)) {
			return false;
		}

		return match ($expr->kind ?? null) {
			AstKind::VAR, AstKind::DIM, AstKind::PROP, AstKind::STATIC_PROP => true,
			default => false,
		};
	}

	private function renderLvalueExpr(mixed $expr, ?string $namespacePhp): string
	{
		if (!is_object($expr)) {
			return $this->renderExpr($expr, $namespacePhp);
		}

		return match ($expr->kind ?? null) {
			AstKind::VAR => $this->renderVar($expr),
			AstKind::DIM => $this->renderDimAccess($expr, $namespacePhp),
			AstKind::PROP, AstKind::STATIC_PROP => $this->renderAssignmentTarget($expr, $namespacePhp),
			default => $this->renderExpr($expr, $namespacePhp),
		};
	}

	private function renderVar(mixed $expr): string
	{
		// Reference-capable variable rendering must stay trivial here: a simple PHP local
		// should lower to the raw C++ identifier so native reference binding and reference
		// returns can attach to the storage location instead of to a copied temporary.
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::VAR)) {
			return '/* unsupported-var */';
		}

		$name = (string) ($expr->children['name'] ?? 'var');
		return $name === 'this' ? 'this' : $name;
	}

	private function inferExprType(mixed $expr): string
	{
		if (is_int($expr)) {
			return 'int_t';
		}
		if (is_float($expr)) {
			return 'float_t';
		}
		if (is_string($expr)) {
			return 'string_t';
		}
		if (!is_object($expr)) {
			return 'auto';
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::VAR) {
			$name = (string) ($expr->children['name'] ?? '');
			$declared = $this->declaredLocalTypes[$name] ?? null;
			if ($declared === null) {
				return 'auto';
			}
			if (str_contains($declared, 'int_t') || str_contains($declared, 'float_t') || str_contains($declared, 'bool_t') || str_contains($declared, 'string_t') || str_starts_with($declared, 'nullable<') || str_starts_with($declared, 'shared_p<') || str_starts_with($declared, 'unique_p<') || str_starts_with($declared, 'weak_p<') || str_starts_with($declared, 'value_p<') || str_starts_with($declared, 'vector_t<')) {
				return $declared;
			}
			return $this->typeMapper->mapDeclaredType($declared);
		}
		if ($kind === AstKind::ARRAY) {
			$elements = array_values($expr->children ?? []);
			if ($elements === []) {
				return 'auto';
			}
			$first = $elements[0]->children['value'] ?? null;
			$elementType = $this->inferExprType($first);
			if ($elementType === 'auto') {
				return 'auto';
			}
			return 'vector_t<' . $elementType . '>';
		}
		if ($kind === AstKind::DIM) {
			$baseType = $this->inferExprType($expr->children['expr'] ?? null);
			if (preg_match('/^vector_t<(.+)>$/', $baseType, $matches) === 1) {
				return $matches[1];
			}
			return 'auto';
		}

		return 'auto';
	}

	private function renderCoalesceExpr(mixed $leftNode, mixed $rightNode, ?string $namespacePhp): string
	{
		$left = $this->renderExpr($leftNode, $namespacePhp);
		$right = $this->renderExpr($rightNode, $namespacePhp);
		$leftType = $this->inferExprType($leftNode);

		if (preg_match('/^nullable<(.+)>$/', $leftType, $matches) === 1) {
			$innerType = $matches[1];
			return '(cast<bool>(::scpp::php::isset(' . $left . ')) ? cast<' . $innerType . '>(' . $left . ') : ' . $right . ')';
		}

		return '(cast<bool>(::scpp::php::isset(' . $left . ')) ? ' . $left . ' : ' . $right . ')';
	}

	private function inferConstantType(mixed $expr, ?string $namespacePhp): string
	{
		if (is_int($expr)) {
			return 'int_t';
		}
		if (is_float($expr)) {
			return 'float_t';
		}
		if (is_string($expr)) {
			return 'string_t';
		}
		if (!is_object($expr)) {
			return 'auto';
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::CONST) {
			$name = strtolower(ltrim((string) ($expr->children['name']->children['name'] ?? ''), '\\'));
			return match ($name) {
				'true', 'false' => 'bool_t',
				'null' => 'null_t',
				default => 'auto',
			};
		}
		if ($kind === AstKind::CAST) {
			$flags = (int) ($expr->flags ?? 0);
			return match ($flags) {
				AstKind::TYPE_STRING => 'string_t',
				AstKind::TYPE_LONG => 'int_t',
				AstKind::TYPE_DOUBLE => 'float_t',
				AstKind::TYPE_BOOL => 'bool_t',
				default => 'auto',
			};
		}
		if ($kind === AstKind::ENCAPS_LIST) {
			return 'string_t';
		}
		if ($kind === AstKind::BINARY_OP) {
			$flags = (int) ($expr->flags ?? 0);
			return match ($flags) {
				AstKind::BINARY_CONCAT => 'string_t',
				AstKind::BINARY_BOOL_AND,
				AstKind::BINARY_BOOL_OR,
				AstKind::BINARY_IS_SMALLER,
				AstKind::BINARY_IS_SMALLER_OR_EQUAL,
				AstKind::BINARY_IS_GREATER,
				AstKind::BINARY_IS_EQUAL,
				AstKind::BINARY_IS_IDENTICAL => 'bool_t',
				default => $this->inferConstantType($expr->children['left'] ?? null, $namespacePhp),
			};
		}

		return 'auto';
	}

	/** @return array<string, bool> */
	private function loadPredefinedConstants(): array
	{
		$result = [];
		foreach (array_keys(get_defined_constants()) as $constantName) {
			$result[(string) $constantName] = true;
		}
		return $result;
	}

	/**

	 * Recognizes null-like expressions that should map directly to runtime sentinels.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function isNullExpr(mixed $expr): bool
	{
		return is_object($expr)
			&& ($expr->kind ?? null) === AstKind::CONST
			&& (($expr->children['name']->children['name'] ?? null) === 'null');
	}

	/**

	 * Returns one tab-based indentation string, matching the project formatting preference.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function indent(int $level): string
	{
		return str_repeat("\t", $level);
	}
}
