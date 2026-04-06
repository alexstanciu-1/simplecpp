<?php
declare(strict_types=1);

namespace Scpp\S2S\Generator;

use Scpp\S2S\Emit\CppFile;
use Scpp\S2S\IR\ArgNormalizationRule;
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
use Scpp\S2S\Support\GenerationException;

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
	/** @var list<string> */
	private array $warnings = [];
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
	/** @var array<string, ClassDecl> */
	private array $classDecls = [];
	/** @var array<string, string> */
	private array $currentParamPassModes = [];
	/** @var list<string> */
	private array $currentScalarRefParamAliasLines = [];
	/** @var list<string> */
	private array $currentParamEntryAliasLines = [];
	/** @var array<string, ArgNormalizationRule> */
	private array $currentArgNormalizationRulesByKey = [];
	private ?string $currentNormalizationCallableName = null;
	private ?string $currentReturnType = null;
	/** @var null|array{flag:string,value:?string,type:?string} */
	private ?array $currentFinallyReturnContext = null;
	/** @var null|array{returnType:?string,paramTypes:list<string>} */
	private ?array $currentExpectedClosureSignature = null;
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
		$this->errors = $file->buildErrors;
		$this->warnings = [];
		$this->localTypeComments = $file->localTypeCommentsByKey;
		$this->declaredLocalTypes = [];
		$this->currentFinallyReturnContext = null;
		$this->tempCounter = 0;
		$this->nameRegistry = NameRegistry::fromPhpFile($file);
		$this->functionDecls = $this->collectFunctionDecls($file);
		$this->methodDecls = $this->collectMethodDecls($file);
		$this->classDecls = $this->collectClassDecls($file);
		$this->typeMapper->setEnumNames($this->collectEnumTypeNames());
		$this->validatePhpFile($file);
		$this->throwIfErrors();

		$baseName = pathinfo($file->path, PATHINFO_FILENAME);
		$header = ['#pragma once', '', '#include <scpp/runtime.hpp>', '#include <type_traits>', '#include <utility>'];
		foreach ($file->prologueIncludes as $includePath) {
			$header[] = '#include "' . $includePath . '"';
		}
		$header[] = '';
		$source = ['#include "' . $baseName . '.hpp"', ''];

		$hasRootNamespaceContent = ($file->rootUses !== [] || $file->constants !== [] || $file->classes !== [] || $file->functions !== [] || $file->rootStatements !== []);
		$rootMainName = $file->rootStatements !== [] ? '__scpp_main' : null;
		if ($hasRootNamespaceContent) {
			$this->emitNamespaceBlock($header, $source, 'scpp', null, $file->rootUses, $file->constants, $file->classes, $file->functions, $file->rootStatements, $rootMainName);
		}

		$namespaceMainTargets = [];
		foreach ($file->namespaces as $namespace) {
			$mainName = $namespace->statements !== [] ? '__scpp_main' : null;
			$namespaceCpp = $this->buildNamespaceCppName($namespace->name);
			$this->emitNamespaceBlock(
				$header,
				$source,
				$namespaceCpp,
				$namespace->name,
				$namespace->uses,
				$namespace->constants,
				$namespace->classes,
				$namespace->functions,
				$namespace->statements,
				$mainName,
			);
			if ($mainName !== null) {
				$namespaceMainTargets[] = $this->qualifyNamespaceSymbol($namespace->name, $mainName) . '()';
			}
		}

		if ($file->rootStatements !== [] && $namespaceMainTargets !== []) {
			$this->fail('Root executable statements and namespace executable statements are not mixed in the current pass.');
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

		$this->throwIfErrors();

		return new CppFile($baseName, $header, $source, $this->errors, $this->warnings);
	}


	private function addError(string $message): void
	{
		$this->errors[] = $message;
	}

	private function fail(string $message): never
	{
		$this->addError($message);
		throw new GenerationException($message);
	}

	private function throwIfErrors(): void
	{
		if ($this->errors === []) {
			return;
		}

		$messages = array_values(array_unique($this->errors));
		throw new GenerationException(implode(PHP_EOL, $messages));
	}

	/** @return array<string, FunctionDecl> */
	private function buildNamespaceCppName(?string $namespacePhp): string
	{
		if ($namespacePhp === null || $namespacePhp === '') {
			return 'scpp';
		}

		return 'scpp::' . str_replace('\\', '::', $namespacePhp);
	}

	private function qualifyNamespaceSymbol(?string $namespacePhp, string $symbol): string
	{
		if ($namespacePhp === null || $namespacePhp === '') {
			return 'scpp::' . $symbol;
		}

		return 'scpp::' . str_replace('\\', '::', $namespacePhp) . '::' . $symbol;
	}

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

	/** @return array<string, ClassDecl> */
	private function collectClassDecls(PhpFile $file): array
	{
		$out = [];

		foreach ($file->classes as $class) {
			$out[$class->name] = $class;
		}

		foreach ($file->namespaces as $namespace) {
			foreach ($namespace->classes as $class) {
				$out[$namespace->name . '\\' . $class->name] = $class;
				$out[$class->name] ??= $class;
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

	/** @return array<string, bool> */
	private function collectEnumTypeNames(): array
	{
		$out = [];
		foreach ($this->classDecls as $name => $class) {
			if (!$class->isEnum) {
				continue;
			}
			$out[ltrim($name, '\\')] = true;
			$out[$class->name] = true;
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
			$out = [];
			foreach ($args as $index => $arg) {
				$param = $params[$index] ?? null;
				$out[] = $param instanceof ParamDecl
					? $this->renderArgForParam($param, $arg, $namespacePhp)
					: $this->renderCallArgExpr($arg, $namespacePhp);
			}
			return implode(', ', $out);
		}

		$fixedCount = count($params) - 1;
		$out = [];
		for ($i = 0; $i < $fixedCount; ++$i) {
			if (array_key_exists($i, $args)) {
				$out[] = $this->renderArgForParam($params[$i], $args[$i], $namespacePhp);
			}
		}

		$variadicType = $lastParam->type !== null
			? $this->typeMapper->mapDeclaredType($lastParam->type)
			: '/* ERROR missing-variadic-element-type */';

		$packedValues = [];
		for ($i = $fixedCount; $i < count($args); ++$i) {
			$packedValues[] = $this->renderCallArgExpr($args[$i], $namespacePhp);
		}

		$out[] = 'vector_t<' . $variadicType . '>{' . implode(', ', $packedValues) . '}';
		return implode(', ', $out);
	}



	private function wrapExprForExpectedType(string $renderedExpr, string $exprType, ?string $expectedType): string
	{
		if ($expectedType === null || $expectedType === '' || $expectedType === 'mixed_t') {
			return $renderedExpr;
		}

		if ($exprType === 'mixed_t') {
			return 'cast<' . $expectedType . '>(' . $renderedExpr . ')';
		}

		if ($exprType === 'nullable<' . $expectedType . '>') {
			return 'cast<' . $expectedType . '>(' . $renderedExpr . ')';
		}

		return $renderedExpr;
	}

	private function renderCallArgExpr(mixed $arg, ?string $namespacePhp): string
	{
		if (is_object($arg) && (($arg->kind ?? null) === AstKind::DIM)) {
			return $this->renderLvalueExpr($arg, $namespacePhp);
		}

		return $this->renderExpr($arg, $namespacePhp);
	}
	private function renderArgForParam(ParamDecl $param, mixed $arg, ?string $namespacePhp): string
	{
		// The S2S generator no longer synthesizes typed scalar reference proxy
		// arguments (int_ref/float_ref/bool_ref/string_ref) at call sites.
		// Keep argument rendering direct and let unsupported by-reference shapes
		// fail later until the reference model is redesigned.
		$rendered = $this->renderCallArgExpr($arg, $namespacePhp);
		if ($param->isReference || $param->type === null) {
			return $rendered;
		}

		if ($this->paramNeedsTemplateNormalization($param)) {
			return $rendered;
		}

		return $this->wrapExprForExpectedType($rendered, $this->inferExprType($arg), $this->typeMapper->mapDeclaredType($param->type));
	}

	private function paramNeedsTemplateNormalization(ParamDecl $param): bool
	{
		if ($param->isReference || $param->isVariadic || count($param->unionTypes) < 2) {
			return false;
		}

		foreach ($param->unionTypes as $unionType) {
			if (!$this->isScalarLikeUnionType($unionType)) {
				return false;
			}
		}

		return true;
	}

	private function isScalarLikeUnionType(string $phpType): bool
	{
		$normalized = trim($phpType);
		if ($normalized === '') {
			return false;
		}

		if (str_starts_with($normalized, '?')) {
			$normalized = substr($normalized, 1);
		}

		return in_array($normalized, ['null', 'bool', 'int', 'float', 'string'], true);
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
			$this->validateReferenceRulesForFunctionLike($function->statements, $function->returnsByReference, 'function ' . $function->name, null);
			$this->validateStatementList($function->statements, null);
		}
		foreach ($file->classes as $class) {
			foreach ($class->properties as $property) {
				$this->validatePropertyDeclaration($class, $property);
			}
			foreach ($class->methods as $method) {
				$this->validateFunctionLikeParameters($method->params, 'method ' . $class->name . '::' . $method->name);
				$this->validateReferenceRulesForFunctionLike($method->statements, $method->returnsByReference, 'method ' . $class->name . '::' . $method->name, null);
				$this->validateStatementList($method->statements, null);
			}
		}
		foreach ($file->namespaces as $namespace) {
			$this->validateStatementList($namespace->statements, $namespace->name);
			foreach ($namespace->functions as $function) {
				$this->validateFunctionLikeParameters($function->params, 'function ' . $function->name);
				$this->validateReferenceRulesForFunctionLike($function->statements, $function->returnsByReference, 'function ' . $function->name, $namespace->name);
				$this->validateStatementList($function->statements, $namespace->name);
			}
			foreach ($namespace->classes as $class) {
				foreach ($class->properties as $property) {
					$this->validatePropertyDeclaration($class, $property);
				}
				foreach ($class->methods as $method) {
					$this->validateFunctionLikeParameters($method->params, 'method ' . $class->name . '::' . $method->name);
					$this->validateReferenceRulesForFunctionLike($method->statements, $method->returnsByReference, 'method ' . $class->name . '::' . $method->name, $namespace->name);
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


	/** @param list<Statement> $statements */
	private function validateReferenceRulesForFunctionLike(array $statements, bool $returnsByReference, string $owner, ?string $namespacePhp): void
	{
		$refBindings = [];
		$refReturnAliasOwners = [];
		if ($returnsByReference) {
			$this->warnings[] = ucfirst($owner) . ' returns by reference. Return-by-reference is not recommended and only partially supported in Simple C++.';
		}
		$this->validateReferenceRulesInStatements($statements, $returnsByReference, $owner, $namespacePhp, false, $refBindings, $refReturnAliasOwners);
		if ($returnsByReference) {
			$returnCount = $this->countReturnStatements($statements);
			if ($returnCount > 1) {
				$this->errors[] = ucfirst($owner) . ' returning by reference must use a single return statement in the current subset.';
			}
		}
	}

	/**
	 * @param list<Statement> $statements
	 * @param array<string, string> $refBindings
	 */
	private function validateReferenceRulesInStatements(array $statements, bool $returnsByReference, string $owner, ?string $namespacePhp, bool $insideControlFlow, array &$refBindings, array &$refReturnAliasOwners): void
	{
		foreach ($statements as $statement) {
			if ($statement->kind === 'assign_ref') {
				$name = $this->extractSimpleVarName($statement->payload['var'] ?? null);
				if ($insideControlFlow) {
					$this->errors[] = 'Conditional or loop-scoped reference binding is not supported for ' . $owner . ' at line ' . $statement->line . '.';
				}
				$expr = $statement->payload['expr'] ?? null;
				if ($name !== null) {
					$refBindings[$name] = $this->classifyReferenceBindingSource($expr);
					$rootVars = $this->extractSimpleVarArgsFromByRefCall($expr, $namespacePhp);
					if ($rootVars !== []) {
						$refReturnAliasOwners[$name] = $rootVars;
					}
				}
			}
			if ($statement->kind === 'assign') {
				$copiedFrom = $this->extractSimpleVarName($statement->payload['expr'] ?? null);
				if ($copiedFrom !== null) {
					foreach ($refReturnAliasOwners as $aliasName => $ownerVars) {
						if (in_array($copiedFrom, $ownerVars, true)) {
							$this->warnings[] = 'Copy-after-alias warning in ' . $owner . ' at line ' . $statement->line . ': $' . $aliasName . ' is bound from a by-reference return rooted in $' . $copiedFrom . ', and copying $' . $copiedFrom . ' may not preserve PHP alias semantics in Simple C++.';
						}
					}
				}
			}
			if ($statement->kind === 'return' && $returnsByReference) {
				$expr = $statement->payload;
				if ($this->isUnsupportedDirectReferenceReturnExpr($expr)) {
					$this->errors[] = ucfirst($owner) . ' returning by reference supports only a simple direct array/property slot chain rooted in a local or parameter variable at line ' . $statement->line . '.';
				} else {
					$local = $this->extractSimpleVarName($expr);
					if ($local !== null) {
						$bindingKind = $refBindings[$local] ?? null;
						if ($bindingKind === 'slot') {
							$this->errors[] = ucfirst($owner) . ' returning by reference does not support returning a local alias created from an array or property slot at line ' . $statement->line . '.';
						}
					}
				}
			}
			if ($statement->kind === 'if') {
				foreach ($statement->payload as $branch) {
					$branchBindings = $refBindings;
					$this->validateReferenceRulesInStatements($branch['stmts'] ?? [], $returnsByReference, $owner, $namespacePhp, true, $branchBindings, $refReturnAliasOwners);
				}
				continue;
			}
			if ($statement->kind === 'while' || $statement->kind === 'do_while' || $statement->kind === 'for' || $statement->kind === 'foreach') {
				$nested = $refBindings;
				$this->validateReferenceRulesInStatements($statement->payload['stmts'] ?? [], $returnsByReference, $owner, $namespacePhp, true, $nested, $refReturnAliasOwners);
				continue;
			}
			if ($statement->kind === 'switch') {
				foreach (($statement->payload['cases'] ?? []) as $case) {
					$caseBindings = $refBindings;
					$this->validateReferenceRulesInStatements($case['stmts'] ?? [], $returnsByReference, $owner, $namespacePhp, true, $caseBindings);
				}
			}
		}
	}

	/** @param list<Statement> $statements */
	private function countReturnStatements(array $statements): int
	{
		$count = 0;
		foreach ($statements as $statement) {
			if ($statement->kind === 'return') {
				++$count;
				continue;
			}
			if ($statement->kind === 'if') {
				foreach ($statement->payload as $branch) {
					$count += $this->countReturnStatements($branch['stmts'] ?? []);
				}
				continue;
			}
			if ($statement->kind === 'while' || $statement->kind === 'do_while' || $statement->kind === 'for' || $statement->kind === 'foreach') {
				$count += $this->countReturnStatements($statement->payload['stmts'] ?? []);
				continue;
			}
			if ($statement->kind === 'switch') {
				foreach (($statement->payload['cases'] ?? []) as $case) {
					$count += $this->countReturnStatements($case['stmts'] ?? []);
				}
			}
		}
		return $count;
	}

	private function classifyReferenceBindingSource(mixed $expr): string
	{
		if ($this->containsReferenceSlotExpr($expr)) {
			return 'slot';
		}
		return 'other';
	}

	/** @return list<string> */
	private function extractSimpleVarArgsFromByRefCall(mixed $expr, ?string $namespacePhp): array
	{
		if (!is_object($expr)) {
			return [];
		}

		$kind = $expr->kind ?? null;
		if ($kind !== AstKind::CALL) {
			return [];
		}

		$decl = $this->lookupFunctionDeclByCall($expr->children['expr'] ?? null, $namespacePhp);
		if ($decl === null || !$decl->returnsByReference) {
			return [];
		}

		$args = $expr->children['args']->children ?? [];
		$out = [];
		foreach ($args as $arg) {
			$name = $this->extractSimpleVarName($arg);
			if ($name !== null) {
				$out[] = $name;
			}
		}

		return array_values(array_unique($out));
	}

	private function isUnsupportedDirectReferenceReturnExpr(mixed $expr): bool
	{
		return $this->containsReferenceSlotExpr($expr) && !$this->isSimpleDirectReferenceReturnExpr($expr);
	}

	private function containsReferenceSlotExpr(mixed $expr): bool
	{
		if (!is_object($expr)) {
			return false;
		}
		$kind = $expr->kind ?? null;
		if (in_array($kind, [AstKind::DIM, AstKind::PROP, AstKind::NULLSAFE_PROP, AstKind::STATIC_PROP], true)) {
			return true;
		}
		foreach ($this->childNodesOf($expr) as $child) {
			if ($this->containsReferenceSlotExpr($child)) {
				return true;
			}
		}
		return false;
	}

	private function isSimpleDirectReferenceReturnExpr(mixed $expr): bool
	{
		if (!is_object($expr)) {
			return false;
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::DIM || $kind === AstKind::PROP) {
			$baseExpr = $expr->children['expr'] ?? null;
			if ($this->extractSimpleVarName($baseExpr) !== null) {
				return true;
			}
			return $this->isSimpleDirectReferenceReturnExpr($baseExpr);
		}

		return false;
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
			if ($statement->kind === 'include_or_eval') {
				$this->errors[] = 'Simple C++ supports require_once only as a static compile-time include with a literal path in the file prologue at line ' . $statement->line . '.';
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
		$source[] = $this->indent(1) . 'using namespace ::scpp;';
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


	private function enumStorageType(ClassDecl $class): string
	{
		$backingType = $class->enumBackingType !== null ? strtolower($class->enumBackingType) : null;
		if ($backingType !== null && $backingType !== 'int') {
			throw new \RuntimeException('Only unit enums and int-backed enums are supported in the current enum lowering');
		}

		$minValue = 0;
		$maxValue = max(0, count($class->enumCases) - 1);
		if ($backingType === 'int') {
			$minValue = 0;
			$maxValue = 0;
			foreach ($class->enumCases as $case) {
				$value = $this->enumCaseIntValue($case);
				$minValue = min($minValue, $value);
				$maxValue = max($maxValue, $value);
			}
		}

		if ($minValue >= 0) {
			if ($maxValue <= 0xFF) {
				return 'std::uint8_t';
			}
			if ($maxValue <= 0xFFFF) {
				return 'std::uint16_t';
			}
			if ($maxValue <= 0xFFFFFFFF) {
				return 'std::uint32_t';
			}
			return 'std::uint64_t';
		}

		if ($minValue >= -0x80 && $maxValue <= 0x7F) {
			return 'std::int8_t';
		}
		if ($minValue >= -0x8000 && $maxValue <= 0x7FFF) {
			return 'std::int16_t';
		}
		if ($minValue >= -0x80000000 && $maxValue <= 0x7FFFFFFF) {
			return 'std::int32_t';
		}
		return 'std::int64_t';
	}

	private function enumCaseIntValue(ConstantDecl $case): int
	{
		$value = $case->value;
		if (is_int($value)) {
			return $value;
		}
		if (is_object($value) && ($value->kind ?? null) === AstKind::UNARY_OP && (($value->flags ?? null) === AstKind::UNARY_MINUS)) {
			$inner = $value->children['expr'] ?? null;
			if (is_int($inner)) {
				return -$inner;
			}
		}
		throw new \RuntimeException('Only literal int-backed enum case values are supported in the current enum lowering');
	}

	private function renderEnumCaseValue(ConstantDecl $case): string
	{
		return (string) $this->enumCaseIntValue($case);
	}

	private function cppIdentifier(string $name): string
	{
		static $reserved = [
			'alignas' => true, 'alignof' => true, 'and' => true, 'and_eq' => true, 'asm' => true, 'auto' => true,
			'bitand' => true, 'bitor' => true, 'bool' => true, 'break' => true, 'case' => true, 'catch' => true,
			'char' => true, 'char8_t' => true, 'char16_t' => true, 'char32_t' => true, 'class' => true,
			'compl' => true, 'concept' => true, 'const' => true, 'consteval' => true, 'constexpr' => true,
			'constinit' => true, 'const_cast' => true, 'continue' => true, 'co_await' => true, 'co_return' => true,
			'co_yield' => true, 'decltype' => true, 'default' => true, 'delete' => true, 'do' => true,
			'double' => true, 'dynamic_cast' => true, 'else' => true, 'enum' => true, 'explicit' => true,
			'export' => true, 'extern' => true, 'false' => true, 'float' => true, 'for' => true, 'friend' => true,
			'goto' => true, 'if' => true, 'inline' => true, 'int' => true, 'long' => true, 'mutable' => true,
			'namespace' => true, 'new' => true, 'noexcept' => true, 'not' => true, 'not_eq' => true,
			'nullptr' => true, 'operator' => true, 'or' => true, 'or_eq' => true, 'private' => true,
			'protected' => true, 'public' => true, 'register' => true, 'reinterpret_cast' => true,
			'requires' => true, 'return' => true, 'short' => true, 'signed' => true, 'sizeof' => true,
			'static' => true, 'static_assert' => true, 'static_cast' => true, 'struct' => true, 'switch' => true,
			'template' => true, 'this' => true, 'thread_local' => true, 'throw' => true, 'true' => true,
			'try' => true, 'typedef' => true, 'typeid' => true, 'typename' => true, 'union' => true,
			'unsigned' => true, 'using' => true, 'virtual' => true, 'void' => true, 'volatile' => true,
			'wchar_t' => true, 'while' => true, 'xor' => true, 'xor_eq' => true,
		];

		return isset($reserved[$name]) ? $name . '_' : $name;
	}

	private function emitEnumClass(array &$header, ClassDecl $class): void
	{
		if ($class->parentClass !== null || $class->interfaces !== [] || $class->properties !== [] || $class->constants !== [] || $class->methods !== []) {
			throw new \RuntimeException('Only simple enums with cases only are supported in the current enum lowering');
		}
		if ($class->enumBackingType === null) {
			foreach ($class->enumCases as $case) {
				if ($case->value !== null) {
					throw new \RuntimeException('Unit enums must not declare backed values in the current enum lowering');
				}
			}
		}
		if ($class->enumCases === []) {
			throw new \RuntimeException('Enums must declare at least one case in the current enum lowering');
		}
		$storage = $this->enumStorageType($class);
		$header[] = 'enum class ' . $class->name . ' : ' . $storage . ' {';
		foreach ($class->enumCases as $index => $case) {
			$suffix = $index + 1 < count($class->enumCases) ? ',' : '';
			$line = $this->indent(1) . $this->cppIdentifier($case->name);
			if ($class->enumBackingType !== null) {
				$line .= ' = ' . $this->renderEnumCaseValue($case);
			}
			$header[] = $line . $suffix;
		}
		$header[] = '};';
		$header[] = '';
	}

	private function emitClass(array &$header, array &$source, ClassDecl $class, ?string $namespacePhp): void
	{
		if ($class->isEnum) {
			$this->emitEnumClass($header, $class);
			return;
		}
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
			$line = $type . ' ' . $this->cppIdentifier($property->name);
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
			$header[] = $this->indent(1) . 'static inline const auto ' . $this->cppIdentifier($constant->name) . ' = ' . $this->renderExpr($constant->value, $namespacePhp) . ';';
		}
		foreach ($class->methods as $method) {
			if ($this->methodNeedsNormalizedTemplate($method)) {
				foreach ($this->renderInlineTemplateMethodArtifacts($class, $method, $namespacePhp) as $line) {
					$header[] = $this->indent(1) . $line;
				}
				continue;
			}
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
			$source[] = $type . ' ' . $class->name . '::' . $this->cppIdentifier($property->name) . ' = ' . ($default ?? ($type . '{}')) . ';';
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
				if ($this->methodIsAbstract($method, $class) || $this->methodNeedsNormalizedTemplate($method)) {
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

	/**
	 * @param list<ParamDecl> $params
	 * @param list<Statement> $statements
	 * @return array<string, string>
	 */
	private function analyzeParamPassModes(array $params, array $statements): array
	{
		$modes = [];
		$tracked = [];
		foreach ($params as $param) {
			if ($param->isReference || $param->isVariadic || $param->type === null) {
				continue;
			}

			$mapped = $this->typeMapper->mapDeclaredType($param->type);
			if ($mapped !== 'string_t' && $mapped !== 'mixed_t' && !str_starts_with($mapped, 'vector_t<')) {
				continue;
			}

			$modes[$param->name] = 'readonly';
			$tracked[$param->name] = true;
		}

		if ($tracked === []) {
			return $modes;
		}

		$this->markOwnedLocalParamsFromStatements($tracked, $modes, $statements);
		return $modes;
	}

	/**
	 * @param array<string, bool> $tracked
	 * @param array<string, string> $modes
	 * @param list<Statement> $statements
	 */
	private function markOwnedLocalParamsFromStatements(array $tracked, array &$modes, array $statements): void
	{
		foreach ($statements as $statement) {
			if (!$statement instanceof Statement) {
				continue;
			}

			if ($statement->kind === 'assign' || $statement->kind === 'assign_ref' || $statement->kind === 'assign_op') {
				$targetRoot = $this->extractAssignmentRootVarName($statement->payload['var'] ?? null);
				if ($targetRoot !== null && isset($tracked[$targetRoot])) {
					$modes[$targetRoot] = 'owned_local';
				}
				continue;
			}

			if ($statement->kind === 'unset') {
				$targetRoot = $this->extractAssignmentRootVarName($statement->payload);
				if ($targetRoot !== null && isset($tracked[$targetRoot])) {
					$modes[$targetRoot] = 'owned_local';
				}
				continue;
			}

			if ($statement->kind === 'expr') {
				$targetRoot = $this->extractMutationExprRootVarName($statement->payload);
				if ($targetRoot !== null && isset($tracked[$targetRoot])) {
					$modes[$targetRoot] = 'owned_local';
				}
				continue;
			}

			if ($statement->kind === 'foreach') {
				if ((bool) ($statement->payload['by_ref'] ?? false)) {
					$sourceRoot = $this->extractSimpleVarName($statement->payload['expr'] ?? null);
					if ($sourceRoot !== null && isset($tracked[$sourceRoot])) {
						$modes[$sourceRoot] = 'owned_local';
					}
				}
				$this->markOwnedLocalParamsFromStatements($tracked, $modes, $statement->payload['stmts'] ?? []);
				continue;
			}

			if ($statement->kind === 'if') {
				foreach ($statement->payload as $branch) {
					$this->markOwnedLocalParamsFromStatements($tracked, $modes, $branch['stmts'] ?? []);
				}
				continue;
			}

			if ($statement->kind === 'while' || $statement->kind === 'do_while' || $statement->kind === 'for') {
				$this->markOwnedLocalParamsFromStatements($tracked, $modes, $statement->payload['stmts'] ?? []);
				continue;
			}

			if ($statement->kind === 'switch') {
				foreach (($statement->payload['cases'] ?? []) as $case) {
					$this->markOwnedLocalParamsFromStatements($tracked, $modes, $case['stmts'] ?? []);
				}
			}
		}
	}

	private function extractAssignmentRootVarName(mixed $target): ?string
	{
		if (!is_object($target)) {
			return null;
		}

		$kind = $target->kind ?? null;
		if ($kind === AstKind::VAR) {
			return $this->extractSimpleVarName($target);
		}

		if ($kind === AstKind::DIM) {
			return $this->extractAssignmentRootVarName($target->children['expr'] ?? null);
		}

		return null;
	}

	private function extractMutationExprRootVarName(mixed $expr): ?string
	{
		if (!is_object($expr)) {
			return null;
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::PRE_INC || $kind === AstKind::PRE_DEC || $kind === AstKind::POST_INC || $kind === AstKind::POST_DEC) {
			return $this->extractAssignmentRootVarName($expr->children['var'] ?? null);
		}

		return null;
	}

	private function emitFunction(array &$header, array &$source, FunctionDecl $function, ?string $namespacePhp): void
	{
		if ($this->functionLikeNeedsNormalizedTemplate($function->params)) {
			foreach ($this->renderFunctionTemplateArtifacts($function, $namespacePhp) as $line) {
				$header[] = $line;
			}
			$header[] = '';
			return;
		}
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
		$paramPassModes = $this->analyzeParamPassModes($method->params, $method->statements);
		if ($method->name === '__construct' && $className !== null) {
			return $className . '(' . $this->renderParams($method->params, true, $namespacePhp, $paramPassModes) . ')';
		}
		if ($method->name === '__destruct' && $className !== null) {
			return '~' . $className . '()';
		}
		$prefix = $method->isStatic ? 'static ' : '';
		if (!$method->isStatic && $classDecl instanceof ClassDecl && ($classDecl->isInterface || $classDecl->parentClass !== null || $classDecl->interfaces !== [])) {
			$prefix .= 'virtual ';
		}
		$returnType = $this->resolveDeclaredReturnType($method->returnType, $method->returnsByReference, 'Method ' . $this->cppIdentifier($method->name));
		$declaration = $prefix . $returnType . ' ' . $this->cppIdentifier($method->name) . '(' . $this->renderParams($method->params, true, $namespacePhp, $paramPassModes) . ')';
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

	private function functionNeedsValueRefOverload(array $params): bool
	{
		foreach ($params as $param) {
			if ($param instanceof ParamDecl && $this->isSupportedScalarValueRefOverloadParam($param)) {
				return true;
			}
		}

		return false;
	}

	private function isSupportedScalarValueRefOverloadParam(ParamDecl $param): bool
	{
		if (!$param->isReference || $param->type === null || $param->isVariadic) {
			return false;
		}

		return in_array($this->typeMapper->mapDeclaredType($param->type), ['int_t', 'float_t', 'bool_t', 'string_t'], true);
	}

	private function mapValueRefAccessorForParam(ParamDecl $param): ?string
	{
		if (!$this->isSupportedScalarValueRefOverloadParam($param)) {
			return null;
		}

		return match ($this->typeMapper->mapDeclaredType($param->type ?? '')) {
			'int_t' => 'as_int_ref',
			'float_t' => 'as_float_ref',
			'bool_t' => 'as_bool_ref',
			'string_t' => 'as_string_ref',
			default => null,
		};
	}

	private function renderValueRefOverloadParams(array $params, bool $includeDefaults, ?string $namespacePhp, array $paramPassModes = []): string
	{
		$out = [];
		foreach ($params as $param) {
			if ($param->isVariadic) {
				$elementType = $param->type !== null ? $this->typeMapper->mapDeclaredType($param->type) : '/* ERROR missing-variadic-element-type */';
				$type = 'const vector_t<' . $elementType . '>&';
			} elseif ($this->isSupportedScalarValueRefOverloadParam($param)) {
				$type = 'mixed_t&';
			} else {
				$type = $param->type !== null ? $this->renderParamTypeForMode($param, $paramPassModes[$param->name] ?? 'readonly') : '/* ERROR missing-parameter-type */';
			}
			$rendered = $type . ' ' . $this->renderParamName($param, $useStorageNames);
			if (!$param->isVariadic && $includeDefaults && $param->default !== null) {
				$rendered .= ' = ' . $this->renderExpr($param->default, $namespacePhp);
			}
			$out[] = $rendered;
		}
		return implode(', ', $out);
	}

	private function renderValueRefOverloadForwardArgs(array $params): string
	{
		$out = [];
		foreach ($params as $param) {
			$accessor = $this->mapValueRefAccessorForParam($param);
			$out[] = $accessor !== null
				? $param->name . '.' . $accessor . '()'
				: $param->name;
		}
		return implode(', ', $out);
	}

	private function renderFunctionValueRefOverloadDeclaration(FunctionDecl $function, ?string $namespacePhp = null): string
	{
		$returnType = $this->resolveDeclaredReturnType($function->returnType, $function->returnsByReference, 'Function ' . $function->name);
		$paramPassModes = $this->analyzeParamPassModes($function->params, $function->statements);
		return $returnType . ' ' . $function->name . '(' . $this->renderValueRefOverloadParams($function->params, true, $namespacePhp, $paramPassModes) . ')';
	}

	private function renderFunctionValueRefOverloadDefinition(FunctionDecl $function, ?string $namespacePhp): string
	{
		$returnType = $this->resolveDeclaredReturnType($function->returnType, $function->returnsByReference, 'Function ' . $function->name);
		$paramPassModes = $this->analyzeParamPassModes($function->params, $function->statements);
		$signature = $returnType . ' ' . $function->name . '(' . $this->renderValueRefOverloadParams($function->params, false, $namespacePhp, $paramPassModes) . ')';
		$forward = $this->renderValueRefOverloadForwardArgs($function->params);
		$body = $returnType === 'void'
			? $this->indent(1) . $function->name . '(' . $forward . ');'
			: $this->indent(1) . 'return ' . $function->name . '(' . $forward . ');';
		return $signature . " {
" . $body . "
}";
	}

	private function renderMethodValueRefOverloadDeclaration(MethodDecl $method, ClassDecl|string|null $classDecl = null, ?string $namespacePhp = null): string
	{
		$paramPassModes = $this->analyzeParamPassModes($method->params, $method->statements);
		$prefix = $method->isStatic ? 'static ' : '';
		$returnType = $this->resolveDeclaredReturnType($method->returnType, $method->returnsByReference, 'Method ' . $this->cppIdentifier($method->name));
		return $prefix . $returnType . ' ' . $this->cppIdentifier($method->name) . '(' . $this->renderValueRefOverloadParams($method->params, true, $namespacePhp, $paramPassModes) . ')';
	}

	private function renderMethodValueRefOverloadDefinition(ClassDecl $class, MethodDecl $method, ?string $namespacePhp): string
	{
		$returnType = $this->resolveDeclaredReturnType($method->returnType, $method->returnsByReference, 'Method ' . $this->cppIdentifier($method->name));
		$paramPassModes = $this->analyzeParamPassModes($method->params, $method->statements);
		$signature = $returnType . ' ' . $class->name . '::' . $this->cppIdentifier($method->name) . '(' . $this->renderValueRefOverloadParams($method->params, false, $namespacePhp, $paramPassModes) . ')';
		$forward = $this->renderValueRefOverloadForwardArgs($method->params);
		$body = $returnType === 'void'
			? $this->indent(1) . $this->cppIdentifier($method->name) . '(' . $forward . ');'
			: $this->indent(1) . 'return ' . $this->cppIdentifier($method->name) . '(' . $forward . ');';
		return $signature . " {
" . $body . "
}";
	}

	private function renderMethodDefinition(ClassDecl $class, MethodDecl $method, ?string $namespacePhp): string
	{
		$this->declaredLocals = [];
		$this->declaredLocalTypes = [];
		$this->currentParamPassModes = $this->analyzeParamPassModes($method->params, $method->statements);
		foreach ($method->params as $param) {
			$this->declaredLocals[$param->name] = true;
			if ($param->type !== null) {
				$this->declaredLocalTypes[$param->name] = $param->type;
			}
		}
		$this->currentArgNormalizationRulesByKey = $this->indexArgNormalizationRules($method->argNormalizationRules);
		$this->currentNormalizationCallableName = $this->methodNeedsNormalizedTemplate($method) ? $method->name : null;
		$this->currentParamEntryAliasLines = $this->buildParamEntryAliasLines($method->params);
		$this->currentScalarRefParamAliasLines = $this->buildScalarRefParamAliasLines($method->params);
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
			$signature = $className . '::' . $className . '(' . $this->renderParams($method->params, false, $namespacePhp, $this->currentParamPassModes, true) . ')' . $initializer;
		} elseif ($method->name === '__destruct') {
			$this->currentReturnType = null;
			$signature = $className . '::~' . $className . '()';
		} else {
			$returnType = $this->resolveDeclaredReturnType($method->returnType, $method->returnsByReference, 'Method ' . $this->cppIdentifier($method->name));
			$this->currentReturnType = $returnType;
			$signature = $returnType . ' ' . $className . '::' . $this->cppIdentifier($method->name) . '(' . $this->renderParams($method->params, false, $namespacePhp, $this->currentParamPassModes, true) . ')';
		}
		$body = $this->renderBody($statements, $namespacePhp);
		$this->currentReturnType = null;
		$this->currentFinallyReturnContext = null;
		$this->currentParamPassModes = [];
		$this->currentScalarRefParamAliasLines = [];
		$this->currentParamEntryAliasLines = [];
		$this->currentArgNormalizationRulesByKey = [];
		$this->currentNormalizationCallableName = null;
		return $signature . " {
" . $body . "
}";
	}


	private function resolveDeclaredReturnType(?string $phpType, bool $explicitRef, string $ownerLabel): string
	{
		if ($explicitRef && $phpType === null) {
			$this->errors[] = $ownerLabel . ' returning by reference requires an explicit declared return type.';
			return '/* unsupported-ref-return-type */';
		}

		return $this->typeMapper->mapReturnType($phpType, $explicitRef);
	}

	/**

	 * Renders a function signature for the generated header.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */


	private function functionLikeNeedsNormalizedTemplate(array $params): bool
	{
		foreach ($params as $param) {
			if ($this->paramNeedsTemplateNormalization($param)) {
				return true;
			}
		}
		return false;
	}

	private function methodNeedsNormalizedTemplate(MethodDecl $method): bool
	{
		if ($method->name === '__construct' || $method->name === '__destruct') {
			return false;
		}
		return $this->functionLikeNeedsNormalizedTemplate($method->params);
	}

	private function renderTemplateLineForParams(array $params): string
	{
		$templateParams = [];
		foreach ($params as $param) {
			if ($this->paramNeedsTemplateNormalization($param)) {
				$templateParams[] = 'typename ' . $this->renderTemplateTypeName($param);
			}
		}
		return 'template <' . implode(', ', $templateParams) . '>';
	}

	private function renderTemplateTypeName(ParamDecl $param): string
	{
		return 'T_' . $param->name;
	}

	private function renderNormalizationHelperName(string $callableName, ParamDecl $param): string
	{
		return '_norm_' . $this->cppIdentifier($callableName) . '__' . $param->name;
	}

	private function renderNormalizationRuleExpression(ArgNormalizationRule $rule, ParamDecl $param, string $sourceAlias): string
	{
		$expression = $rule->expression;
		$expression = preg_replace('/\$' . preg_quote($param->name, '/') . '\b/', $sourceAlias, $expression) ?? $expression;
		$expression = preg_replace('/\b' . preg_quote($param->name, '/') . '\b/', $sourceAlias, $expression) ?? $expression;
		return $expression;
	}

	private function renderNormalizationDirectBranchLines(ParamDecl $param, string $unionType, bool $sourceIsMixed = false): array
	{
		$mappedPrimaryType = $this->typeMapper->mapDeclaredType($param->primaryType ?? $param->type);
		$mappedSourceType = $this->typeMapper->mapDeclaredType($unionType);
		$sourceExpr = '_' . $param->name;
		$rule = $this->lookupArgNormalizationRule($param, $unionType);
		if ($rule === null) {
			if (($param->primaryType ?? $param->type) === $unionType) {
				if ($sourceIsMixed) {
					return ['return cast<' . $mappedPrimaryType . '>(' . $sourceExpr . ');'];
				}
				return ['return ' . $sourceExpr . ';'];
			}
			return ['return cast<' . $mappedPrimaryType . '>(' . $sourceExpr . ');'];
		}
		$expr = $this->renderNormalizationRuleExpression($rule, $param, $param->name);
		return [
			$mappedSourceType . ' ' . $param->name . ' = cast<' . $mappedSourceType . '>(' . $sourceExpr . ');',
			'return ' . $expr . ';',
		];
	}

	private function renderNormalizationMixedBranchLines(ParamDecl $param): array
	{
		$mappedPrimaryType = $this->typeMapper->mapDeclaredType($param->primaryType ?? $param->type);
		$lines = [];
		$first = true;
		foreach ($param->unionTypes as $unionType) {
			$condition = $this->renderUnionSourceKindCheck('_' . $param->name, $unionType);
			if ($condition === null) {
				continue;
			}
			$prefix = $first ? 'if' : 'else if';
			$first = false;
			$lines[] = $prefix . ' (' . $condition . ') {';
			foreach ($this->renderNormalizationDirectBranchLines($param, $unionType, true) as $line) {
				$lines[] = $this->indent(1) . $line;
			}
			$lines[] = '}';
		}
		$lines[] = 'throw std::runtime_error("Unsupported runtime kind for normalized parameter $' . addslashes($param->name) . '");';
		return $lines;
	}

	private function renderNormalizationHelperDefinition(string $callableName, ParamDecl $param, bool $classInline = false): string
	{
		$primaryType = $this->typeMapper->mapDeclaredType($param->primaryType ?? $param->type);
		$templateType = $this->renderTemplateTypeName($param);
		$helperName = $this->renderNormalizationHelperName($callableName, $param);
		$prefix = $classInline ? 'static ' : '';
		$lines = [];
		$lines[] = 'template <typename ' . $templateType . '>';
		$lines[] = $prefix . $primaryType . ' ' . $helperName . '(' . $templateType . '&& _' . $param->name . ') {';
		$lines[] = $this->indent(1) . 'using _norm_arg_t = std::remove_cv_t<std::remove_reference_t<' . $templateType . '>>;';
		$first = true;
		foreach ($param->unionTypes as $unionType) {
			$mappedSourceType = $this->typeMapper->mapDeclaredType($unionType);
			$prefixCond = $first ? 'if constexpr' : 'else if constexpr';
			$first = false;
			$lines[] = $this->indent(1) . $prefixCond . ' (std::is_same_v<_norm_arg_t, ' . $mappedSourceType . '>) {';
			foreach ($this->renderNormalizationDirectBranchLines($param, $unionType, true) as $line) {
				$lines[] = $this->indent(2) . $line;
			}
			$lines[] = $this->indent(1) . '}';
		}
		$lines[] = $this->indent(1) . 'else if constexpr (std::is_same_v<_norm_arg_t, mixed_t>) {';
		foreach ($this->renderNormalizationMixedBranchLines($param) as $line) {
			$lines[] = $this->indent(2) . $line;
		}
		$lines[] = $this->indent(1) . '} else {';
		$lines[] = $this->indent(2) . 'static_assert(!std::is_same_v<_norm_arg_t, _norm_arg_t>, "Unsupported type for normalized parameter");';
		$lines[] = $this->indent(1) . '}';
		$lines[] = '}';
		return implode("\n", $lines);
	}

	private function renderFunctionTemplateArtifacts(FunctionDecl $function, ?string $namespacePhp): array
	{
		$prevRules = $this->currentArgNormalizationRulesByKey;
		$prevCallable = $this->currentNormalizationCallableName;
		$this->currentArgNormalizationRulesByKey = $this->indexArgNormalizationRules($function->argNormalizationRules);
		$this->currentNormalizationCallableName = $function->name;

		$lines = [];
		foreach ($function->params as $param) {
			if ($this->paramNeedsTemplateNormalization($param)) {
				$lines[] = $this->renderNormalizationHelperDefinition($function->name, $param, false);
				$lines[] = '';
			}
		}
		$lines[] = $this->renderTemplateLineForParams($function->params);
		$lines[] = $this->renderFunctionDefinition($function, $namespacePhp);

		$this->currentArgNormalizationRulesByKey = $prevRules;
		$this->currentNormalizationCallableName = $prevCallable;
		return $lines;
	}

	private function renderInlineTemplateMethodArtifacts(ClassDecl $class, MethodDecl $method, ?string $namespacePhp): array
	{
		$prevRules = $this->currentArgNormalizationRulesByKey;
		$prevCallable = $this->currentNormalizationCallableName;
		$this->currentArgNormalizationRulesByKey = $this->indexArgNormalizationRules($method->argNormalizationRules);
		$this->currentNormalizationCallableName = $method->name;

		$lines = [];
		foreach ($method->params as $param) {
			if ($this->paramNeedsTemplateNormalization($param)) {
				foreach (explode("\n", $this->renderNormalizationHelperDefinition($method->name, $param, true)) as $line) {
					$lines[] = $line;
				}
				$lines[] = '';
			}
		}
		foreach (explode("\n", $this->renderTemplateLineForParams($method->params)) as $line) {
			$lines[] = $line;
		}
		foreach (explode("\n", $this->renderInlineMethodDefinition($class, $method, $namespacePhp)) as $line) {
			$lines[] = $line;
		}

		$this->currentArgNormalizationRulesByKey = $prevRules;
		$this->currentNormalizationCallableName = $prevCallable;
		return $lines;
	}

	private function renderInlineMethodDefinition(ClassDecl $class, MethodDecl $method, ?string $namespacePhp): string
	{
		$this->declaredLocals = [];
		$this->declaredLocalTypes = [];
		$this->currentParamPassModes = $this->analyzeParamPassModes($method->params, $method->statements);
		foreach ($method->params as $param) {
			$this->declaredLocals[$param->name] = true;
			if ($param->type !== null) {
				$this->declaredLocalTypes[$param->name] = $param->type;
			}
		}
		$this->currentArgNormalizationRulesByKey = $this->indexArgNormalizationRules($method->argNormalizationRules);
		$this->currentNormalizationCallableName = $method->name;
		$this->currentParamEntryAliasLines = $this->buildParamEntryAliasLines($method->params);
		$this->currentScalarRefParamAliasLines = $this->buildScalarRefParamAliasLines($method->params);
		$returnType = $this->resolveDeclaredReturnType($method->returnType, $method->returnsByReference, 'Method ' . $this->cppIdentifier($method->name));
		$this->currentReturnType = $returnType;
		$prefix = $method->isStatic ? 'static ' : '';
		$signature = $prefix . $returnType . ' ' . $this->cppIdentifier($method->name) . '(' . $this->renderParams($method->params, false, $namespacePhp, $this->currentParamPassModes, true) . ')';
		$body = $this->renderBody($method->statements, $namespacePhp);
		$this->currentReturnType = null;
		$this->currentFinallyReturnContext = null;
		$this->currentParamPassModes = [];
		$this->currentScalarRefParamAliasLines = [];
		$this->currentParamEntryAliasLines = [];
		$this->currentArgNormalizationRulesByKey = [];
		$this->currentNormalizationCallableName = null;
		return $signature . " {\n" . $body . "\n}";
	}

	private function renderFunctionDeclaration(FunctionDecl $function, ?string $namespacePhp = null): string
	{
		$returnType = $this->resolveDeclaredReturnType($function->returnType, $function->returnsByReference, 'Function ' . $function->name);
		$paramPassModes = $this->analyzeParamPassModes($function->params, $function->statements);
		return $returnType . ' ' . $function->name . '(' . $this->renderParams($function->params, true, $namespacePhp, $paramPassModes) . ')';
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
		$this->currentParamPassModes = $this->analyzeParamPassModes($function->params, $function->statements);
		foreach ($function->params as $param) {
			$this->declaredLocals[$param->name] = true;
			if ($param->type !== null) {
				$this->declaredLocalTypes[$param->name] = $param->type;
			}
		}
		$this->currentArgNormalizationRulesByKey = $this->indexArgNormalizationRules($function->argNormalizationRules);
		$this->currentNormalizationCallableName = $this->functionLikeNeedsNormalizedTemplate($function->params) ? $function->name : null;
		$this->currentParamEntryAliasLines = $this->buildParamEntryAliasLines($function->params);
		$this->currentScalarRefParamAliasLines = $this->buildScalarRefParamAliasLines($function->params);
		$returnType = $this->resolveDeclaredReturnType($function->returnType, $function->returnsByReference, 'Function ' . $function->name);
		$this->currentReturnType = $returnType;
		$signature = $returnType . ' ' . $function->name . '(' . $this->renderParams($function->params, false, $namespacePhp, $this->currentParamPassModes, true) . ')';
		$body = $this->renderBody($function->statements, $namespacePhp);
		$this->currentReturnType = null;
		$this->currentFinallyReturnContext = null;
		$this->currentParamPassModes = [];
		$this->currentScalarRefParamAliasLines = [];
		$this->currentParamEntryAliasLines = [];
		$this->currentArgNormalizationRulesByKey = [];
		$this->currentNormalizationCallableName = null;
		return $signature . " {\n" . $body . "\n}";
	}

	/**

	 * Renders the lowered parameter list, optionally including default expressions when a declaration requires them.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderParams(array $params, bool $includeDefaults, ?string $namespacePhp, array $paramPassModes = [], bool $useStorageNames = false): string
	{
		$out = [];
		foreach ($params as $param) {
			if ($param->isVariadic) {
				$elementType = $param->type !== null ? $this->typeMapper->mapDeclaredType($param->type) : '/* ERROR missing-variadic-element-type */';
				$type = 'const vector_t<' . $elementType . '>&';
			} else {
				$type = $param->type !== null ? $this->renderParamTypeForMode($param, $paramPassModes[$param->name] ?? 'readonly') : '/* ERROR missing-parameter-type */';
			}
			$name = $this->renderParamName($param, $useStorageNames);
			if ($this->paramNeedsTemplateNormalization($param) && !$useStorageNames) {
				$name = '_' . $param->name;
			}
			$rendered = $type . ' ' . $name;
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

	private function renderParamTypeForMode(ParamDecl $param, string $mode): string
	{
		if ($param->type === null) {
			return '/* ERROR missing-parameter-type */';
		}

		if ($this->paramNeedsTemplateNormalization($param)) {
			return $this->renderTemplateTypeName($param) . '&&';
		}

		$mapped = $this->typeMapper->mapDeclaredType($param->type);
		if ($param->isReference) {
			$proxyType = $this->typeMapper->mapReferenceProxyType($param->type);
			if ($proxyType !== null) {
				return $proxyType;
			}
			return $this->typeMapper->mapParamType($param->type, true);
		}

		if ($mode === 'owned_local' && ($mapped === 'string_t' || $mapped === 'mixed_t' || str_starts_with($mapped, 'vector_t<'))) {
			return $mapped;
		}

		return $this->typeMapper->mapParamType($param->type, false);
	}

	private function renderBody(array $statements, ?string $namespacePhp): string
	{
		$lines = array_merge($this->renderCurrentParamEntryAliases(), $this->renderCurrentScalarRefParamAliases(), $this->renderCurrentArrayParamGuards());
		foreach ($this->renderStatementSequence($statements, $namespacePhp) as $line) {
			$lines[] = $this->indent(1) . $line;
		}
		return implode("\n", $lines);
	}

	/** @return list<string> */
	private function renderCurrentParamEntryAliases(): array
	{
		return $this->currentParamEntryAliasLines;
	}

	/**
	 * Emits entry guards for PHP array / ?array parameters lowered to mixed_t.
	 *
	 * @return list<string>
	 */
	private function renderCurrentScalarRefParamAliases(): array
	{
		return $this->currentScalarRefParamAliasLines;
	}

	private function buildScalarRefParamAliasLines(array $params): array
	{
		// Typed scalar by-reference proxy lowering has been disabled in the S2S
		// generator. Do not synthesize entry aliases back from proxy storage.
		return [];
	}

	/** @param list<ParamDecl> $params @return list<string> */
	private function buildParamEntryAliasLines(array $params): array
	{
		$lines = [];
		foreach ($params as $param) {
			$storageName = $this->renderParamStorageName($param);
			if ($this->paramNeedsTemplateNormalization($param)) {
				$primaryType = $this->typeMapper->mapDeclaredType($param->primaryType ?? $param->type);
				$callableName = $this->currentNormalizationCallableName ?? 'callable';
				$lines[] = $primaryType . ' ' . $param->name . ' = ' . $this->renderNormalizationHelperName($callableName, $param) . '(std::forward<' . $this->renderTemplateTypeName($param) . '>(' . $storageName . '));';
				continue;
			}
			if ($storageName === $param->name) {
				continue;
			}
			$binding = $param->isReference ? 'auto&' : 'auto';
			$lines[] = $binding . ' ' . $param->name . ' = ' . $storageName . ';';
		}
		return $lines;
	}

	private function renderParamName(ParamDecl $param, bool $useStorageNames): string
	{
		return $useStorageNames ? $this->renderParamStorageName($param) : $param->name;
	}

	private function renderParamStorageName(ParamDecl $param): string
	{
		if (!$this->paramNeedsGeneratedStorageName($param)) {
			return $param->name;
		}

		return '_' . $param->name;
	}


	/** @param list<ArgNormalizationRule> $rules @return array<string, ArgNormalizationRule> */
	private function indexArgNormalizationRules(array $rules): array
	{
		$indexed = [];
		foreach ($rules as $rule) {
			$indexed[$rule->paramName . '|' . $rule->sourceType] = $rule;
		}
		return $indexed;
	}

	private function lookupArgNormalizationRule(ParamDecl $param, string $sourceType): ?ArgNormalizationRule
	{
		return $this->currentArgNormalizationRulesByKey[$param->name . '|' . $sourceType] ?? null;
	}

	private function paramNeedsGeneratedStorageName(ParamDecl $param): bool
	{
		return $this->paramNeedsTemplateNormalization($param);
	}

	private function renderUnionSourceKindCheck(string $storageName, string $sourceType): ?string
	{
		$normalized = ltrim(trim($sourceType), '?');
		return match ($normalized) {
			'null' => $storageName . '.kind() == mixed_t::kind_t::null_v',
			'bool' => 'static_cast<bool>(' . $storageName . '.is_bool())',
			'int' => 'static_cast<bool>(' . $storageName . '.is_int())',
			'float' => 'static_cast<bool>(' . $storageName . '.is_float())',
			'string' => 'static_cast<bool>(' . $storageName . '.is_string())',
			default => null,
		};
	}

	private function renderForwardedNormalizationExpression(ArgNormalizationRule $rule, ParamDecl $param, string $storageName): string
	{
		$expression = $rule->expression;
		$sourceAlias = '_gen_' . $param->name . '_from_' . (preg_replace('/[^A-Za-z0-9_]+/', '_', $rule->sourceType) ?? 'value');
		$mappedSourceType = $this->typeMapper->mapDeclaredType($rule->sourceType);
		$expression = preg_replace('/\$' . preg_quote($param->name, '/') . '\b/', $sourceAlias, $expression) ?? $expression;
		$expression = preg_replace('/\b' . preg_quote($param->name, '/') . '\b/', $sourceAlias, $expression) ?? $expression;
		return '([&]() -> ' . $this->typeMapper->mapDeclaredType($param->primaryType ?? $param->type) . ' { ' . $mappedSourceType . ' ' . $sourceAlias . ' = cast<' . $mappedSourceType . '>(' . $storageName . '); return ' . $expression . '; })()';
	}

	private function buildMixedCarrierNormalizationLines(ParamDecl $param, string $storageName): array
	{
		$primaryType = $param->primaryType ?? $param->type;
		$mappedPrimaryType = $primaryType !== null ? $this->typeMapper->mapDeclaredType($primaryType) : 'mixed_t';
		$lines = [$mappedPrimaryType . ' ' . $param->name . ';'];
		$firstBranch = true;
		foreach ($param->unionTypes as $unionType) {
			$condition = $this->renderUnionSourceKindCheck($storageName, $unionType);
			if ($condition === null) {
				continue;
			}
			$branchPrefix = $firstBranch ? 'if' : 'else if';
			$firstBranch = false;
			$rule = $this->lookupArgNormalizationRule($param, $unionType);
			$assignmentExpr = $rule !== null
				? $this->renderForwardedNormalizationExpression($rule, $param, $storageName)
				: 'cast<' . $mappedPrimaryType . '>(' . $storageName . ')';
			$lines[] = $branchPrefix . ' (' . $condition . ') {';
			$lines[] = $this->indent(1) . $param->name . ' = ' . $assignmentExpr . ';';
			$lines[] = '}';
		}
		$lines[] = 'else {';
		$lines[] = $this->indent(1) . 'throw std::runtime_error("Unsupported runtime kind for normalized parameter $' . addslashes($param->name) . '");';
		$lines[] = '}';
		return $lines;
	}

	private function renderCurrentArrayParamGuards(): array
	{
		$lines = [];
		foreach ($this->declaredLocalTypes as $name => $declaredType) {
			if (!$this->isPhpArrayLikeDeclaredType($declaredType)) {
				continue;
			}
			$lines[] = 'expect_array_argument(' . $name . ', ' . ($this->isNullablePhpArrayDeclaredType($declaredType) ? 'true' : 'false') . ', "' . addslashes($name) . '");';
		}
		return $lines;
	}

	private function isPhpArrayLikeDeclaredType(string $declaredType): bool
	{
		$normalized = trim($declaredType);
		return $normalized === 'array' || $normalized === '?array';
	}

	private function isNullablePhpArrayDeclaredType(string $declaredType): bool
	{
		return trim($declaredType) === '?array';
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
		if ($statement->kind === 'declare_local') {
			$name = (string) ($statement->payload['name'] ?? '');
			$typed = (string) ($statement->payload['type'] ?? '');
			if ($name === '' || $typed === '') {
				$this->fail('Typed local declaration is missing its name or type at line ' . $statement->line . '.');
			}
			if (isset($this->declaredLocals[$name])) {
				$this->fail('Variable $' . $name . ' is already declared in this block at line ' . $statement->line . '.');
			}
			$this->declaredLocals[$name] = true;
			$this->declaredLocalTypes[$name] = $this->normalizeStoredLocalType($typed);
			return [$this->typeMapper->mapTypedLocalType($typed) . ' ' . $name . ';'];
		}
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
					$this->fail($validationError);
				}
				$validationError = $this->validateTypedLocalAssignment($effectiveTyped, $statement->kind, $exprNode, $statement->line);
				if ($validationError !== null) {
					$this->fail($validationError);
				}
			}

			$expr = $statement->kind === 'assign_ref'
				? $this->renderReferenceBindingExpr($exprNode, $namespacePhp)
				: $this->renderInitializerExpr($exprNode, $effectiveTyped, $namespacePhp);
			$typedVectorType = $effectiveTyped !== null ? $this->mapTypedVectorLocalType($effectiveTyped) : null;
			$isTypedEmptyVectorLiteral = $statement->kind === 'assign' && $typedVectorType !== null && $this->isEmptyPositionalArrayLiteral($exprNode);
			if ($statement->kind === 'assign_ref') {
				if ($name === null) {
					$error = 'reference assignment requires a fresh simple local target at line ' . $statement->line . '.';
					$this->fail($error);
				}

				if (isset($this->declaredLocals[$name])) {
					$error = 'reference rebinding is not supported for $' . $name . ' at line ' . $statement->line . '.';
					$this->fail($error);
				}

				$this->declaredLocals[$name] = true;
				if ($effectiveTyped !== null) {
					$this->declaredLocalTypes[$name] = $this->normalizeStoredLocalType($effectiveTyped);
					return [$this->typeMapper->mapTypedLocalType($effectiveTyped) . ' ' . $name . ' = ' . $expr . ';'];
				}

				return ['auto& ' . $name . ' = ' . $expr . ';'];
			}

			if ($name !== null && !isset($this->declaredLocals[$name])) {
				$this->declaredLocals[$name] = true;
				$closureFunctionType = $effectiveTyped === null ? $this->tryInferStdFunctionTypeFromClosureExpr($exprNode) : null;
				$inferredType = $effectiveTyped ?? $closureFunctionType ?? $this->inferExprType($exprNode);
				if ($inferredType !== 'auto') {
					$this->declaredLocalTypes[$name] = $effectiveTyped !== null ? $this->normalizeStoredLocalType($effectiveTyped) : $inferredType;
				}
				if ($effectiveTyped !== null) {
					if ($isTypedEmptyVectorLiteral) {
						return [$typedVectorType . ' ' . $name . ' = {};'];
					}
					return [$this->typeMapper->mapTypedLocalType($effectiveTyped) . ' ' . $name . ' = ' . $expr . ';'];
				}
				if ($closureFunctionType !== null) {
					return [$closureFunctionType . ' ' . $name . ' = ' . $expr . ';'];
				}
				$declarationType = $this->inferFirstAssignmentDeclarationType($exprNode, $inferredType);
				return [$declarationType . ' ' . $name . ' = ' . $expr . ';'];
			}
			if (is_object($varNode) && (($varNode->kind ?? null) === AstKind::DIM)) {
				if (($varNode->children['dim'] ?? null) === null) {
					$baseExpr = $varNode->children['expr'] ?? null;
					$base = is_object($baseExpr) && (($baseExpr->kind ?? null) === AstKind::DIM)
						? $this->renderDimWriteAccess($baseExpr, $namespacePhp)
						: $this->renderExpr($baseExpr, $namespacePhp);
					$value = $this->renderExpr($exprNode, $namespacePhp);
					$baseType = $this->inferExprType($baseExpr);
					$appendBase = $base;
					if ($this->isUntypedTableHandleType($baseType)) {
						$appendBase = '(' . $base . ')';
					}
					$appendMethod = preg_match('/^vector_t<(.+)>$/', $baseType) === 1 ? 'push_back' : ($this->isUntypedTableHandleType($baseType) ? '->append' : '.append');
					$appendValue = $value;
					if ($this->shouldInlineAssignmentValue($exprNode)) {
						return ['(void) ' . $appendBase . $appendMethod . '(' . $appendValue . ');'];
					}

					$tempName = $this->nextTempName('__append_value');
					$storedTemp = $tempName;
					return [
						'{',
							'auto ' . $tempName . ' = ' . $value . ';',
							'(void) ' . $appendBase . $appendMethod . '(' . $storedTemp . ');',
						'}',
					];
				}
				$target = $this->renderDimWriteAccess($varNode, $namespacePhp);
				$value = $this->renderExpr($exprNode, $namespacePhp);
				return [$target . ' = ' . $value . ';'];
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
			if ($this->currentFinallyReturnContext !== null) {
				return $this->renderFinallyAwareReturnStatement($statement, $namespacePhp);
			}
			if ($statement->payload === null) {
				return ['return;'];
			}
			return ['return ' . $this->renderReturnExpr($statement->payload, $namespacePhp) . ';'];
		}

		if ($statement->kind === 'throw') {
			return ['throw ::scpp::php::make_thrown(' . $this->renderExpr($statement->payload, $namespacePhp) . ');'];
		}

		if ($statement->kind === 'try') {
			return $this->renderTryStatement($statement, $namespacePhp);
		}

		if ($statement->kind === 'echo') {
			// Keep the single-statement fallback lazy as well so operand evaluation order stays explicit.
			return ['echo_eval(' . $this->renderEchoThunk($statement->payload, $namespacePhp) . ');'];
		}

		if ($statement->kind === 'unset') {
			$targetNode = $statement->payload;
			if (is_object($targetNode) && (($targetNode->kind ?? null) === AstKind::DIM) && (($targetNode->children['dim'] ?? null) !== null)) {
				$baseExpr = $targetNode->children['expr'] ?? null;
				$base = $this->renderExpr($baseExpr, $namespacePhp);
				$dim = $this->renderExpr($targetNode->children['dim'] ?? null, $namespacePhp);
				$baseType = $this->inferExprType($baseExpr);
				if (preg_match('/^vector_t<(.+)>$/', $baseType) === 1) {
					$this->fail('unset() on vector_t indexed elements is not supported yet at line ' . $statement->line . '.');
				}
				if ($baseType === 'mixed_t' || $baseType === 'maybe_value_t') {
					return [$base . '.as_table_ref().remove(' . $dim . ');'];
				}
				return [$base . '.remove(' . $dim . ');'];
			}
			// Preserve the generic runtime fallback for non-array/table forms.
			return ['unset(' . $this->renderExpr($statement->payload, $namespacePhp) . ');'];
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
			$scopedLocals = $this->declaredLocals;
			$scopedLocalTypes = $this->declaredLocalTypes;
			$init = $this->renderForInit($statement->payload['init'] ?? [], $namespacePhp);
			$cond = $this->renderForConditionClause($statement->payload['cond'] ?? [], $namespacePhp);
			$loop = $this->renderForClause($statement->payload['loop'] ?? [], $namespacePhp, '');
			$lines = ['for (' . $init . '; ' . $cond . '; ' . $loop . ') {'];
			foreach ($this->renderNestedStatements($statement->payload['stmts'] ?? [], $namespacePhp) as $line) {
				$lines[] = $line;
			}
			$lines[] = '}';
			$this->declaredLocals = $scopedLocals;
			$this->declaredLocalTypes = $scopedLocalTypes;
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
			if (!$this->isSimpleUnitLoopDepth($depth)) {
				$this->fail('Only break 1 is supported; break depth expressions and depths greater than 1 are not supported at line ' . $statement->line . '.');
			}
			return ['break;'];
		}

		if ($statement->kind === 'continue') {
			$depth = $statement->payload;
			if (!$this->isSimpleUnitLoopDepth($depth)) {
				$this->fail('Only continue 1 is supported; continue depth expressions and depths greater than 1 are not supported at line ' . $statement->line . '.');
			}
			return ['continue;'];
		}

		if ($statement->kind === 'expr') {
			return [$this->renderExpr($statement->payload, $namespacePhp) . ';'];
		}

		if ($statement->kind === 'include_or_eval') {
			$error = 'Simple C++ supports require_once only as a static compile-time include with a literal path in the file prologue at line ' . $statement->line . '.';
			$this->fail($error);
		}

		$this->fail('Unsupported statement kind ' . $statement->kind . ' at line ' . $statement->line . '.');
	}

	private function renderTryStatement(Statement $statement, ?string $namespacePhp): array
	{
		$payload = is_array($statement->payload) ? $statement->payload : [];
		$tryStatements = is_array($payload['try'] ?? null) ? $payload['try'] : [];
		$catches = is_array($payload['catches'] ?? null) ? $payload['catches'] : [];
		$finallyStatements = is_array($payload['finally'] ?? null) ? $payload['finally'] : [];

		if ($finallyStatements !== [] && $this->statementsContainUnsupportedFinallyExit($tryStatements, true)) {
			$error = 'finally lowering does not support break/continue leaving the protected try region yet at line ' . $statement->line . '.';
			$this->fail($error);
		}
		if ($finallyStatements !== [] && $this->catchesContainUnsupportedFinallyExit($catches, true)) {
			$error = 'finally lowering does not support break/continue leaving catch handlers yet at line ' . $statement->line . '.';
			$this->fail($error);
		}
		if ($finallyStatements !== [] && $this->statementsContainUnsupportedFinallyExit($finallyStatements, false)) {
			$error = 'finally lowering does not support return/break/continue inside finally blocks yet at line ' . $statement->line . '.';
			$this->fail($error);
		}

		$lines = ['{'];
		$pendingName = $this->nextTempName('__scpp_pending_exception');
		$returnContext = $finallyStatements !== [] ? $this->createFinallyReturnContext() : null;
		$lines[] = $this->indent(1) . 'std::exception_ptr ' . $pendingName . ';';
		if ($returnContext !== null) {
			if ($returnContext['value'] !== null && $returnContext['type'] !== null) {
				$lines[] = $this->indent(1) . 'std::optional<' . $returnContext['type'] . '> ' . $returnContext['value'] . ';';
			}
			$lines[] = $this->indent(1) . 'bool ' . $returnContext['flag'] . ' = false;';
		}
		$lines[] = $this->indent(1) . 'try {';
		$tryBody = $returnContext !== null
			? $this->renderFinallyAwareStatementSequence($tryStatements, $namespacePhp, $returnContext)
			: $this->renderNestedStatements($tryStatements, $namespacePhp);
		foreach ($tryBody as $line) {
			$lines[] = $this->indent(1) . $line;
		}
		$lines[] = $this->indent(1) . '}';

		foreach ($catches as $catchSpec) {
			$lines = array_merge($lines, $this->renderCatchChainArm($catchSpec, $namespacePhp, 1, $returnContext));
		}

		$lines[] = $this->indent(1) . 'catch (...) {';
		$lines[] = $this->indent(2) . $pendingName . ' = std::current_exception();';
		$lines[] = $this->indent(1) . '}';

		if ($finallyStatements !== []) {
			$lines[] = '';
			$lines[] = $this->indent(1) . '{';
			foreach ($this->renderNestedStatements($finallyStatements, $namespacePhp) as $line) {
				$lines[] = $this->indent(1) . $line;
			}
			$lines[] = $this->indent(1) . '}';
			$lines[] = '';
		}

		$lines[] = $this->indent(1) . 'if (' . $pendingName . ') {';
		$lines[] = $this->indent(2) . 'std::rethrow_exception(' . $pendingName . ');';
		$lines[] = $this->indent(1) . '}';
		if ($returnContext !== null) {
			$lines[] = $this->indent(1) . 'if (' . $returnContext['flag'] . ') {';
			if ($returnContext['value'] === null) {
				$lines[] = $this->indent(2) . 'return;';
			} else {
				$lines[] = $this->indent(2) . 'return *' . $returnContext['value'] . ';';
			}
			$lines[] = $this->indent(1) . '}';
		}
		$lines[] = '}';
		return $lines;
	}

	/** @param array{classes:list<mixed>,var:mixed,stmts:list<Statement>,line:int} $catchSpec */
	private function renderCatchChainArm(array $catchSpec, ?string $namespacePhp, int $baseIndentLevel, ?array $returnContext = null): array
	{
		$catchLine = (int) ($catchSpec['line'] ?? 0);
		$varName = $this->extractSimpleVarName($catchSpec['var'] ?? null);
		if ($varName === null) {
			$error = 'catch handlers require a simple variable name at line ' . $catchLine . '.';
			$this->errors[] = $error;
			return [$this->indent($baseIndentLevel) . '// ERROR: ' . $error];
		}

		$classes = is_array($catchSpec['classes'] ?? null) ? $catchSpec['classes'] : [];
		if ($classes === []) {
			$error = 'catch handlers require an explicit exception class at line ' . $catchLine . '.';
			$this->errors[] = $error;
			return [$this->indent($baseIndentLevel) . '// ERROR: ' . $error];
		}

		$throwVar = $this->nextTempName('__scpp_thrown');
		$lines = [$this->indent($baseIndentLevel) . 'catch (const ::scpp::php::thrown_object& ' . $throwVar . ') {'];
		$matched = false;
		foreach ($classes as $classNode) {
			if (!is_object($classNode) || (($classNode->kind ?? null) !== AstKind::NAME)) {
				$error = 'Only named catch types are supported in v1 at line ' . $catchLine . '.';
				$this->errors[] = $error;
				$lines[] = $this->indent($baseIndentLevel + 1) . '// ERROR: ' . $error;
				continue;
			}
			$classType = $this->renderClassName($classNode, $namespacePhp);
			$caughtVar = $this->nextTempName('__scpp_caught');
			$prefix = $matched ? 'else if' : 'if';
			$lines[] = $this->indent($baseIndentLevel + 1) . $prefix . ' (auto ' . $caughtVar . ' = ::scpp::php::catch_as<' . $classType . '>(' . $throwVar . '); static_cast<bool>(' . $caughtVar . ')) {';
			$scopedLocals = $this->declaredLocals;
			$scopedLocalTypes = $this->declaredLocalTypes;
			$this->declaredLocals[$varName] = true;
			$this->declaredLocalTypes[$varName] = 'shared_p<' . $classType . '>' ;
			$lines[] = $this->indent($baseIndentLevel + 1) . 'auto ' . $varName . ' = ' . $caughtVar . ';';
			$bodyLines = $returnContext !== null
				? $this->renderFinallyAwareStatementSequence($catchSpec['stmts'] ?? [], $namespacePhp, $returnContext)
				: $this->renderNestedStatements($catchSpec['stmts'] ?? [], $namespacePhp);
			foreach ($bodyLines as $line) {
				$lines[] = $this->indent($baseIndentLevel) . $line;
			}
			$this->declaredLocals = $scopedLocals;
			$this->declaredLocalTypes = $scopedLocalTypes;
			$lines[] = $this->indent($baseIndentLevel + 1) . '}';
			$matched = true;
		}

		if ($matched) {
			$lines[] = $this->indent($baseIndentLevel + 1) . 'else {';
			$lines[] = $this->indent($baseIndentLevel + 2) . 'throw;';
			$lines[] = $this->indent($baseIndentLevel + 1) . '}';
		} else {
			$lines[] = $this->indent($baseIndentLevel + 1) . 'throw;';
		}
		$lines[] = $this->indent($baseIndentLevel) . '}';
		return $lines;
	}

	/** @param list<Statement> $statements */
	private function statementsContainUnsupportedFinallyExit(array $statements, bool $allowReturn, int $loopDepth = 0, int $switchDepth = 0): bool
	{
		foreach ($statements as $statement) {
			if (!$statement instanceof Statement) {
				continue;
			}
			if ($statement->kind === 'break') {
				if ($loopDepth === 0 && $switchDepth === 0) {
					return true;
				}
				continue;
			}
			if ($statement->kind === 'continue') {
				if ($loopDepth === 0) {
					return true;
				}
				continue;
			}
			if (!$allowReturn && $statement->kind === 'return') {
				return true;
			}
			if ($statement->kind === 'if') {
				foreach (($statement->payload ?? []) as $branch) {
					if ($this->statementsContainUnsupportedFinallyExit($branch['stmts'] ?? [], $allowReturn, $loopDepth, $switchDepth)) {
						return true;
					}
				}
				continue;
			}
			if ($statement->kind === 'while' || $statement->kind === 'do_while' || $statement->kind === 'for' || $statement->kind === 'foreach') {
				if ($this->statementsContainUnsupportedFinallyExit($statement->payload['stmts'] ?? [], $allowReturn, $loopDepth + 1, $switchDepth)) {
					return true;
				}
				continue;
			}
			if ($statement->kind === 'switch') {
				foreach (($statement->payload['cases'] ?? []) as $case) {
					if ($this->statementsContainUnsupportedFinallyExit($case['stmts'] ?? [], $allowReturn, $loopDepth, $switchDepth + 1)) {
						return true;
					}
				}
				continue;
			}
			if ($statement->kind === 'try') {
				$payload = is_array($statement->payload) ? $statement->payload : [];
				if ($this->statementsContainUnsupportedFinallyExit($payload['try'] ?? [], $allowReturn, $loopDepth, $switchDepth)) {
					return true;
				}
				if ($this->catchesContainUnsupportedFinallyExit($payload['catches'] ?? [], $allowReturn, $loopDepth, $switchDepth)) {
					return true;
				}
				if ($this->statementsContainUnsupportedFinallyExit($payload['finally'] ?? [], $allowReturn, $loopDepth, $switchDepth)) {
					return true;
				}
			}
		}
		return false;
	}

	/** @param list<array<string,mixed>> $catches */
	private function catchesContainUnsupportedFinallyExit(array $catches, bool $allowReturn, int $loopDepth = 0, int $switchDepth = 0): bool
	{
		foreach ($catches as $catchSpec) {
			if ($this->statementsContainUnsupportedFinallyExit($catchSpec['stmts'] ?? [], $allowReturn, $loopDepth, $switchDepth)) {
				return true;
			}
		}
		return false;
	}

	private function createFinallyReturnContext(): ?array
	{
		if ($this->currentReturnType === null) {
			return null;
		}

		return [
			'flag' => $this->nextTempName('__scpp_pending_return'),
			'value' => $this->currentReturnType === 'void' ? null : $this->nextTempName('__scpp_return_value'),
			'type' => $this->currentReturnType === 'void' ? null : $this->currentReturnType,
		];
	}

	private function renderFinallyAwareReturnStatement(Statement $statement, ?string $namespacePhp): array
	{
		$context = $this->currentFinallyReturnContext;
		if ($context === null) {
			return ['return;'];
		}

		if ($context['value'] === null) {
			return [
				$context['flag'] . ' = true;',
			];
		}

		return [
			$context['value'] . ' = ' . $this->renderReturnExpr($statement->payload, $namespacePhp) . ';',
			$context['flag'] . ' = true;',
		];
	}

	private function renderFinallyAwareStatementSequence(array $statements, ?string $namespacePhp, array $returnContext): array
	{
		$previousContext = $this->currentFinallyReturnContext;
		$this->currentFinallyReturnContext = $returnContext;
		try {
			$lines = [];
			foreach ($statements as $statement) {
				foreach ($this->wrapWithReturnGuard($this->renderFinallyAwareStatement($statement, $namespacePhp, $returnContext), $returnContext['flag']) as $line) {
					$lines[] = $line;
				}
			}
			return $lines;
		} finally {
			$this->currentFinallyReturnContext = $previousContext;
		}
	}

	private function renderFinallyAwareStatement(Statement $statement, ?string $namespacePhp, array $returnContext): array
	{
		if ($statement->kind === 'if') {
			$lines = [];
			$branches = is_array($statement->payload) ? $statement->payload : [];
			$first = true;
			foreach ($branches as $branch) {
				if (($branch['cond'] ?? null) !== null) {
					$lines[] = ($first ? 'if' : 'else if') . ' (' . $this->renderConditionExpr($branch['cond'], $namespacePhp) . ') {';
				} else {
					$lines[] = 'else {';
				}
				foreach ($this->renderFinallyAwareStatementSequence($branch['stmts'] ?? [], $namespacePhp, $returnContext) as $line) {
					$lines[] = $this->indent(1) . $line;
				}
				$lines[] = '}';
				$first = false;
			}
			return $lines;
		}

		if ($statement->kind === 'while') {
			$lines = ['while (!' . $returnContext['flag'] . ' && (' . $this->renderConditionExpr($statement->payload['cond'] ?? null, $namespacePhp) . ')) {'];
			foreach ($this->renderFinallyAwareStatementSequence($statement->payload['stmts'] ?? [], $namespacePhp, $returnContext) as $line) {
				$lines[] = $this->indent(1) . $line;
			}
			$lines[] = '}';
			return $lines;
		}

		if ($statement->kind === 'do_while') {
			$lines = ['do {'];
			foreach ($this->renderFinallyAwareStatementSequence($statement->payload['stmts'] ?? [], $namespacePhp, $returnContext) as $line) {
				$lines[] = $this->indent(1) . $line;
			}
			$lines[] = '} while (!' . $returnContext['flag'] . ' && (' . $this->renderConditionExpr($statement->payload['cond'] ?? null, $namespacePhp) . '));';
			return $lines;
		}

		if ($statement->kind === 'for') {
			$scopedLocals = $this->declaredLocals;
			$scopedLocalTypes = $this->declaredLocalTypes;
			$init = $this->renderForInit($statement->payload['init'] ?? [], $namespacePhp);
			$cond = $this->renderForConditionClause($statement->payload['cond'] ?? [], $namespacePhp);
			$loop = $this->renderForClause($statement->payload['loop'] ?? [], $namespacePhp, '');
			$lines = ['{'];
			if ($init !== '') {
				$lines[] = $this->indent(1) . $init . ';';
			}
			$lines[] = $this->indent(1) . 'while (!' . $returnContext['flag'] . ' && (' . $cond . ')) {';
			foreach ($this->renderFinallyAwareStatementSequence($statement->payload['stmts'] ?? [], $namespacePhp, $returnContext) as $line) {
				$lines[] = $this->indent(2) . $line;
			}
			if ($loop !== '') {
				$lines[] = $this->indent(2) . 'if (!' . $returnContext['flag'] . ') {';
				$lines[] = $this->indent(3) . $loop . ';';
				$lines[] = $this->indent(2) . '}';
			}
			$lines[] = $this->indent(1) . '}';
			$lines[] = '}';
			$this->declaredLocals = $scopedLocals;
			$this->declaredLocalTypes = $scopedLocalTypes;
			return $lines;
		}

		if ($statement->kind === 'foreach') {
			return $this->renderForeachStatement($statement, $namespacePhp, $returnContext);
		}

		if ($statement->kind === 'switch') {
			$lines = ['switch (' . $this->renderSwitchExpr($statement->payload['cond'] ?? null, $namespacePhp) . ') {'];
			foreach (($statement->payload['cases'] ?? []) as $case) {
				$caseCond = $case['cond'] ?? null;
				$lines[] = $caseCond === null
					? $this->indent(1) . 'default:'
					: $this->indent(1) . 'case ' . $this->renderSwitchCaseValue($caseCond) . ':';
				foreach ($this->renderFinallyAwareStatementSequence($case['stmts'] ?? [], $namespacePhp, $returnContext) as $line) {
					$lines[] = $this->indent(1) . $line;
				}
			}
			$lines[] = '}';
			return $lines;
		}

		if ($statement->kind === 'try') {
			return $this->renderTryStatement($statement, $namespacePhp);
		}

		return $this->renderStatement($statement, $namespacePhp);
	}

	private function wrapWithReturnGuard(array $lines, string $returnFlag): array
	{
		if ($lines === []) {
			return [];
		}
		$out = ['if (!' . $returnFlag . ') {'];
		foreach ($lines as $line) {
			$out[] = $this->indent(1) . $line;
		}
		$out[] = '}';
		return $out;
	}

	private function isSimpleUnitLoopDepth(mixed $depth): bool
	{
		return $depth === null || $depth === 1;
	}

	private function renderForeachStatement(Statement $statement, ?string $namespacePhp, ?array $returnContext = null): array
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
		$sourceType = $this->inferExprType($payload['expr'] ?? null);
		$sourceAccessExpr = $this->isUntypedTableHandleType($sourceType) ? '(*(' . $sourceExpr . '))' : $sourceExpr;
		$elementExpr = $sourceAccessExpr . '.at(' . $indexName . ')';
		$valuePrefix = $byRef ? 'auto &' : 'auto ';
		$valueStoredType = null;
		if (preg_match('/^vector_t<(.+)>$/', $sourceType, $matches) === 1) {
			$valueStoredType = $matches[1];
		} elseif ($this->isUntypedTableType($sourceType)) {
			$valueStoredType = 'mixed_t';
		}

		$lines = [
			'for (int_t ' . $indexName . ' = static_cast<int_t>(0); static_cast<bool>(' . $indexName . ' < static_cast<int_t>(' . $sourceAccessExpr . '.size())); ++' . $indexName . ') {',
		];

		$scopedLocals = $this->declaredLocals;
		$scopedLocalTypes = $this->declaredLocalTypes;

		if ($keyName !== null) {
			// Foreach key bindings remain loop-local in the current model.
			// They do not declare or update an outer name after the loop exits.
			$lines[] = $this->indent(1) . 'auto ' . $keyName . ' = ' . $indexName . ';';
			$this->declaredLocals[$keyName] = true;
			$this->declaredLocalTypes[$keyName] = 'int_t';
		}

		$hasOuterValueBinding = isset($scopedLocals[$valueName]);
		if ($hasOuterValueBinding) {
			// Simple C++ policy: foreach reuses an existing outer value variable if one was
			// already declared before the loop. Otherwise the foreach value target is scoped
			// to the loop body and is not visible after the loop exits.
			$lines[] = $this->indent(1) . $valueName . ' = ' . $elementExpr . ';';
		} else {
			$lines[] = $this->indent(1) . $valuePrefix . $valueName . ' = ' . $elementExpr . ';';
			$this->declaredLocals[$valueName] = true;
			if ($valueStoredType !== null) {
				$this->declaredLocalTypes[$valueName] = $valueStoredType;
			}
		}

		if ($returnContext !== null) {
			$bodyLines = $this->renderFinallyAwareStatementSequence($payload['stmts'] ?? [], $namespacePhp, $returnContext);
			$lines[] = $this->indent(1) . 'if (' . $returnContext['flag'] . ') {';
			$lines[] = $this->indent(2) . 'break;';
			$lines[] = $this->indent(1) . '}';
			foreach ($bodyLines as $line) {
				$lines[] = $this->indent(1) . $line;
			}
		} else {
			foreach ($this->renderNestedStatements($payload['stmts'] ?? [], $namespacePhp) as $line) {
				$lines[] = $line;
			}
		}

		$this->declaredLocals = $scopedLocals;
		$this->declaredLocalTypes = $scopedLocalTypes;
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
		$scopedLocals = $this->declaredLocals;
		$scopedLocalTypes = $this->declaredLocalTypes;

		$lines = [];
		foreach ($this->renderStatementSequence($statements, $namespacePhp) as $line) {
			$lines[] = $this->indent(1) . $line;
		}

		$this->declaredLocals = $scopedLocals;
		$this->declaredLocalTypes = $scopedLocalTypes;
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
				$lines[] = 'echo_eval(' . implode(', ', $thunks) . ');';
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
		$baseExpr = $expr->children['expr'] ?? null;
		$base = $this->renderExpr($baseExpr, $namespacePhp);
		$dimNode = $expr->children['dim'] ?? null;
		if ($dimNode === null) {
			$this->errors[] = 'Append syntax cannot be used as a read expression.';
			return '/* unsupported-append-read */';
		}
		$dim = $this->renderExpr($dimNode, $namespacePhp);
		$baseType = $this->inferExprType($baseExpr);
		if (preg_match('/^vector_t<(.+)>$/', $baseType) === 1) {
			return $base . '.at(' . $dim . ')';
		}
		if ($baseType === 'mixed_t' || $baseType === 'maybe_value_t') {
			return $base . '.get(' . $dim . ')';
		}
		if (is_object($baseExpr) && (($baseExpr->kind ?? null) === AstKind::DIM)) {
			return $base . '.get(' . $dim . ')';
		}
		if ($this->isUntypedTableType($baseType)) {
			return $this->renderUntypedTableAccessBase($base, $baseType) . '._find_val(' . $dim . ')';
		}
		// Unknown dim-read fallback stays on direct indexing so non-array expressions still surface compile-time errors.
		return $base . '[' . $dim . ']';
	}

	private function renderDimWriteAccess(mixed $expr, ?string $namespacePhp): string
	{
		$baseExpr = $expr->children['expr'] ?? null;
		$base = is_object($baseExpr) && (($baseExpr->kind ?? null) === AstKind::DIM)
			? $this->renderDimWriteAccess($baseExpr, $namespacePhp)
			: $this->renderExpr($baseExpr, $namespacePhp);
		$dimNode = $expr->children['dim'] ?? null;
		if ($dimNode === null) {
			$this->errors[] = 'Append syntax cannot be used as an lvalue target.';
			return '/* unsupported-append-lvalue */';
		}
		$dim = $this->renderExpr($dimNode, $namespacePhp);
		$baseType = $this->inferExprType($baseExpr);
		if (preg_match('/^vector_t<(.+)>$/', $baseType) === 1) {
			return $base . '.at(' . $dim . ')';
		}
		if ($baseType === 'mixed_t' || $baseType === 'maybe_value_t') {
			return $base . '[' . $dim . ']';
		}
		if ($this->isUntypedTableType($baseType)) {
			return $this->renderUntypedTableAccessBase($base, $baseType) . '[' . $dim . ']';
		}
		return $base . '[' . $dim . ']';
	}

	private function isUntypedTableType(string $type): bool
	{
		return $type === 'hash_t'
			|| $type === '::scpp::hash_t'
			|| $type === 'hash_t<mixed_t>'
			|| $type === '::scpp::hash_t<mixed_t>'
			|| $type === 'unique_p<hash_t<mixed_t>>'
			|| $type === 'unique_p<::scpp::hash_t<mixed_t>>'
			|| $type === '::scpp::unique_p<hash_t<mixed_t>>'
			|| $type === '::scpp::unique_p<::scpp::hash_t<mixed_t>>';
	}

	private function isUntypedTableHandleType(string $type): bool
	{
		return $type === 'unique_p<hash_t<mixed_t>>'
			|| $type === 'unique_p<::scpp::hash_t<mixed_t>>'
			|| $type === '::scpp::unique_p<hash_t<mixed_t>>'
			|| $type === '::scpp::unique_p<::scpp::hash_t<mixed_t>>';
	}

	private function renderUntypedTableAccessBase(string $base, string $type): string
	{
		if ($this->isUntypedTableHandleType($type)) {
			return '(*(' . $base . '))';
		}

		return $base;
	}

	private function renderAssignmentExpr(mixed $varNode, mixed $valueNode, ?string $namespacePhp): string
	{
		if (is_object($varNode) && (($varNode->kind ?? null) === AstKind::DIM)) {
			$baseExpr = $varNode->children['expr'] ?? null;
			$base = is_object($baseExpr) && (($baseExpr->kind ?? null) === AstKind::DIM)
				? $this->renderDimWriteAccess($baseExpr, $namespacePhp)
				: $this->renderExpr($baseExpr, $namespacePhp);
			$value = $this->renderExpr($valueNode, $namespacePhp);
			$baseType = $this->inferExprType($baseExpr);
			$dimNode = $varNode->children['dim'] ?? null;
			if ($dimNode === null) {
				$appendBase = $base;
				if ($this->isUntypedTableHandleType($baseType)) {
					$appendBase = '(' . $base . ')';
				}
				$appendMethod = preg_match('/^vector_t<(.+)>$/', $baseType) === 1 ? 'push_back' : ($this->isUntypedTableHandleType($baseType) ? '->append' : '.append');
				$appendValue = $value;
				$returnValue = $value;
				if ($this->shouldInlineAssignmentValue($valueNode)) {
					return '([&]() { (void) ' . $appendBase . $appendMethod . '(' . $appendValue . '); return ' . $returnValue . '; }())';
				}

				// Complex append assignments still spill into a temporary so the right-hand side is named once.
				$tempName = $this->nextTempName('__append_value');
				$storedTemp = $tempName;
				return '([&]() { auto ' . $tempName . ' = ' . $value . '; (void) ' . $appendBase . $appendMethod . '(' . $storedTemp . '); return ' . $storedTemp . '; }())';
			}

			$target = $this->renderDimWriteAccess($varNode, $namespacePhp);
			return '(' . $target . ' = ' . $value . ')';
		}

		$target = $this->renderAssignmentTarget($varNode, $namespacePhp);
		$value = $this->renderExpr($valueNode, $namespacePhp);
		return '(' . $target . ' = ' . $value . ')';
	}

	private function shouldInlineAssignmentValue(mixed $expr): bool
	{
		if (is_int($expr) || is_float($expr) || is_string($expr) || is_bool($expr) || $expr === null) {
			return true;
		}

		if (!is_object($expr)) {
			return false;
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::VAR
			|| $kind === AstKind::CONST
			|| $kind === AstKind::CLASS_CONST
			|| $kind === AstKind::NAME
			|| $kind === AstKind::ARRAY) {
			return true;
		}

		$magicConstKindName = AstKind::class . '::MAGIC_CONST';
		if (defined($magicConstKindName) && $kind === constant($magicConstKindName)) {
			return true;
		}

		return false;
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
			return $this->renderDimWriteAccess($expr, $namespacePhp);
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
				$checks[] = 'static_cast<bool>(identical(' . $subjectName . ', ' . $this->renderExpr($condition, $namespacePhp) . '))';
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
		$rightDeclarationType = $this->inferFirstAssignmentDeclarationType($rightExprNode, null);
		$leftType = $typed !== null
			? $this->typeMapper->mapTypedLocalType($typed)
			: $this->inferFirstAssignmentDeclarationType($rightExprNode, null);
		return [
			$rightDeclarationType . ' ' . $rightName . ' = ' . $rightExpr . ';',
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

		if (is_object($expr) && in_array(($expr->kind ?? null), [AstKind::CLOSURE, AstKind::ARROW_FUNC], true)) {
			$savedExpectedClosureSignature = $this->currentExpectedClosureSignature;
			try {
				$this->currentExpectedClosureSignature = $this->parseExpectedClosureSignature($typedLocalType);
				return $this->renderExpr($expr, $namespacePhp);
			} finally {
				$this->currentExpectedClosureSignature = $savedExpectedClosureSignature;
			}
		}

		$rendered = $this->renderExpr($expr, $namespacePhp);
		if ($typedLocalType !== null) {
			return $this->wrapExprForExpectedType($rendered, $this->inferExprType($expr), $this->typeMapper->mapTypedLocalType($typedLocalType));
		}

		return $rendered;
	}



	private function shouldCopyInitializerValue(mixed $expr): bool
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::DIM)) {
			return false;
		}

		$baseExpr = $expr->children['expr'] ?? null;
		$baseType = $this->inferExprType($baseExpr);
		return $this->isUntypedTableType($baseType);
	}

	private function inferFirstAssignmentDeclarationType(mixed $exprNode, ?string $inferredType): string
	{
		if (is_object($exprNode) && (($exprNode->kind ?? null) === AstKind::ARRAY)) {
			return 'mixed_t';
		}

		if ($this->isNullExpr($exprNode)) {
			return 'mixed_t';
		}

		if ($inferredType === 'mixed_t') {
			return 'mixed_t';
		}

		return 'auto';
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
			return 'unique<' . trim($matches[1]) . '>(' . $args . ')';
		}

		return null;
	}

	private function renderArrayLiteral(mixed $expr, ?string $namespacePhp, ?string $typedLocalType = null): string
	{
		$elements = is_object($expr) && isset($expr->children) && is_array($expr->children)
			? array_values($expr->children)
			: [];

		$mappedVectorType = $typedLocalType !== null ? $this->mapTypedVectorLocalType($typedLocalType) : null;
		if ($mappedVectorType !== null) {
			if ($elements === []) {
				return $mappedVectorType . '{}';
			}

			$values = [];
			foreach ($elements as $element) {
				if (!is_object($element) || (($element->kind ?? null) !== AstKind::ARRAY_ELEM)) {
					$this->errors[] = 'Unsupported array literal element shape at line ' . (int) ($expr->lineno ?? 0) . '.';
					return '/* unsupported-array-literal */';
				}

				$key = $element->children['key'] ?? null;
				if ($key !== null) {
					$this->errors[] = 'Typed vector literals cannot contain explicit keys at line ' . (int) ($element->lineno ?? $expr->lineno ?? 0) . '.';
					return '/* unsupported-keyed-vector-literal */';
				}

				$valueNode = $element->children['value'] ?? null;
				if ($valueNode === null) {
					$this->errors[] = 'Array unpack and empty array elements are not supported yet at line ' . (int) ($element->lineno ?? $expr->lineno ?? 0) . '.';
					return '/* unsupported-array-element */';
				}

				$values[] = $this->renderExpr($valueNode, $namespacePhp);
			}

			return $mappedVectorType . '{' . implode(', ', $values) . '}';
		}

		if ($elements === []) {
			return 'mixed_t{shared_table_()}';
		}

		$items = [];
		foreach ($elements as $element) {
			if (!is_object($element) || (($element->kind ?? null) !== AstKind::ARRAY_ELEM)) {
				$this->errors[] = 'Unsupported array literal element shape at line ' . (int) ($expr->lineno ?? 0) . '.';
				return '/* unsupported-array-literal */';
			}

			$valueNode = $element->children['value'] ?? null;
			if ($valueNode === null) {
				$this->errors[] = 'Array unpack and empty array elements are not supported yet at line ' . (int) ($element->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-array-element */';
			}

			$keyNode = $element->children['key'] ?? null;
			if ($keyNode === null) {
				$items[] = 'table_item_(' . $this->renderExpr($valueNode, $namespacePhp) . ')';
				continue;
			}

			$items[] = 'table_kv_(' . $this->renderExpr($keyNode, $namespacePhp) . ', ' . $this->renderExpr($valueNode, $namespacePhp) . ')';
		}

		return 'mixed_t{shared_table_(' . implode(', ', $items) . ')}';
	}


	private function tryInferStdFunctionTypeFromClosureExpr(mixed $exprNode): ?string
	{
		if (!is_object($exprNode) || !in_array(($exprNode->kind ?? null), [AstKind::CLOSURE, AstKind::ARROW_FUNC], true)) {
			return null;
		}

		$paramsNode = $exprNode->children['params'] ?? null;
		$returnTypeNode = $exprNode->children['returnType'] ?? null;
		$stmtsNode = $exprNode->children['stmts'] ?? null;
		$params = (is_object($paramsNode) && isset($paramsNode->children) && is_array($paramsNode->children))
			? array_values($paramsNode->children)
			: [];
		$statements = $this->buildStatementSequenceFromMixed($stmtsNode);

		$paramTypes = [];
		foreach ($params as $param) {
			if (!is_object($param) || (($param->kind ?? null) !== AstKind::PARAM)) {
				continue;
			}
			if (($param->flags ?? 0) & AstKind::PARAM_VARIADIC) {
				return null;
			}
			if (($param->children['default'] ?? null) !== null) {
				return null;
			}
			$phpType = $this->readAstTypeName($param->children['type'] ?? null);
			if ($phpType === null) {
				return null;
			}
			$isReference = (((int) ($param->flags ?? 0)) & AstKind::PARAM_REF) !== 0;
			$paramTypes[] = $this->typeMapper->mapParamType($phpType, $isReference);
		}

		$returnType = $this->renderClosureReturnType($returnTypeNode, $statements, $exprNode);
		if ($returnType === '/* unsupported-closure-return-type */' || $returnType === null) {
			return null;
		}

		return 'std::function<' . $returnType . '(' . implode(', ', $paramTypes) . ')>';
	}

	/** @return null|array{returnType:?string,paramTypes:list<string>} */
	private function parseExpectedClosureSignature(?string $typedLocalType): ?array
	{
		if ($typedLocalType === null) {
			return null;
		}
		$normalized = trim($typedLocalType);
		if (preg_match('/^function\s*<\s*(.+)\s*>$/', $normalized, $matches) !== 1) {
			return null;
		}
		$inner = trim($matches[1]);
		$open = strpos($inner, '(');
		$close = strrpos($inner, ')');
		if ($open === false || $close === false || $close < $open) {
			return null;
		}
		$returnPhpType = trim(substr($inner, 0, $open));
		$paramsInner = trim(substr($inner, $open + 1, $close - $open - 1));
		$returnType = $returnPhpType !== '' ? $this->typeMapper->mapReturnType($returnPhpType, false) : null;
		$paramTypes = [];
		foreach ($this->splitTopLevelCommaList($paramsInner) as $param) {
			$param = trim($param);
			if ($param === '') {
				continue;
			}
			$byRef = false;
			if (str_starts_with($param, 'ref ')) {
				$byRef = true;
				$param = trim(substr($param, 4));
			} elseif (str_ends_with($param, '&')) {
				$byRef = true;
				$param = rtrim(substr($param, 0, -1));
			}
			$paramTypes[] = $this->typeMapper->mapParamType($param, $byRef);
		}
		return ['returnType' => $returnType, 'paramTypes' => $paramTypes];
	}

	/** @return list<string> */
	private function splitTopLevelCommaList(string $value): array
	{
		$value = trim($value);
		if ($value === '') {
			return [];
		}
		$out = [];
		$current = '';
		$angleDepth = 0;
		$parenDepth = 0;
		$length = strlen($value);
		for ($i = 0; $i < $length; ++$i) {
			$ch = $value[$i];
			if ($ch === '<') {
				++$angleDepth;
			} elseif ($ch === '>') {
				--$angleDepth;
			} elseif ($ch === '(') {
				++$parenDepth;
			} elseif ($ch === ')') {
				--$parenDepth;
			} elseif ($ch === ',' && $angleDepth === 0 && $parenDepth === 0) {
				$out[] = trim($current);
				$current = '';
				continue;
			}
			$current .= $ch;
		}
		if (trim($current) !== '') {
			$out[] = trim($current);
		}
		return $out;
	}

	/** @return list<Statement> */
	private function buildStatementSequenceFromMixed(mixed $node): array
	{
		if (is_object($node) && isset($node->children) && is_array($node->children) && (($node->kind ?? null) === AstKind::STMT_LIST)) {
			return $this->buildStatementsFromAstNodes(array_values($node->children));
		}
		$stmt = $this->buildStatementFromAstNode($node);
		return $stmt !== null ? [$stmt] : [];
	}

	/** @param list<string> $paramNames @return list<string> */
	private function collectImplicitCaptureNames(mixed $node, array $paramNames): array
	{
		$paramSet = [];
		foreach ($paramNames as $name) {
			$paramSet[$name] = true;
		}
		$captureSet = [];
		$this->walkArrowCaptureCandidates($node, $paramSet, $captureSet);
		return array_keys($captureSet);
	}

	/** @param array<string,bool> $paramSet @param array<string,bool> $captureSet */
	private function walkArrowCaptureCandidates(mixed $node, array $paramSet, array &$captureSet): void
	{
		if (!is_object($node)) {
			return;
		}

		$kind = $node->kind ?? null;
		if ($kind === AstKind::VAR) {
			$name = $this->extractSimpleVarName($node);
			if ($name !== null && !isset($paramSet[$name]) && isset($this->declaredLocals[$name])) {
				$captureSet[$name] = true;
			}
			return;
		}

		foreach (($node->children ?? []) as $child) {
			if (is_array($child)) {
				foreach ($child as $subChild) {
					$this->walkArrowCaptureCandidates($subChild, $paramSet, $captureSet);
				}
				continue;
			}
			$this->walkArrowCaptureCandidates($child, $paramSet, $captureSet);
		}
	}

	private function resolveAstParamDocType(object $param): ?string
	{
		$name = (string) ($param->children['name'] ?? '');
		$line = (int) ($param->lineno ?? 0);
		if ($name !== '') {
			$key = $line . ':' . $name;
			$fromMap = $this->localTypeComments[$key] ?? null;
			if (is_string($fromMap) && $fromMap !== '') {
				return $fromMap;
			}
		}

		$docComment = $param->children['docComment'] ?? null;
		if (!is_string($docComment)) {
			return null;
		}

		$inner = trim($docComment);
		if (!str_starts_with($inner, '/**') || !str_ends_with($inner, '*/')) {
			return null;
		}

		$inner = trim(substr($inner, 3, -2));
		return $inner === '' ? null : $inner;
	}

	private function appendLvalueReferenceType(string $mappedType): string
	{
		return str_ends_with($mappedType, '&') ? $mappedType : ($mappedType . '&');
	}

	private function mapClosureDocParamType(string $docType, bool $isReference): string
	{
		return $this->typeMapper->mapParamType($docType, $isReference);
	}

	private function renderArrowFunctionExpr(object $expr, ?string $namespacePhp): string
	{
		$paramsNode = $expr->children['params'] ?? null;
		$stmtsNode = $expr->children['stmts'] ?? null;
		$returnTypeNode = $expr->children['returnType'] ?? null;

		$params = (is_object($paramsNode) && isset($paramsNode->children) && is_array($paramsNode->children))
			? array_values($paramsNode->children)
			: [];
		$statements = $this->buildStatementSequenceFromMixed($stmtsNode);
		$paramNames = [];
		foreach ($params as $param) {
			if (!is_object($param) || (($param->kind ?? null) !== AstKind::PARAM)) {
				continue;
			}
			$name = (string) ($param->children['name'] ?? '');
			if ($name !== '') {
				$paramNames[] = $name;
			}
		}
		$captureItems = $this->collectImplicitCaptureNames($stmtsNode, $paramNames);

		$expectedSignature = $this->currentExpectedClosureSignature;
		$paramIndex = 0;
		foreach ($params as $param) {
			if (!is_object($param) || (($param->kind ?? null) !== AstKind::PARAM)) {
				continue;
			}
			if (($param->flags ?? 0) & AstKind::PARAM_VARIADIC) {
				$this->errors[] = 'Arrow function variadic parameters are not supported yet at line ' . (int) ($param->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-arrow-variadic */';
			}
			if (($param->children['default'] ?? null) !== null) {
				$this->errors[] = 'Arrow function default parameters are not supported yet when lowering to std::function at line ' . (int) ($param->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-arrow-default-param */';
			}
			$expectedParamType = $expectedSignature['paramTypes'][$paramIndex] ?? null;
			$phpType = $this->readAstTypeName($param->children['type'] ?? null);
			$docType = $this->resolveAstParamDocType($param);
			if ($phpType !== null && $docType !== null) {
				$this->errors[] = 'Conflicting arrow function parameter type sources for $' . (string) ($param->children['name'] ?? '') . ' at line ' . (int) ($param->lineno ?? $expr->lineno ?? 0) . ': use either a native PHP type or a doc-comment type, not both.';
				return '/* unsupported-arrow-conflicting-param-type */';
			}
			if ($phpType === null && $docType === null && !is_string($expectedParamType)) {
				$this->errors[] = 'Arrow function parameters require PHP signature types or explicit doc-comment types in std::function lowering at line ' . (int) ($param->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-arrow-untyped-param */';
			}
			$paramIndex++;
		}

		$capture = $captureItems === [] ? '[]' : '[' . implode(', ', $captureItems) . ']';
		$paramList = $this->renderClosureParams($params, $namespacePhp);
		$returnType = $this->renderClosureReturnType($returnTypeNode, $statements, $expr);
		if (is_string($returnType) && str_starts_with($returnType, '/* unsupported-closure-')) {
			return $returnType;
		}

		$savedDeclaredLocals = $this->declaredLocals;
		$savedDeclaredLocalTypes = $this->declaredLocalTypes;
		$savedReturnType = $this->currentReturnType;

		foreach ($captureItems as $captureName) {
			$this->declaredLocals[$captureName] = true;
			if (isset($savedDeclaredLocalTypes[$captureName])) {
				$this->declaredLocalTypes[$captureName] = $savedDeclaredLocalTypes[$captureName];
			}
		}
		$paramIndex = 0;
		foreach ($params as $param) {
			if (!is_object($param) || (($param->kind ?? null) !== AstKind::PARAM)) {
				continue;
			}
			$name = (string) ($param->children['name'] ?? '');
			if ($name === '') {
				continue;
			}
			$this->declaredLocals[$name] = true;
			$expectedParamType = $expectedSignature['paramTypes'][$paramIndex] ?? null;
			$storedType = $this->inferClosureParamStoredType($param, $expectedParamType);
			if ($storedType !== null) {
				$this->declaredLocalTypes[$name] = $storedType;
			}
			$paramIndex++;
		}
		$this->currentReturnType = $returnType !== 'void' ? $returnType : null;

		$bodyLines = $this->renderStatementSequence($statements, $namespacePhp);

		$this->declaredLocals = $savedDeclaredLocals;
		$this->declaredLocalTypes = $savedDeclaredLocalTypes;
		$this->currentReturnType = $savedReturnType;

		$out = [];
		$signature = $capture . '(' . $paramList . ')';
		if ($captureItems !== []) {
			$signature .= ' mutable';
		}
		if ($returnType !== null) {
			$signature .= ' -> ' . $returnType;
		}
		$signature .= ' {';
		$out[] = $signature;
		foreach ($bodyLines as $line) {
			$out[] = $this->indent(1) . $line;
		}
		$out[] = '}';

		return implode("
", $out);
	}

	private function renderClosureExpr(object $expr, ?string $namespacePhp): string
	{
		$paramsNode = $expr->children['params'] ?? null;
		$useNode = $expr->children['uses'] ?? null;
		$stmtsNode = $expr->children['stmts'] ?? null;
		$returnTypeNode = $expr->children['returnType'] ?? null;

		$params = (is_object($paramsNode) && isset($paramsNode->children) && is_array($paramsNode->children))
			? array_values($paramsNode->children)
			: [];
		$uses = (is_object($useNode) && isset($useNode->children) && is_array($useNode->children))
			? array_values($useNode->children)
			: [];
		$statements = (is_object($stmtsNode) && isset($stmtsNode->children) && is_array($stmtsNode->children))
			? $this->buildStatementsFromAstNodes(array_values($stmtsNode->children))
			: [];

		$captureItems = [];
		foreach ($uses as $use) {
			if (!is_object($use)) {
				continue;
			}
			$isUseByReference = false;
			if (($use->kind ?? null) === AstKind::REF) {
				$isUseByReference = true;
			} elseif (($use->kind ?? null) === AstKind::CLOSURE_VAR && (((int) ($use->flags ?? 0)) & 1) !== 0) {
				// php-ast represents `use (&$x)` as AST_CLOSURE_VAR with a by-reference flag,
				// not as a nested AST_REF node.
				$isUseByReference = true;
			}
			if ($isUseByReference) {
				$this->errors[] = 'Closure use-by-reference is not supported yet at line ' . (int) ($use->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-closure-use-ref */';
			}
			$name = (string) ($use->children['name'] ?? '');
			if ($name === '') {
				$this->errors[] = 'Closure use-capture requires a simple variable name at line ' . (int) ($use->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-closure-use */';
			}
			$captureItems[] = $name;
		}

		$expectedSignature = $this->currentExpectedClosureSignature;
		$paramIndex = 0;
		foreach ($params as $param) {
			if (!is_object($param) || (($param->kind ?? null) !== AstKind::PARAM)) {
				continue;
			}
			if (($param->flags ?? 0) & AstKind::PARAM_VARIADIC) {
				$this->errors[] = 'Closure variadic parameters are not supported yet at line ' . (int) ($param->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-closure-variadic */';
			}
			if (($param->children['default'] ?? null) !== null) {
				$this->errors[] = 'Closure default parameters are not supported yet when lowering to std::function at line ' . (int) ($param->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-closure-default-param */';
			}
			$expectedParamType = $expectedSignature['paramTypes'][$paramIndex] ?? null;
			$phpType = $this->readAstTypeName($param->children['type'] ?? null);
			$docType = $this->resolveAstParamDocType($param);
			if ($phpType !== null && $docType !== null) {
				$this->errors[] = 'Conflicting closure parameter type sources for $' . (string) ($param->children['name'] ?? '') . ' at line ' . (int) ($param->lineno ?? $expr->lineno ?? 0) . ': use either a native PHP type or a doc-comment type, not both.';
				return '/* unsupported-closure-conflicting-param-type */';
			}
			if ($phpType === null && $docType === null) {
				$this->errors[] = 'Closure parameters require PHP signature types or explicit doc-comment types in std::function lowering at line ' . (int) ($param->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-closure-untyped-param */';
			}
			$paramIndex++;
		}

		$capture = $captureItems === [] ? '[]' : '[' . implode(', ', $captureItems) . ']';
		$paramList = $this->renderClosureParams($params, $namespacePhp);
		$returnType = $this->renderClosureReturnType($returnTypeNode, $statements, $expr);
		if ($returnType === '/* unsupported-closure-return-type */') {
			return $returnType;
		}

		$savedDeclaredLocals = $this->declaredLocals;
		$savedDeclaredLocalTypes = $this->declaredLocalTypes;
		$savedReturnType = $this->currentReturnType;

		foreach ($captureItems as $captureName) {
			$this->declaredLocals[$captureName] = true;
			if (isset($savedDeclaredLocalTypes[$captureName])) {
				$this->declaredLocalTypes[$captureName] = $savedDeclaredLocalTypes[$captureName];
			}
		}
		$paramIndex = 0;
		foreach ($params as $param) {
			if (!is_object($param) || (($param->kind ?? null) !== AstKind::PARAM)) {
				continue;
			}
			$name = (string) ($param->children['name'] ?? '');
			if ($name === '') {
				continue;
			}
			$this->declaredLocals[$name] = true;
			$expectedParamType = $expectedSignature['paramTypes'][$paramIndex] ?? null;
			$storedType = $this->inferClosureParamStoredType($param, $expectedParamType);
			if ($storedType !== null) {
				$this->declaredLocalTypes[$name] = $storedType;
			}
			$paramIndex++;
		}
		$this->currentReturnType = $returnType !== 'void' ? $returnType : null;

		$bodyLines = $this->renderStatementSequence($statements, $namespacePhp);

		$this->declaredLocals = $savedDeclaredLocals;
		$this->declaredLocalTypes = $savedDeclaredLocalTypes;
		$this->currentReturnType = $savedReturnType;

		$out = [];
		$signature = $capture . '(' . $paramList . ')';
		if ($captureItems !== []) {
			// PHP closures captured by value may still assign to the captured name locally.
			// In C++, lambda operator() is const by default, so mark captured closures mutable.
			$signature .= ' mutable';
		}
		if ($returnType !== null) {
			$signature .= ' -> ' . $returnType;
		}
		$signature .= ' {';
		$out[] = $signature;
		foreach ($bodyLines as $line) {
			$out[] = $this->indent(1) . $line;
		}
		$out[] = '}';

		return implode("
", $out);
	}

	/** @param list<mixed> $nodes @return list<Statement> */
	private function buildStatementsFromAstNodes(array $nodes): array
	{
		$out = [];
		foreach ($nodes as $node) {
			$stmt = $this->buildStatementFromAstNode($node);
			if ($stmt !== null) {
				$out[] = $stmt;
			}
		}
		return $out;
	}

	private function buildStatementFromAstNode(mixed $node): ?Statement
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
		if ($kind === AstKind::RETURN) {
			return new Statement('return', $node->children['expr'] ?? null, $line);
		}
		if ($kind === AstKind::THROW) {
			return new Statement('throw', $node->children['expr'] ?? null, $line);
		}
		if ($kind === AstKind::TRY) {
			$catches = [];
			foreach (($node->children['catches']->children ?? []) as $catchNode) {
				if (!is_object($catchNode) || (($catchNode->kind ?? null) !== AstKind::CATCH)) {
					continue;
				}
				$classNode = $catchNode->children['class'] ?? null;
				$classKinds = is_object($classNode) && isset($classNode->children) && is_array($classNode->children)
					? array_values($classNode->children)
					: [$classNode];
				$stmtsNode = $catchNode->children['stmts'] ?? null;
				$catches[] = [
					'classes' => $classKinds,
					'var' => $catchNode->children['var'] ?? null,
					'stmts' => (is_object($stmtsNode) && isset($stmtsNode->children) && is_array($stmtsNode->children))
						? $this->buildStatementsFromAstNodes(array_values($stmtsNode->children))
						: [],
					'line' => (int) ($catchNode->lineno ?? $line),
				];
			}
			$tryNode = $node->children['try'] ?? null;
			$finallyNode = $node->children['finally'] ?? null;
			return new Statement('try', [
				'try' => (is_object($tryNode) && isset($tryNode->children) && is_array($tryNode->children))
					? $this->buildStatementsFromAstNodes(array_values($tryNode->children))
					: [],
				'catches' => $catches,
				'finally' => (is_object($finallyNode) && isset($finallyNode->children) && is_array($finallyNode->children))
					? $this->buildStatementsFromAstNodes(array_values($finallyNode->children))
					: [],
			], $line);
		}
		if ($kind === AstKind::AST_ECHO) {
			return new Statement('echo', $node->children['expr'] ?? null, $line);
		}
		if ($kind === AstKind::AST_UNSET) {
			return new Statement('unset', $node->children['var'] ?? null, $line);
		}
		if ($kind === AstKind::IF) {
			$branches = [];
			$children = (isset($node->children) && is_array($node->children)) ? array_values($node->children) : [];
			foreach ($children as $ifElem) {
				if (!is_object($ifElem) || (($ifElem->kind ?? null) !== AstKind::IF_ELEM)) {
					continue;
				}
				$branchStmtsNode = $ifElem->children['stmts'] ?? null;
				$branchStmts = (is_object($branchStmtsNode) && isset($branchStmtsNode->children) && is_array($branchStmtsNode->children))
					? $this->buildStatementsFromAstNodes(array_values($branchStmtsNode->children))
					: [];
				$branches[] = [
					'cond' => $ifElem->children['cond'] ?? null,
					'stmts' => $branchStmts,
					'line' => (int) ($ifElem->lineno ?? $line),
				];
			}
			return new Statement('if', $branches, $line);
		}
		if ($kind === AstKind::WHILE) {
			$stmtsNode = $node->children['stmts'] ?? null;
			return new Statement('while', [
				'cond' => $node->children['cond'] ?? null,
				'stmts' => (is_object($stmtsNode) && isset($stmtsNode->children) && is_array($stmtsNode->children))
					? $this->buildStatementsFromAstNodes(array_values($stmtsNode->children))
					: [],
			], $line);
		}
		if ($kind === AstKind::DO_WHILE) {
			$stmtsNode = $node->children['stmts'] ?? null;
			return new Statement('do_while', [
				'cond' => $node->children['cond'] ?? null,
				'stmts' => (is_object($stmtsNode) && isset($stmtsNode->children) && is_array($stmtsNode->children))
					? $this->buildStatementsFromAstNodes(array_values($stmtsNode->children))
					: [],
			], $line);
		}
		if ($kind === AstKind::BREAK) {
			return new Statement('break', $node->children['depth'] ?? null, $line);
		}
		if ($kind === AstKind::CONTINUE) {
			return new Statement('continue', $node->children['depth'] ?? null, $line);
		}
		if ($kind === AstKind::CALL || $kind === AstKind::STATIC_CALL || $kind === AstKind::METHOD_CALL || $kind === AstKind::PRE_INC || $kind === AstKind::PRE_DEC || $kind === AstKind::POST_INC || $kind === AstKind::POST_DEC) {
			return new Statement('expr', $node, $line);
		}
		return null;
	}

	/** @param list<mixed> $params */
	private function renderClosureParams(array $params, ?string $namespacePhp): string
	{
		$out = [];
		$paramIndex = 0;
		foreach ($params as $param) {
			if (!is_object($param) || (($param->kind ?? null) !== AstKind::PARAM)) {
				continue;
			}
			$name = (string) ($param->children['name'] ?? '');
			if ($name === '') {
				continue;
			}
			$type = $this->renderClosureParamType($param, $paramIndex);
			$rendered = $type . ' ' . $name;
			$default = $param->children['default'] ?? null;
			if ($default !== null) {
				$rendered .= ' = ' . $this->renderExpr($default, $namespacePhp);
			}
			$out[] = $rendered;
			$paramIndex++;
		}
		return implode(', ', $out);
	}

	private function renderClosureParamType(object $param, int $paramIndex): string
	{
		$expectedParamType = $this->currentExpectedClosureSignature['paramTypes'][$paramIndex] ?? null;
		$phpType = $this->readAstTypeName($param->children['type'] ?? null);
		$docType = $this->resolveAstParamDocType($param);
		$isReference = (((int) ($param->flags ?? 0)) & AstKind::PARAM_REF) !== 0;
		if ($phpType !== null) {
			return $this->typeMapper->mapParamType($phpType, $isReference);
		}
		if ($docType !== null) {
			return $this->mapClosureDocParamType($docType, $isReference);
		}
		if (is_string($expectedParamType) && $expectedParamType !== '') {
			return $expectedParamType;
		}
		$default = $param->children['default'] ?? null;
		if ($default !== null) {
			$defaultType = $this->inferExprType($default);
			if ($defaultType !== 'auto') {
				return $defaultType;
			}
		}
		return $isReference ? 'auto&' : 'auto';
	}

	private function inferClosureParamStoredType(object $param, ?string $expectedParamType = null): ?string
	{
		$phpType = $this->readAstTypeName($param->children['type'] ?? null);
		$docType = $this->resolveAstParamDocType($param);
		$isReference = (((int) ($param->flags ?? 0)) & AstKind::PARAM_REF) !== 0;
		if ($phpType !== null) {
			return $this->typeMapper->mapParamType($phpType, $isReference);
		}
		if ($docType !== null) {
			return $this->normalizeStoredLocalType($docType);
		}
		if (is_string($expectedParamType) && $expectedParamType !== '') {
			return $expectedParamType;
		}
		$default = $param->children['default'] ?? null;
		if ($default !== null) {
			$defaultType = $this->inferExprType($default);
			return $defaultType !== 'auto' ? $defaultType : null;
		}
		return null;
	}

	/** @param list<Statement> $statements */
	private function renderClosureReturnType(mixed $returnTypeNode, array $statements, object $expr): ?string
	{
		$phpType = $this->readAstTypeName($returnTypeNode);
		$docFunctionType = $this->resolveClosureReturnDocFunctionType($statements);
		if ($phpType !== null && $docFunctionType !== null) {
			$this->errors[] = 'Conflicting closure return type sources at line ' . (int) ($expr->lineno ?? 0) . ': use either a native PHP return type or a doc-comment callable return type, not both.';
			return '/* unsupported-closure-conflicting-return-type */';
		}
		if ($phpType !== null) {
			return $this->typeMapper->mapReturnType($phpType, false);
		}

		if ($docFunctionType !== null) {
			return $this->typeMapper->mapTypedLocalType($docFunctionType);
		}

		foreach ($statements as $statement) {
			if ($statement->kind === 'return' && $statement->payload !== null) {
				$this->errors[] = 'Closure return types must be declared explicitly in std::function lowering at line ' . (int) ($expr->lineno ?? 0) . '.';
				return '/* unsupported-closure-return-type */';
			}
		}

		return 'void';
	}

	/** @param list<Statement> $statements */
	private function resolveClosureReturnDocFunctionType(array $statements): ?string
	{
		if (count($statements) !== 1) {
			return null;
		}
		$statement = $statements[0] ?? null;
		if (!$statement instanceof Statement || $statement->kind !== 'return' || !is_object($statement->payload)) {
			return null;
		}
		$payload = $statement->payload;
		if (($payload->kind ?? null) !== AstKind::CLOSURE) {
			return null;
		}
		$docComment = $payload->children['docComment'] ?? null;
		if (!is_string($docComment)) {
			return null;
		}
		$inner = trim($docComment);
		if (!str_starts_with($inner, '/**') || !str_ends_with($inner, '*/')) {
			return null;
		}
		$inner = trim(substr($inner, 3, -2));
		if ($inner === '' || preg_match('/^function\s*</', $inner) !== 1) {
			return null;
		}
		return $inner;
	}

	private function readAstTypeName(mixed $typeNode): ?string
	{
		if (!is_object($typeNode)) {
			return null;
		}
		$kind = (int) ($typeNode->kind ?? 0);
		$flags = (int) ($typeNode->flags ?? 0);
		if ($kind === AstKind::NULLABLE_TYPE) {
			$inner = $this->readAstTypeName($typeNode->children['type'] ?? null);
			return $inner !== null ? '?' . ltrim($inner, '?') : null;
		}
		if ($kind === AstKind::NAME) {
			$name = (string) ($typeNode->children['name'] ?? '');
			return $name !== '' ? $name : null;
		}
		return match ($flags) {
			AstKind::TYPE_BOOL => 'bool',
			AstKind::TYPE_LONG => 'int',
			AstKind::TYPE_DOUBLE => 'float',
			AstKind::TYPE_STRING => 'string',
			AstKind::TYPE_VOID => 'void',
			AstKind::TYPE_MIXED => 'mixed',
			default => null,
		};
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
		if ($kind === AstKind::CLOSURE) {
			return $this->renderClosureExpr($expr, $namespacePhp);
		}
		if ($kind === AstKind::ARROW_FUNC) {
			return $this->renderArrowFunctionExpr($expr, $namespacePhp);
		}
		if ($kind === AstKind::ARRAY) {
			return $this->renderArrayLiteral($expr, $namespacePhp);
		}
		if ($kind === AstKind::VAR) {
			$name = (string) ($expr->children['name'] ?? 'var');
			if ($name === 'this') {
				return 'this';
			}
			if ($name !== '' && !isset($this->declaredLocals[$name])) {
				$this->errors[] = 'Variable $' . $name . ' is not visible in this block at line ' . (int) ($expr->lineno ?? 0) . '. Safe v1 uses block-local variable visibility; declare $' . $name . ' in the current block or an enclosing block before use.';
				return '/* undeclared-var-' . $name . ' */';
			}
			return $name;
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
				AstKind::BINARY_IS_IDENTICAL => 'identical(' . $left . ', ' . $right . ')',
				AstKind::BINARY_IS_NOT_IDENTICAL => 'not_identical(' . $left . ', ' . $right . ')',
				257 => '(' . $left . ' >= ' . $right . ')',
				AstKind::BINARY_COALESCE => $this->renderCoalesceExpr($leftNode, $rightNode, $namespacePhp),
				default => '/* unsupported-binary-op-' . $flags . ' */',
			};
		}
		if ($kind === AstKind::CAST) {
			$innerNode = $expr->children['expr'] ?? null;
			$inner = $this->renderExpr($innerNode, $namespacePhp);
			$flags = (int) ($expr->flags ?? 0);
			return match ($flags) {
				AstKind::TYPE_STRING => 'cast<string_t>(' . $inner . ')',
				AstKind::TYPE_LONG => 'static_cast<int_t>(' . $inner . ')',
				AstKind::TYPE_DOUBLE => 'static_cast<float_t>(' . $inner . ')',
				AstKind::TYPE_BOOL => 'static_cast<bool_t>(' . $inner . ')',
				AstKind::TYPE_OBJECT => $this->renderObjectCastExpr($innerNode, $namespacePhp),
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
			$baseExpr = $expr->children['expr'] ?? null;
			$base = $this->renderExpr($baseExpr, $namespacePhp);
			$prop = $this->cppIdentifier((string) ($expr->children['prop'] ?? 'prop'));
			return $base === 'this' ? 'this->' . $prop : $base . '->' . $prop;
		}
		if ($kind === AstKind::NULLSAFE_PROP) {
			$baseExpr = $expr->children['expr'] ?? null;
			$base = $this->renderExpr($baseExpr, $namespacePhp);
			$prop = $this->cppIdentifier((string) ($expr->children['prop'] ?? 'prop'));
			return '([&]() -> auto { auto __scpp_tmp = ' . $base . '; return static_cast<bool>(isset(__scpp_tmp)) ? __scpp_tmp->' . $prop . ' : null; }())';
		}
		if ($kind === AstKind::STATIC_PROP) {
			return $this->renderStaticPropertyAccess($expr, $namespacePhp);
		}
		if ($kind === AstKind::CLASS_CONST) {
			$class = $this->renderClassName($expr->children['class'] ?? null, $namespacePhp);
			$const = $this->cppIdentifier((string) ($expr->children['const'] ?? 'CONST'));
			return $class . '::' . $const;
		}
		if ($kind === AstKind::NEW) {
			if ($this->isStdClassNewExpr($expr)) {
				return 'mixed_t{dynamic_()}';
			}
			$class = $this->renderClassName($expr->children['class'] ?? null, $namespacePhp);
			return 'create<' . $class . '>(' . $this->renderArgs($expr->children['args']->children ?? [], $namespacePhp) . ')';
		}
		if ($kind === AstKind::STATIC_CALL) {
			$classNode = $expr->children['class'] ?? null;
			$method = (string) ($expr->children['method'] ?? '');
			$args = $expr->children['args']->children ?? [];
			$class = is_object($classNode) && ($classNode->kind ?? null) === AstKind::VAR
				? 'class_t<decltype(' . $this->renderExpr($classNode, $namespacePhp) . ')>'
				: $this->renderClassName($classNode, $namespacePhp);
			$methodDecl = $this->lookupMethodDeclByStaticCall($classNode, $method, $namespacePhp);
			$renderedArgs = $methodDecl !== null ? $this->renderCallArgsForParams($methodDecl->params, $args, $namespacePhp) : $this->renderArgs($args, $namespacePhp);
			return $class . '::' . $this->cppIdentifier($method) . '(' . $renderedArgs . ')';
		}
		if ($kind === AstKind::AST_ISSET) {
			// In this exporter, multi-argument isset() is already normalized into boolean-op trees.
			// AST_ISSET itself carries exactly one operand in `children['var']`.
			$varNode = $expr->children['var'] ?? null;
			if (is_object($varNode) && (($varNode->kind ?? null) === AstKind::DIM) && (($varNode->children['dim'] ?? null) !== null)) {
				$baseExpr = $varNode->children['expr'] ?? null;
				$baseType = $this->inferExprType($baseExpr);
				if (preg_match('/^vector_t<(.+)>$/', $baseType) !== 1) {
					$base = $this->renderExpr($baseExpr, $namespacePhp);
					$dim = $this->renderExpr($varNode->children['dim'] ?? null, $namespacePhp);
					return 'table_has_(' . $base . ', ' . $dim . ')';
				}
			}
			return 'isset(' . $this->renderExpr($varNode, $namespacePhp) . ')';
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
			return $base . '->' . $this->cppIdentifier($method) . '(' . $renderedArgs . ')';
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
		if ($kind === AstKind::THROW) {
			$this->fail('throw used as an expression is not supported yet at line ' . (int) ($expr->lineno ?? 0) . '.');
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
			$out[] = $this->renderCallArgExpr($arg, $namespacePhp);
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
			return 'class_t<decltype(' . $this->renderExpr($classNode, $namespacePhp) . ')>::' . $prop;
		}

		if (($classNode->kind ?? null) !== AstKind::NAME) {
			return '/* unsupported-static-prop */';
		}

		$name = (string) ($classNode->children['name'] ?? '');
		if (strtolower(ltrim($name, '\\')) === 'static') {
			$this->errors[] = 'static::$property is not supported in the current pass.';
			return '/* unsupported-static-late-binding */';
		}

		return $this->renderClassName($classNode, $namespacePhp) . '::' . $this->cppIdentifier($prop);
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
			return str_replace('\\', '::', $trimmed);
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
			return $cpp;
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
			if (!$this->isLvalueCapableExpr($expr, $namespacePhp)) {
				$this->errors[] = 'Reference return requires an lvalue-capable expression or a call known to return by reference.';
				return '/* unsupported-ref-return */';
			}
			return $this->renderLvalueExpr($expr, $namespacePhp);
		}

		$rendered = $this->renderExpr($expr, $namespacePhp);
		if ($expected === null) {
			return $rendered;
		}

		$exprType = $this->inferExprType($expr);
		return $this->wrapExprForExpectedType($rendered, $exprType, $expected);
	}

	private function isLvalueCapableExpr(mixed $expr, ?string $namespacePhp = null): bool
	{
		if (!is_object($expr)) {
			return false;
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::CALL) {
			$nameExpr = $expr->children['expr'] ?? null;
			$functionDecl = $this->lookupFunctionDeclByCall($nameExpr, $namespacePhp);
			return $functionDecl !== null && $functionDecl->returnsByReference;
		}

		if ($kind === AstKind::STATIC_CALL) {
			$classNode = $expr->children['class'] ?? null;
			$methodName = (string) ($expr->children['method'] ?? '');
			$methodDecl = $this->lookupMethodDeclByStaticCall($classNode, $methodName, $namespacePhp);
			return $methodDecl !== null && $methodDecl->returnsByReference;
		}

		if ($kind === AstKind::METHOD_CALL) {
			$baseExpr = $expr->children['expr'] ?? null;
			$methodName = (string) ($expr->children['method'] ?? '');
			$methodDecl = is_object($baseExpr) && ($baseExpr->kind ?? null) === AstKind::VAR && ($baseExpr->children['name'] ?? null) === 'this'
				? $this->lookupMethodDeclByCurrentClass($methodName, $namespacePhp)
				: null;
			return $methodDecl !== null && $methodDecl->returnsByReference;
		}

		return match ($kind) {
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
			AstKind::DIM => $this->renderDimWriteAccess($expr, $namespacePhp),
			AstKind::PROP, AstKind::STATIC_PROP => $this->renderAssignmentTarget($expr, $namespacePhp),
			default => $this->renderExpr($expr, $namespacePhp),
		};
	}

	private function renderReferenceBindingExpr(mixed $expr, ?string $namespacePhp): string
	{
		if ($this->isLvalueCapableExpr($expr, $namespacePhp)) {
			return $this->renderLvalueExpr($expr, $namespacePhp);
		}

		return $this->renderExpr($expr, $namespacePhp);
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
			if (str_contains($declared, 'int_t') || str_contains($declared, 'float_t') || str_contains($declared, 'bool_t') || str_contains($declared, 'string_t') || $declared === 'mixed_t' || str_starts_with($declared, 'nullable<') || str_starts_with($declared, 'shared_p<') || str_starts_with($declared, 'unique_p<') || str_starts_with($declared, 'weak_p<') || str_starts_with($declared, 'value_p<') || str_starts_with($declared, 'vector_t<') || $declared === 'hash_t' || $declared === '::scpp::hash_t' || $declared === 'hash_t<mixed_t>' || $declared === '::scpp::hash_t<mixed_t>') {
				return $declared;
			}
			return $this->typeMapper->mapDeclaredType($declared);
		}
		if ($kind === AstKind::CLASS_CONST) {
			$classNode = $expr->children['class'] ?? null;
			if (is_object($classNode) && ($classNode->kind ?? null) === AstKind::NAME) {
				$phpClass = (string) ($classNode->children['name'] ?? '');
				if ($phpClass === 'self' && $this->currentClassName !== null) {
					$phpClass = $this->currentClassName;
				}
				$classDecl = $this->classDecls[$phpClass] ?? $this->classDecls[basename(str_replace('\\', '/', $phpClass))] ?? null;
				if ($classDecl instanceof ClassDecl && $classDecl->isEnum) {
					return $this->typeMapper->mapDeclaredType($classDecl->name);
				}
			}
			return 'auto';
		}
		if ($kind === AstKind::STATIC_CALL) {
			return 'auto';
		}
		if ($kind === AstKind::NEW && $this->isStdClassNewExpr($expr)) {
			return 'mixed_t';
		}
		if ($kind === AstKind::ARRAY) {
			return 'mixed_t';
		}
		if ($kind === AstKind::CAST && ((int) ($expr->flags ?? 0) === AstKind::TYPE_OBJECT)) {
			return 'mixed_t';
		}
		if ($kind === AstKind::DIM) {
			$baseType = $this->inferExprType($expr->children['expr'] ?? null);
			if (preg_match('/^vector_t<(.+)>$/', $baseType, $matches) === 1) {
				return $matches[1];
			}
			if ($this->isUntypedTableType($baseType) || $baseType === 'mixed_t' || $baseType === 'maybe_value_t') {
				return 'mixed_t';
			}
			return 'auto';
		}

		return 'auto';
	}

private function isStdClassNameNode(mixed $classNode): bool
{
	if (!is_object($classNode) || (($classNode->kind ?? null) !== AstKind::NAME)) {
		return false;
	}

	$name = strtolower(ltrim((string) ($classNode->children['name'] ?? ''), '\\'));
	return $name === 'stdclass';
}

private function isStdClassNewExpr(mixed $expr): bool
{
	if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::NEW)) {
		return false;
	}

	$argsNode = $expr->children['args'] ?? null;
	$args = (is_object($argsNode) && isset($argsNode->children) && is_array($argsNode->children))
		? array_values($argsNode->children)
		: [];

	return $args === [] && $this->isStdClassNameNode($expr->children['class'] ?? null);
}

private function renderObjectCastExpr(mixed $expr, ?string $namespacePhp): string
{
	if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::ARRAY)) {
		$this->errors[] = 'Only (object)[...] is supported in the current pass.';
		return '/* unsupported-object-cast */';
	}

	$elements = isset($expr->children) && is_array($expr->children)
		? array_values($expr->children)
		: [];

	if ($elements === []) {
		return 'mixed_t{dynamic_()}';
	}

	$items = [];
	foreach ($elements as $element) {
		if (!is_object($element) || (($element->kind ?? null) !== AstKind::ARRAY_ELEM)) {
			$this->errors[] = 'Unsupported object-cast array element shape at line ' . (int) ($expr->lineno ?? 0) . '.';
			return '/* unsupported-object-cast */';
		}

		$valueNode = $element->children['value'] ?? null;
		if ($valueNode === null) {
			$this->errors[] = 'Array unpack and empty array elements are not supported yet at line ' . (int) ($element->lineno ?? $expr->lineno ?? 0) . '.';
			return '/* unsupported-object-cast */';
		}

		$keyNode = $element->children['key'] ?? null;
		if ($keyNode === null) {
			$items[] = 'table_item_(' . $this->renderExpr($valueNode, $namespacePhp) . ')';
			continue;
		}

		$items[] = 'table_kv_(' . $this->renderExpr($keyNode, $namespacePhp) . ', ' . $this->renderExpr($valueNode, $namespacePhp) . ')';
	}

	return 'mixed_t{dynamic_(' . implode(', ', $items) . ')}';
}

	private function renderCoalesceExpr(mixed $leftNode, mixed $rightNode, ?string $namespacePhp): string
	{
		$left = $this->renderExpr($leftNode, $namespacePhp);
		$right = $this->renderExpr($rightNode, $namespacePhp);
		$leftType = $this->inferExprType($leftNode);

		if (preg_match('/^nullable<(.+)>$/', $leftType, $matches) === 1) {
			$innerType = $matches[1];
			return '(cast<bool>(isset(' . $left . ')) ? cast<' . $innerType . '>(' . $left . ') : ' . $right . ')';
		}

		return '(cast<bool>(isset(' . $left . ')) ? ' . $left . ' : ' . $right . ')';
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
				AstKind::TYPE_OBJECT => 'mixed_t',
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
