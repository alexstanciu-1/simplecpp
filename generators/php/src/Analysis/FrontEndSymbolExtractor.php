<?php
declare(strict_types=1);

namespace Scpp\S2S\Analysis;

use Scpp\S2S\Support\AstKind;
use Scpp\S2S\Builder\IrBuilder;
use Scpp\S2S\IR\ClassDecl;
use Scpp\S2S\IR\ConstantDecl;
use Scpp\S2S\IR\FunctionDecl;
use Scpp\S2S\IR\MethodDecl;
use Scpp\S2S\IR\NamespaceBlock;
use Scpp\S2S\IR\ParamDecl;
use Scpp\S2S\IR\PhpFile;
use Scpp\S2S\IR\PropertyDecl;
use Scpp\S2S\IR\UseDecl;
use Scpp\S2S\Loader\InputLoader;

final class FrontEndSymbolExtractor
{
	public function __construct(
		private readonly InputLoader $loader = new InputLoader(),
		private readonly IrBuilder $builder = new IrBuilder(),
	)
	{
	}

	public function extract(string $path, ?string $sourceCode = null): PhpFile
	{
		return $this->builder->build($this->loader->load($path, $sourceCode));
	}

	/** @return array<string,mixed> */
	public function summarize(PhpFile $file, ?string $sourceCode = null): array
	{
		$sourceLines = $this->loadSourceLines($file->path, $sourceCode);
		$namespaces = [];
		foreach ($file->namespaces as $namespaceBlock) {
			if (!$namespaceBlock instanceof NamespaceBlock) {
				continue;
			}
			$namespaces[] = $this->summarizeNamespace($namespaceBlock, $sourceLines);
		}

		$rootFunctions = [];
		foreach ($file->functions as $function) {
			if (!$function instanceof FunctionDecl) {
				continue;
			}
			$rootFunctions[] = $this->summarizeFunction($function, null, $sourceLines);
		}
		if ($file->rootStatements !== []) {
			$rootFunctions[] = $this->summarizeExecutableStatements('__scpp_main', $file->rootStatements, null, $sourceLines);
		}

		$rootConstants = [];
		foreach ($file->constants as $constant) {
			if (!$constant instanceof ConstantDecl) {
				continue;
			}
			$rootConstants[] = $this->summarizeConstant($constant, null);
		}

		$rootClasses = [];
		foreach ($file->classes as $class) {
			if (!$class instanceof ClassDecl) {
				continue;
			}
			$rootClasses[] = $this->summarizeClass($class, null, $sourceLines);
		}

		$rootUses = [];
		foreach ($file->rootUses as $useDecl) {
			if (!$useDecl instanceof UseDecl) {
				continue;
			}
			$rootUses[] = $this->summarizeUse($useDecl);
		}

		return [
			'path' => $file->path,
			'prologue_includes' => $file->prologueIncludes,
			'root_uses' => $rootUses,
			'root_constants' => $rootConstants,
			'root_functions' => $rootFunctions,
			'root_classes' => $rootClasses,
			'namespaces' => $namespaces,
			'build_errors' => $file->buildErrors,
			'scanner_annotations' => $file->scannerAnnotations,
			'dependencies' => $this->collectDependencies($file),
		];
	}

	/** @return array<string,mixed> */
	private function summarizeNamespace(NamespaceBlock $namespaceBlock, array $sourceLines): array
	{
		$uses = [];
		foreach ($namespaceBlock->uses as $useDecl) {
			if (!$useDecl instanceof UseDecl) {
				continue;
			}
			$uses[] = $this->summarizeUse($useDecl);
		}

		$functions = [];
		foreach ($namespaceBlock->functions as $function) {
			if (!$function instanceof FunctionDecl) {
				continue;
			}
			$functions[] = $this->summarizeFunction($function, $namespaceBlock->name, $sourceLines);
		}
		if ($namespaceBlock->statements !== []) {
			$functions[] = $this->summarizeExecutableStatements('__scpp_main', $namespaceBlock->statements, $namespaceBlock->name, $sourceLines);
		}

		$constants = [];
		foreach ($namespaceBlock->constants as $constant) {
			if (!$constant instanceof ConstantDecl) {
				continue;
			}
			$constants[] = $this->summarizeConstant($constant, $namespaceBlock->name);
		}

		$classes = [];
		foreach ($namespaceBlock->classes as $class) {
			if (!$class instanceof ClassDecl) {
				continue;
			}
			$classes[] = $this->summarizeClass($class, $namespaceBlock->name, $sourceLines);
		}

		return [
			'name' => $namespaceBlock->name,
			'uses' => $uses,
			'constants' => $constants,
			'functions' => $functions,
			'classes' => $classes,
		];
	}

	/** @return array<string,mixed> */
	private function summarizeUse(UseDecl $useDecl): array
	{
		return [
			'kind' => $useDecl->kind,
			'name' => $useDecl->name,
			'alias' => $useDecl->alias,
			'line' => $useDecl->line,
			'is_grouped' => $useDecl->isGrouped,
		];
	}

	/** @return array<string,mixed> */
	private function summarizeFunction(FunctionDecl $function, ?string $namespace, array $sourceLines): array
	{
		$params = [];
		foreach ($function->params as $param) {
			if (!$param instanceof ParamDecl) {
				continue;
			}
			$params[] = $this->summarizeParam($param);
		}

		return [
			'name' => $function->name,
			'namespace' => $namespace,
			'params' => $params,
			'return_type' => $function->returnType,
			'typed_locals' => $this->summarizeTypedLocals($function->statements, $sourceLines),
			'return_chains' => $this->summarizeReturnChains($function->statements),
			'return_values' => $this->summarizeReturnValues($function->statements),
			'returns_on_all_paths' => $this->statementsReturnOnAllPaths($function->statements),
			'expression_chains' => $this->summarizeExpressionChains($function->statements),
			'property_reads' => $this->summarizePropertyReads($function->statements),
			'call_sites' => $this->summarizeCallSites($function->statements),
			'local_alias_assignments' => $this->summarizeLocalAliasAssignments($function->statements, $sourceLines),
			'local_literal_assignments' => $this->summarizeLocalLiteralAssignments($function->statements, $sourceLines),
			'local_type_assignments' => $this->summarizeLocalTypeAssignments($function->statements, $sourceLines),
			'local_constructed_assignments' => $this->summarizeLocalConstructedAssignments($function->statements, $sourceLines),
			'local_descriptor_assignments' => $this->summarizeLocalDescriptorAssignments($function->statements, $sourceLines),
			'typed_boundary_assignments' => $this->summarizeTypedBoundaryAssignments($function->statements, $sourceLines),
			'local_branch_assignments' => $this->summarizeLocalBranchAssignments($function->statements),
			'non_null_guards' => $this->summarizeNonNullGuards($function->statements),
			'non_false_guards' => $this->summarizeNonFalseGuards($function->statements),
			'foreach_locals' => $this->summarizeForeachLocals($function->statements),
			'for_loop_locals' => $this->summarizeForLoopLocals($function->statements),
			'property_assignments' => $this->summarizePropertyAssignments($function->statements),
			'static_property_assignments' => $this->summarizeStaticPropertyAssignments($function->statements),
			'property_branch_assignments' => $this->summarizePropertyBranchAssignments($function->statements),
			'static_property_reads' => $this->summarizeStaticPropertyReads($function->statements),
			'class_constant_accesses' => $this->summarizeClassConstantAccesses($function->statements),
			'local_invalidations' => $this->summarizeLocalInvalidations($function->statements, $sourceLines),
			'statement_count' => count($function->statements),
			'line' => $function->line,
			'returns_by_reference' => $function->returnsByReference,
			'is_lib_export' => $function->isLibExport,
		];
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return array<string,mixed> */
	private function summarizeExecutableStatements(string $name, array $statements, ?string $namespace, array $sourceLines): array
	{
		return [
			'name' => $name,
			'namespace' => $namespace,
			'params' => [],
			'return_type' => 'int',
			'typed_locals' => $this->summarizeTypedLocals($statements, $sourceLines),
			'return_chains' => $this->summarizeReturnChains($statements),
			'return_values' => $this->summarizeReturnValues($statements),
			'returns_on_all_paths' => $this->statementsReturnOnAllPaths($statements),
			'expression_chains' => $this->summarizeExpressionChains($statements),
			'property_reads' => $this->summarizePropertyReads($statements),
			'call_sites' => $this->summarizeCallSites($statements),
			'local_alias_assignments' => $this->summarizeLocalAliasAssignments($statements, $sourceLines),
			'local_literal_assignments' => $this->summarizeLocalLiteralAssignments($statements, $sourceLines),
			'local_type_assignments' => $this->summarizeLocalTypeAssignments($statements, $sourceLines),
			'local_constructed_assignments' => $this->summarizeLocalConstructedAssignments($statements, $sourceLines),
			'local_descriptor_assignments' => $this->summarizeLocalDescriptorAssignments($statements, $sourceLines),
			'typed_boundary_assignments' => $this->summarizeTypedBoundaryAssignments($statements, $sourceLines),
			'local_branch_assignments' => $this->summarizeLocalBranchAssignments($statements),
			'non_null_guards' => $this->summarizeNonNullGuards($statements),
			'non_false_guards' => $this->summarizeNonFalseGuards($statements),
			'foreach_locals' => $this->summarizeForeachLocals($statements),
			'for_loop_locals' => $this->summarizeForLoopLocals($statements),
			'property_assignments' => $this->summarizePropertyAssignments($statements),
			'static_property_assignments' => $this->summarizeStaticPropertyAssignments($statements),
			'property_branch_assignments' => $this->summarizePropertyBranchAssignments($statements),
			'static_property_reads' => $this->summarizeStaticPropertyReads($statements),
			'class_constant_accesses' => $this->summarizeClassConstantAccesses($statements),
			'local_invalidations' => $this->summarizeLocalInvalidations($statements, $sourceLines),
			'statement_count' => count($statements),
			'line' => 1,
			'returns_by_reference' => false,
			'is_lib_export' => false,
			'is_synthetic_entrypoint' => true,
		];
	}

	/** @return array<string,mixed> */
	private function summarizeClass(ClassDecl $class, ?string $namespace, array $sourceLines): array
	{
		$methods = [];
		foreach ($class->methods as $method) {
			if (!$method instanceof MethodDecl) {
				continue;
			}
			$params = [];
			foreach ($method->params as $param) {
				if (!$param instanceof ParamDecl) {
					continue;
				}
				$params[] = $this->summarizeParam($param);
			}
			$methods[] = [
				'name' => $method->name,
				'params' => $params,
				'return_type' => $method->returnType,
				'typed_locals' => $this->summarizeTypedLocals($method->statements, $sourceLines),
				'return_chains' => $this->summarizeReturnChains($method->statements),
				'return_values' => $this->summarizeReturnValues($method->statements),
				'returns_on_all_paths' => $this->statementsReturnOnAllPaths($method->statements),
				'expression_chains' => $this->summarizeExpressionChains($method->statements),
				'property_reads' => $this->summarizePropertyReads($method->statements),
				'call_sites' => $this->summarizeCallSites($method->statements),
				'local_alias_assignments' => $this->summarizeLocalAliasAssignments($method->statements, $sourceLines),
				'local_literal_assignments' => $this->summarizeLocalLiteralAssignments($method->statements, $sourceLines),
				'local_type_assignments' => $this->summarizeLocalTypeAssignments($method->statements, $sourceLines),
				'local_constructed_assignments' => $this->summarizeLocalConstructedAssignments($method->statements, $sourceLines),
				'local_descriptor_assignments' => $this->summarizeLocalDescriptorAssignments($method->statements, $sourceLines),
				'typed_boundary_assignments' => $this->summarizeTypedBoundaryAssignments($method->statements, $sourceLines),
				'local_branch_assignments' => $this->summarizeLocalBranchAssignments($method->statements),
				'non_null_guards' => $this->summarizeNonNullGuards($method->statements),
				'non_false_guards' => $this->summarizeNonFalseGuards($method->statements),
				'foreach_locals' => $this->summarizeForeachLocals($method->statements),
				'for_loop_locals' => $this->summarizeForLoopLocals($method->statements),
				'property_assignments' => $this->summarizePropertyAssignments($method->statements),
				'static_property_assignments' => $this->summarizeStaticPropertyAssignments($method->statements),
				'property_branch_assignments' => $this->summarizePropertyBranchAssignments($method->statements),
				'static_property_reads' => $this->summarizeStaticPropertyReads($method->statements),
				'class_constant_accesses' => $this->summarizeClassConstantAccesses($method->statements),
				'local_invalidations' => $this->summarizeLocalInvalidations($method->statements, $sourceLines),
				'statement_count' => count($method->statements),
				'line' => $method->line,
				'returns_by_reference' => $method->returnsByReference,
				'is_static' => $method->isStatic,
				'visibility' => $method->visibility,
			];
		}

		$properties = [];
		foreach ($class->properties as $property) {
			if (!$property instanceof PropertyDecl) {
				continue;
			}
			$properties[] = [
				'name' => $property->name,
				'type' => $property->type,
				'line' => $property->line,
				'is_static' => $property->isStatic,
				'has_default' => $property->hasDefault,
				'visibility' => $property->visibility,
			];
		}
		$constants = [];
		foreach ($class->constants as $constant) {
			if (!$constant instanceof ConstantDecl) {
				continue;
			}
			$constants[] = $this->summarizeConstant($constant, $namespace);
		}

		return [
			'name' => $class->name,
			'namespace' => $namespace,
			'line' => $class->line,
			'parent_class' => $class->parentClass,
			'interfaces' => $class->interfaces,
			'is_interface' => $class->isInterface,
			'is_abstract' => $class->isAbstract,
			'is_enum' => $class->isEnum,
			'is_lib_export' => $class->isLibExport,
			'methods' => $methods,
			'properties' => $properties,
			'constants' => $constants,
		];
	}

	/** @return array<string,mixed> */
	private function summarizeConstant(ConstantDecl $constant, ?string $namespace): array
	{
		return [
			'name' => $constant->name,
			'namespace' => $namespace,
			'line' => $constant->line,
			'is_lib_export' => $constant->isLibExport,
			'visibility' => $constant->visibility,
		];
	}

	/** @return list<array{kind:string,target:string,owner:?string}> */
	private function collectDependencies(PhpFile $file): array
	{
		$dependencies = [];

		foreach ($file->classes as $class) {
			if (!$class instanceof ClassDecl) {
				continue;
			}
			if (is_string($class->parentClass) && $class->parentClass !== '') {
				$dependencies[] = [
					'kind' => 'extends',
					'target' => $class->parentClass,
					'owner' => $class->name,
				];
			}
			foreach ($class->interfaces as $interface) {
				$dependencies[] = [
					'kind' => 'implements',
					'target' => $interface,
					'owner' => $class->name,
				];
			}
		}

		foreach ($file->namespaces as $namespaceBlock) {
			if (!$namespaceBlock instanceof NamespaceBlock) {
				continue;
			}
			foreach ($namespaceBlock->classes as $class) {
				if (!$class instanceof ClassDecl) {
					continue;
				}
				if (is_string($class->parentClass) && $class->parentClass !== '') {
					$dependencies[] = [
						'kind' => 'extends',
						'target' => $class->parentClass,
						'owner' => $namespaceBlock->name . '\\' . $class->name,
					];
				}
				foreach ($class->interfaces as $interface) {
					$dependencies[] = [
						'kind' => 'implements',
						'target' => $interface,
						'owner' => $namespaceBlock->name . '\\' . $class->name,
					];
				}
			}

			foreach ($namespaceBlock->uses as $useDecl) {
				if (!$useDecl instanceof UseDecl) {
					continue;
				}
				$dependencies[] = [
					'kind' => 'use',
					'target' => $useDecl->name,
					'owner' => $namespaceBlock->name,
				];
			}
		}

		foreach ($file->rootUses as $useDecl) {
			if (!$useDecl instanceof UseDecl) {
				continue;
			}
			$dependencies[] = [
				'kind' => 'use',
				'target' => $useDecl->name,
				'owner' => null,
			];
		}

		return $dependencies;
	}

	/** @return array<string,mixed> */
	private function summarizeParam(ParamDecl $param): array
	{
		return [
			'name' => $param->name,
			'type' => $param->type,
			'native_type' => $param->nativeType,
			'doc_type' => $param->docType,
			'primary_type' => $param->primaryType,
			'union_types' => $param->unionTypes,
			'is_reference' => $param->isReference,
			'is_variadic' => $param->isVariadic,
			'has_default' => $param->default !== null,
			'line' => $param->line,
		];
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeReturnChains(array $statements): array
	{
		$chains = [];
		foreach ($statements as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement || $statement->kind !== 'return') {
				continue;
			}
			$chain = $this->extractChainDescriptor($statement->payload, $statement->line);
			if ($chain !== null) {
				$chains[] = $chain;
			}
		}
		return $chains;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeReturnValues(array $statements): array
	{
		$values = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement || $statement->kind !== 'return') {
				continue;
			}
			$values[] = [
				'line' => $statement->line,
				'descriptor' => $this->describeExpression($statement->payload, $statement->line),
				'direct_call_name' => $this->extractDirectFunctionCallName($statement->payload),
			];
		}
		return $values;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeExpressionChains(array $statements): array
	{
		$chains = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement) {
				continue;
			}

			if ($statement->kind === 'expr') {
				$chain = $this->extractChainDescriptor($statement->payload, $statement->line);
				if ($chain !== null) {
					$chain['statement_kind'] = 'expr';
					$chains[] = $chain;
				}
				continue;
			}

			if ($statement->kind === 'assign' || $statement->kind === 'assign_ref' || $statement->kind === 'assign_op') {
				$payload = $statement->payload;
				if (!is_array($payload)) {
					continue;
				}
				$exprNode = $payload['expr'] ?? null;
				$chain = $this->extractChainDescriptor($exprNode, $statement->line);
				if ($chain !== null) {
					$chain['statement_kind'] = $statement->kind;
					$assignVar = $this->extractAssignedVariableName($payload['var'] ?? null);
					if ($assignVar !== null) {
						$chain['assigned_var'] = $assignVar;
					}
					$chains[] = $chain;
				}
			}
		}
		return $chains;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeLocalAliasAssignments(array $statements): array
	{
		$aliases = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement) {
				continue;
			}
			if ($statement->kind !== 'assign') {
				continue;
			}
			$payload = $statement->payload;
			if (!is_array($payload)) {
				continue;
			}
			$target = $this->extractAssignedVariableName($payload['var'] ?? null);
			$source = $this->extractAssignedVariableName($payload['expr'] ?? null);
			if ($target === null || $source === null) {
				continue;
			}
			$aliases[] = [
				'line' => $statement->line,
				'target' => $target,
				'source' => $source,
				'statement_kind' => 'assign',
			];
		}
		return $aliases;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeTypedLocals(array $statements, array $sourceLines): array
	{
		$locals = [];
		$seen = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement || $statement->kind !== 'declare_local') {
				continue;
			}
			$payload = $statement->payload;
			if (!is_array($payload)) {
				continue;
			}
			$name = isset($payload['name']) && is_string($payload['name']) ? $payload['name'] : null;
			$type = isset($payload['type']) && is_string($payload['type']) ? $payload['type'] : null;
			if ($name === null || $name === '' || $type === null || $type === '') {
				continue;
			}
			$key = $statement->line . '|' . $name;
			$seen[$key] = true;
			$locals[] = [
				'line' => $statement->line,
				'name' => $name,
				'type' => $type,
				'is_initialized' => false,
			];
		}
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement || $statement->kind !== 'assign') {
				continue;
			}
			$payload = $statement->payload;
			if (!is_array($payload)) {
				continue;
			}
			$name = $this->extractAssignedVariableName($payload['var'] ?? null);
			if ($name === null) {
				continue;
			}
			$type = $this->extractInlineTypedAssignmentType($statement->line, $name, $sourceLines);
			if ($type === null) {
				continue;
			}
			$key = $statement->line . '|' . $name;
			if (isset($seen[$key])) {
				continue;
			}
			$literalIntValue = $this->inferIntegerLiteralValue($payload['expr'] ?? null);
			$seen[$key] = true;
			$local = [
				'line' => $statement->line,
				'name' => $name,
				'type' => $type,
				'is_initialized' => true,
			];
			if ($literalIntValue !== null) {
				$local['literal_int_value'] = $literalIntValue;
			}
			$locals[] = $local;
		}
		return $locals;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeLocalInvalidations(array $statements, array $sourceLines): array
	{
		$invalidations = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement) {
				continue;
			}
			if ($statement->kind !== 'assign' && $statement->kind !== 'assign_ref' && $statement->kind !== 'assign_op') {
				continue;
			}
			$payload = $statement->payload;
			if (!is_array($payload)) {
				continue;
			}
			$target = $this->extractAssignedVariableName($payload['var'] ?? null);
			if ($target === null) {
				continue;
			}
			if ($this->extractInlineTypedAssignmentType($statement->line, $target, $sourceLines) !== null) {
				continue;
			}
			$expr = $payload['expr'] ?? null;
			if ($this->extractAssignedVariableName($expr) !== null) {
				continue;
			}
			if ($this->extractChainDescriptor($expr, $statement->line) !== null) {
				continue;
			}
			$descriptor = $this->describeExpression($expr, $statement->line);
			if (($descriptor['kind'] ?? 'unknown') !== 'unknown') {
				continue;
			}
			if ($this->inferLiteralType($expr) !== null) {
				continue;
			}
			if ($this->inferConstructedClassType($expr) !== null) {
				continue;
			}
			if ($this->inferSimpleExpressionType($expr) !== null) {
				continue;
			}
			$invalidations[] = [
				'line' => $statement->line,
				'name' => $target,
				'statement_kind' => $statement->kind,
			];
		}
		return $invalidations;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeLocalLiteralAssignments(array $statements, array $sourceLines): array
	{
		$literals = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement || $statement->kind !== 'assign') {
				continue;
			}
			$payload = $statement->payload;
			if (!is_array($payload)) {
				continue;
			}
			$target = $this->extractAssignedVariableName($payload['var'] ?? null);
			if ($target === null) {
				continue;
			}
			if ($this->extractInlineTypedAssignmentType($statement->line, $target, $sourceLines) !== null) {
				continue;
			}
			$type = $this->inferLiteralType($payload['expr'] ?? null);
			if ($type === null) {
				continue;
			}
			$literals[] = [
				'line' => $statement->line,
				'name' => $target,
				'type' => $type,
				'statement_kind' => 'assign',
			];
		}
		return $literals;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeLocalTypeAssignments(array $statements, array $sourceLines): array
	{
		$assignments = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement || $statement->kind !== 'assign') {
				continue;
			}
			$payload = $statement->payload;
			if (!is_array($payload)) {
				continue;
			}
			$target = $this->extractAssignedVariableName($payload['var'] ?? null);
			if ($target === null) {
				continue;
			}
			if ($this->extractInlineTypedAssignmentType($statement->line, $target, $sourceLines) !== null) {
				continue;
			}
			$type = $this->inferSimpleExpressionType($payload['expr'] ?? null);
			if ($type === null) {
				continue;
			}
			$assignments[] = [
				'line' => $statement->line,
				'name' => $target,
				'type' => $type,
				'statement_kind' => 'assign',
			];
		}
		return $assignments;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeLocalConstructedAssignments(array $statements, array $sourceLines): array
	{
		$constructed = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement || $statement->kind !== 'assign') {
				continue;
			}
			$payload = $statement->payload;
			if (!is_array($payload)) {
				continue;
			}
			$target = $this->extractAssignedVariableName($payload['var'] ?? null);
			if ($target === null) {
				continue;
			}
			if ($this->extractInlineTypedAssignmentType($statement->line, $target, $sourceLines) !== null) {
				continue;
			}
			$type = $this->inferConstructedClassType($payload['expr'] ?? null);
			if ($type === null) {
				continue;
			}
			$constructed[] = [
				'line' => $statement->line,
				'name' => $target,
				'type' => $type,
				'statement_kind' => 'assign',
			];
		}
		return $constructed;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeLocalDescriptorAssignments(array $statements, array $sourceLines): array
	{
		$assignments = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement || $statement->kind !== 'assign') {
				continue;
			}
			$payload = $statement->payload;
			if (!is_array($payload)) {
				continue;
			}
			$target = $this->extractAssignedVariableName($payload['var'] ?? null);
			if ($target === null) {
				continue;
			}
			if ($this->extractInlineTypedAssignmentType($statement->line, $target, $sourceLines) !== null) {
				continue;
			}
			$descriptor = $this->describeExpression($payload['expr'] ?? null, $statement->line);
			$kind = (string) ($descriptor['kind'] ?? 'unknown');
			if (in_array($kind, ['unknown', 'alias', 'chain', 'type'], true)) {
				continue;
			}
			$assignments[] = [
				'line' => $statement->line,
				'name' => $target,
				'descriptor' => $descriptor,
				'statement_kind' => 'assign',
			];
		}
		return $assignments;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeTypedBoundaryAssignments(array $statements, array $sourceLines): array
	{
		$assignments = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement || $statement->kind !== 'assign') {
				continue;
			}
			$payload = $statement->payload;
			if (!is_array($payload)) {
				continue;
			}
			$target = $this->extractAssignedVariableName($payload['var'] ?? null);
			if ($target === null) {
				continue;
			}
			$targetType = $this->extractInlineTypedAssignmentType($statement->line, $target, $sourceLines);
			if ($targetType === null) {
				continue;
			}
			$descriptor = $this->describeExpression($payload['expr'] ?? null, $statement->line);
			if (($descriptor['kind'] ?? 'unknown') === 'unknown') {
				continue;
			}
			$assignments[] = [
				'line' => $statement->line,
				'name' => $target,
				'target_type' => $targetType,
				'descriptor' => $descriptor,
				'statement_kind' => 'assign',
			];
		}
		return $assignments;
	}

	/** @return array<string,mixed>|null */
	private function extractChainDescriptor(mixed $node, int $line): ?array
	{
		if (!is_object($node) || !isset($node->kind, $node->children) || !is_array($node->children)) {
			return null;
		}

		$segments = [];
		$current = $node;
		while (is_object($current) && isset($current->kind, $current->children) && is_array($current->children)) {
			$kind = $current->kind;
			if ($kind === AstKind::PROP || $kind === AstKind::NULLSAFE_PROP) {
				$prop = $current->children['prop'] ?? null;
				if (!is_string($prop) || $prop === '') {
					return null;
				}
				array_unshift($segments, ['kind' => 'property', 'name' => $prop]);
				$current = $current->children['expr'] ?? null;
				continue;
			}
			if ($kind === AstKind::METHOD_CALL) {
				$method = $current->children['method'] ?? null;
				if (!is_string($method) || $method === '') {
					return null;
				}
				array_unshift($segments, ['kind' => 'method', 'name' => $method]);
				$current = $current->children['expr'] ?? null;
				continue;
			}
			if ($kind === AstKind::VAR) {
				$name = $current->children['name'] ?? null;
				if (!is_string($name) || $name === '') {
					return null;
				}
				return [
					'line' => $line,
					'root_kind' => 'variable',
					'root_name' => $name,
					'segments' => $segments,
				];
			}
			if ($kind === AstKind::CALL) {
				$callee = $current->children['expr'] ?? null;
				if (!is_object($callee) || !isset($callee->kind, $callee->children) || !is_array($callee->children)) {
					return null;
				}
				if ($callee->kind !== AstKind::NAME) {
					return null;
				}
				$name = $callee->children['name'] ?? null;
				if (!is_string($name) || $name === '') {
					return null;
				}
				return [
					'line' => $line,
					'root_kind' => 'function_call',
					'root_name' => $name,
					'segments' => $segments,
				];
			}
			if ($kind === AstKind::STATIC_CALL) {
				$classNode = $current->children['class'] ?? null;
				if (!is_object($classNode) || !isset($classNode->kind, $classNode->children) || !is_array($classNode->children) || $classNode->kind !== AstKind::NAME) {
					return null;
				}
				$className = trim((string) ($classNode->children['name'] ?? ''));
				$method = $current->children['method'] ?? null;
				if ($className === '' || !is_string($method) || $method === '') {
					return null;
				}
				return [
					'line' => $line,
					'root_kind' => 'static_call',
					'root_name' => $className . '::' . $method,
					'root_class' => ltrim($className, '\\'),
					'root_method' => $method,
					'segments' => $segments,
				];
			}
			return null;
		}

		return null;
	}

	private function extractAssignedVariableName(mixed $node): ?string
	{
		if (!is_object($node) || !isset($node->kind, $node->children) || !is_array($node->children)) {
			return null;
		}
		if ($node->kind !== AstKind::VAR) {
			return null;
		}
		$name = $node->children['name'] ?? null;
		return is_string($name) && $name !== '' ? $name : null;
	}

	private function inferLiteralType(mixed $expr): ?string
	{
		if ($expr === null) {
			return 'null';
		}
		if (is_object($expr) && isset($expr->kind, $expr->children) && is_array($expr->children) && $expr->kind === AstKind::CONST) {
			$nameNode = $expr->children['name'] ?? null;
			if (is_object($nameNode) && isset($nameNode->kind, $nameNode->children) && is_array($nameNode->children) && $nameNode->kind === AstKind::NAME) {
				$name = strtolower(trim((string) ($nameNode->children['name'] ?? '')));
				if ($name === 'null') {
					return 'null';
				}
				if ($name === 'true' || $name === 'false') {
					return 'bool';
				}
			}
		}
		if (is_object($expr) && isset($expr->kind, $expr->children) && is_array($expr->children) && $expr->kind === AstKind::NAME) {
			$name = strtolower(trim((string) ($expr->children['name'] ?? '')));
			if ($name === 'null') {
				return 'null';
			}
			if ($name === 'true' || $name === 'false') {
				return 'bool';
			}
		}
		if (is_bool($expr)) {
			return 'bool';
		}
		if (is_int($expr)) {
			return 'int';
		}
		if (is_float($expr)) {
			return 'float';
		}
		if (is_string($expr)) {
			return 'string';
		}
		return null;
	}

	private function inferIntegerLiteralValue(mixed $expr): ?int
	{
		if (is_int($expr)) {
			return $expr;
		}
		if (!is_object($expr) || !isset($expr->kind, $expr->children) || !is_array($expr->children)) {
			return null;
		}
		if ($expr->kind !== AstKind::UNARY_OP) {
			return null;
		}
		$inner = $this->inferIntegerLiteralValue($expr->children['expr'] ?? null);
		if ($inner === null) {
			return null;
		}
		$flag = (int) ($expr->flags ?? 0);
		if ($flag === AstKind::UNARY_PLUS) {
			return $inner;
		}
		if ($flag === AstKind::UNARY_MINUS) {
			return -$inner;
		}
		return null;
	}

	private function inferConstructedClassType(mixed $expr): ?string
	{
		if (!is_object($expr) || !isset($expr->kind, $expr->children) || !is_array($expr->children)) {
			return null;
		}
		if ($expr->kind !== AstKind::NEW) {
			return null;
		}

		$classNode = $expr->children['class'] ?? null;
		if (!is_object($classNode) || !isset($classNode->kind, $classNode->children) || !is_array($classNode->children)) {
			return null;
		}
		if ($classNode->kind !== AstKind::NAME) {
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

	private function inferSimpleExpressionType(mixed $expr): ?string
	{
		$literal = $this->inferLiteralType($expr);
		if ($literal !== null) {
			return $literal;
		}
		$constructed = $this->inferConstructedClassType($expr);
		if ($constructed !== null) {
			return $constructed;
		}
		$cast = $this->describeCastExpression($expr);
		if (is_array($cast) && (($cast['kind'] ?? '') === 'type')) {
			$type = (string) ($cast['type'] ?? '');
			if ($type !== '') {
				return $type;
			}
		}
		$boolean = $this->describeBooleanExpression($expr);
		if (is_array($boolean) && (($boolean['kind'] ?? '') === 'type')) {
			$type = (string) ($boolean['type'] ?? '');
			if ($type !== '') {
				return $type;
			}
		}
		if (!is_object($expr) || !isset($expr->kind, $expr->children) || !is_array($expr->children)) {
			return null;
		}
		if ($expr->kind === AstKind::BINARY_OP) {
			$flag = (int) ($expr->flags ?? 0);
			if ($flag === AstKind::BINARY_CONCAT) {
				return 'string';
			}
		}
		return null;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeLocalBranchAssignments(array $statements): array
	{
		$events = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement || $statement->kind !== 'if' || !is_array($statement->payload)) {
				continue;
			}

			$clauses = $statement->payload;
			$hasElse = false;
			$branchMaps = [];
			$branchTerminates = [];
			foreach ($clauses as $clause) {
				if (!is_array($clause)) {
					continue;
				}
				if (($clause['cond'] ?? null) === null) {
					$hasElse = true;
				}
				$branchStatements = is_array($clause['stmts'] ?? null) ? $clause['stmts'] : [];
				$branchMaps[] = $this->summarizeBranchAssignmentMap($branchStatements, $statement->line);
				$branchTerminates[] = $this->branchAlwaysTerminates($branchStatements);
			}

			$targets = [];
			foreach ($branchMaps as $branchMap) {
				foreach ($branchMap as $target => $_descriptor) {
					$targets[$target] = true;
				}
			}

			foreach (array_keys($targets) as $target) {
				$branches = [];
				$supported = true;
				$coveredBranchCount = 0;
				foreach ($branchMaps as $branchIndex => $branchMap) {
					if (!array_key_exists($target, $branchMap)) {
						if (($branchTerminates[$branchIndex] ?? false) === true) {
							$coveredBranchCount++;
						}
						continue;
					}
					$descriptor = $branchMap[$target];
					if (!is_array($descriptor)) {
						$supported = false;
						break;
					}
					$branches[] = $descriptor;
					$coveredBranchCount++;
				}
				if (!$supported || $branches === []) {
					continue;
				}
				$eventLine = $this->findBranchMergeLine($clauses, $statement->line);
				$events[] = [
					'line' => $eventLine,
					'name' => $target,
					'branches' => $branches,
					'branch_count' => count($branchMaps),
					'covered_branch_count' => $coveredBranchCount,
					'has_fallthrough' => !$hasElse,
					'statement_kind' => 'if',
				];
			}
		}
		return $events;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizePropertyBranchAssignments(array $statements): array
	{
		$events = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement || $statement->kind !== 'if' || !is_array($statement->payload)) {
				continue;
			}

			$clauses = $statement->payload;
			$hasElse = false;
			$branchMaps = [];
			$branchTerminates = [];
			foreach ($clauses as $clause) {
				if (!is_array($clause)) {
					continue;
				}
				if (($clause['cond'] ?? null) === null) {
					$hasElse = true;
				}
				$branchStatements = is_array($clause['stmts'] ?? null) ? $clause['stmts'] : [];
				$branchMaps[] = $this->summarizeBranchPropertyAssignmentMap($branchStatements, $statement->line);
				$branchTerminates[] = $this->branchAlwaysTerminates($branchStatements);
			}

			$targets = [];
			foreach ($branchMaps as $branchMap) {
				foreach ($branchMap as $target => $_descriptor) {
					$targets[$target] = true;
				}
			}

			foreach (array_keys($targets) as $target) {
				$branches = [];
				$supported = true;
				$coveredBranchCount = 0;
				foreach ($branchMaps as $branchIndex => $branchMap) {
					if (!array_key_exists($target, $branchMap)) {
						if (($branchTerminates[$branchIndex] ?? false) === true) {
							$coveredBranchCount++;
						}
						continue;
					}
					$descriptor = $branchMap[$target];
					if (!is_array($descriptor)) {
						$supported = false;
						break;
					}
					$branches[] = $descriptor;
					$coveredBranchCount++;
				}
				if (!$supported || $branches === []) {
					continue;
				}
				$eventLine = $this->findBranchMergeLine($clauses, $statement->line);
				$events[] = [
					'line' => $eventLine,
					'property_name' => $target,
					'branches' => $branches,
					'branch_count' => count($branchMaps),
					'covered_branch_count' => $coveredBranchCount,
					'has_fallthrough' => !$hasElse,
					'statement_kind' => 'if',
				];
			}
		}
		return $events;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizePropertyAssignments(array $statements): array
	{
		$assignments = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement || $statement->kind !== 'assign') {
				continue;
			}
			$payload = $statement->payload;
			if (!is_array($payload)) {
				continue;
			}
			$targetChain = $this->extractChainDescriptor($payload['var'] ?? null, $statement->line);
			if ($targetChain === null) {
				continue;
			}
			$segments = $targetChain['segments'] ?? [];
			if (!is_array($segments) || $segments === []) {
				continue;
			}
			$lastSegment = $segments[count($segments) - 1] ?? null;
			if (!is_array($lastSegment) || (($lastSegment['kind'] ?? '') !== 'property')) {
				continue;
			}

			$assignment = [
				'line' => $statement->line,
				'target_chain' => $targetChain,
				'statement_kind' => 'assign',
			];

			$expr = $payload['expr'] ?? null;
			$alias = $this->extractAssignedVariableName($expr);
			if ($alias !== null) {
				$assignment['source'] = ['kind' => 'alias', 'source' => $alias];
				$assignments[] = $assignment;
				continue;
			}

			$chain = $this->extractChainDescriptor($expr, $statement->line);
			if ($chain !== null) {
				$assignment['source'] = ['kind' => 'chain', 'chain' => $chain];
				$assignments[] = $assignment;
				continue;
			}

			$type = $this->inferLiteralType($expr);
			if ($type !== null) {
				$assignment['source'] = ['kind' => 'type', 'type' => $type];
				$assignments[] = $assignment;
				continue;
			}

			$type = $this->inferConstructedClassType($expr);
			if ($type !== null) {
				$assignment['source'] = ['kind' => 'type', 'type' => $type];
				$assignments[] = $assignment;
			}
		}
		return $assignments;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeStaticPropertyAssignments(array $statements): array
	{
		$assignments = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement || $statement->kind !== 'assign') {
				continue;
			}
			$payload = $statement->payload;
			if (!is_array($payload)) {
				continue;
			}
			$access = $this->describeStaticPropertyAccess($payload['var'] ?? null, $statement->line);
			if ($access === null) {
				continue;
			}
			$access['statement_kind'] = 'assign';
			$assignments[] = $access;
		}
		return $assignments;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeStaticPropertyReads(array $statements): array
	{
		$reads = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement) {
				continue;
			}
			$expr = match ($statement->kind) {
				'assign', 'assign_ref', 'assign_op' => is_array($statement->payload) ? ($statement->payload['expr'] ?? null) : null,
				'expr', 'return', 'throw', 'echo' => $statement->payload,
				default => null,
			};
			$this->collectStaticPropertyReadsFromNode($expr, $statement->line, $reads, $statement->kind);
		}
		return $reads;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeClassConstantAccesses(array $statements): array
	{
		$accesses = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement) {
				continue;
			}
			$expr = match ($statement->kind) {
				'assign', 'assign_ref', 'assign_op' => is_array($statement->payload) ? ($statement->payload['expr'] ?? null) : null,
				'expr', 'return', 'throw', 'echo' => $statement->payload,
				default => null,
			};
			$this->collectClassConstantAccessesFromNode($expr, $statement->line, $accesses, $statement->kind);
		}
		return $accesses;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeCallSites(array $statements): array
	{
		$calls = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement) {
				continue;
			}
			$this->collectCallSitesFromNode(match ($statement->kind) {
				'assign', 'assign_ref', 'assign_op' => is_array($statement->payload) ? ($statement->payload['expr'] ?? null) : null,
				'expr', 'return', 'throw', 'echo' => $statement->payload,
				default => null,
			}, $statement->line, $calls, $statement->kind);
		}
		return $calls;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizePropertyReads(array $statements): array
	{
		$reads = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement) {
				continue;
			}
			$this->collectPropertyReadsFromNode(match ($statement->kind) {
				'assign', 'assign_ref', 'assign_op' => is_array($statement->payload) ? ($statement->payload['expr'] ?? null) : null,
				'expr', 'return', 'throw', 'echo' => $statement->payload,
				default => null,
			}, $statement->line, $reads, $statement->kind);
		}
		return $reads;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeNonNullGuards(array $statements): array
	{
		$guards = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement || $statement->kind !== 'if' || !is_array($statement->payload)) {
				continue;
			}
			$clauses = $statement->payload;
			if (count($clauses) !== 1 || !is_array($clauses[0] ?? null)) {
				continue;
			}
			$clause = $clauses[0];
			$guard = $this->extractNullGuardName($clause['cond'] ?? null);
			if ($guard === null) {
				continue;
			}
			$branchStatements = is_array($clause['stmts'] ?? null) ? $clause['stmts'] : [];
			if (!$this->branchAlwaysTerminates($branchStatements)) {
				continue;
			}
			$guards[] = [
				'line' => $this->findBranchMergeLine($clauses, $statement->line),
				'name' => $guard,
			];
		}
		return $guards;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeNonFalseGuards(array $statements): array
	{
		$guards = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement || $statement->kind !== 'if' || !is_array($statement->payload)) {
				continue;
			}
			$clauses = $statement->payload;
			if (count($clauses) !== 1 || !is_array($clauses[0] ?? null)) {
				continue;
			}
			$clause = $clauses[0];
			$guard = $this->extractFalseGuardName($clause['cond'] ?? null);
			if ($guard === null) {
				continue;
			}
			$branchStatements = is_array($clause['stmts'] ?? null) ? $clause['stmts'] : [];
			if (!$this->branchAlwaysTerminates($branchStatements)) {
				continue;
			}
			$guards[] = [
				'line' => $this->findBranchMergeLine($clauses, $statement->line),
				'name' => $guard,
			];
		}
		return $guards;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeForeachLocals(array $statements): array
	{
		$locals = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement || $statement->kind !== 'foreach' || !is_array($statement->payload)) {
				continue;
			}
			$valueName = $this->extractAssignedVariableName($statement->payload['value'] ?? null);
			$keyName = $this->extractAssignedVariableName($statement->payload['key'] ?? null);
			$source = $this->describeExpression($statement->payload['expr'] ?? null, $statement->line);
			if ($valueName !== null) {
				$locals[] = [
					'line' => $statement->line,
					'name' => $valueName,
					'role' => 'value',
					'source' => $source,
				];
			}
			if ($keyName !== null) {
				$locals[] = [
					'line' => $statement->line,
					'name' => $keyName,
					'role' => 'key',
					'source' => $source,
				];
			}
		}
		return $locals;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<array<string,mixed>> */
	private function summarizeForLoopLocals(array $statements): array
	{
		$locals = [];
		foreach ($this->flattenStatements($statements) as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement || $statement->kind !== 'for' || !is_array($statement->payload)) {
				continue;
			}
			$initNodes = is_array($statement->payload['init'] ?? null) ? $statement->payload['init'] : [];
			foreach ($initNodes as $initNode) {
				$local = $this->describeForLoopInitLocal($initNode, $statement->line);
				if ($local === null) {
					continue;
				}
				$locals[] = $local;
			}
		}
		return $locals;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements @return list<\Scpp\S2S\IR\Statement> */
	private function flattenStatements(array $statements): array
	{
		$flat = [];
		foreach ($statements as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement) {
				continue;
			}
			$flat[] = $statement;
			if ($statement->kind === 'if' && is_array($statement->payload)) {
				foreach ($statement->payload as $branch) {
					if (is_array($branch) && is_array($branch['stmts'] ?? null)) {
						$flat = array_merge($flat, $this->flattenStatements($branch['stmts']));
					}
				}
				continue;
			}
			if (in_array($statement->kind, ['while', 'do_while', 'foreach'], true) && is_array($statement->payload) && is_array($statement->payload['stmts'] ?? null)) {
				$flat = array_merge($flat, $this->flattenStatements($statement->payload['stmts']));
				continue;
			}
			if ($statement->kind === 'for' && is_array($statement->payload) && is_array($statement->payload['stmts'] ?? null)) {
				$flat = array_merge($flat, $this->flattenStatements($statement->payload['stmts']));
				continue;
			}
			if ($statement->kind === 'switch' && is_array($statement->payload)) {
				foreach (($statement->payload['cases'] ?? []) as $case) {
					if (is_array($case) && is_array($case['stmts'] ?? null)) {
						$flat = array_merge($flat, $this->flattenStatements($case['stmts']));
					}
				}
				continue;
			}
			if ($statement->kind === 'try' && is_array($statement->payload)) {
				foreach (['try', 'finally'] as $bucket) {
					if (is_array($statement->payload[$bucket] ?? null)) {
						$flat = array_merge($flat, $this->flattenStatements($statement->payload[$bucket]));
					}
				}
				foreach (($statement->payload['catches'] ?? []) as $catch) {
					if (is_array($catch) && is_array($catch['stmts'] ?? null)) {
						$flat = array_merge($flat, $this->flattenStatements($catch['stmts']));
					}
				}
			}
		}
		return $flat;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $statements */
	private function statementsReturnOnAllPaths(array $statements): bool
	{
		foreach ($statements as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement) {
				continue;
			}
			if ($statement->kind === 'return') {
				return true;
			}
			if ($statement->kind === 'if' && is_array($statement->payload) && $this->ifStatementReturnsOnAllPaths($statement->payload)) {
				return true;
			}
		}
		return false;
	}

	/** @param list<array<string,mixed>> $branches */
	private function ifStatementReturnsOnAllPaths(array $branches): bool
	{
		if ($branches === []) {
			return false;
		}
		$hasElse = false;
		foreach ($branches as $branch) {
			if (!is_array($branch) || !array_key_exists('cond', $branch) || !is_array($branch['stmts'] ?? null)) {
				return false;
			}
			if ($branch['cond'] === null) {
				$hasElse = true;
			}
			if (!$this->statementsReturnOnAllPaths($branch['stmts'])) {
				return false;
			}
		}
		return $hasElse;
	}

	/** @param list<array<string,mixed>> $calls */
	private function collectCallSitesFromNode(mixed $node, int $line, array &$calls, string $statementKind): void
	{
		if (!is_object($node) || !isset($node->kind, $node->children) || !is_array($node->children)) {
			return;
		}

		if ($node->kind === AstKind::CALL) {
			$callee = $node->children['expr'] ?? null;
			if (is_object($callee) && isset($callee->kind, $callee->children) && is_array($callee->children) && $callee->kind === AstKind::NAME) {
				$name = (string) ($callee->children['name'] ?? '');
				if ($name !== '') {
					$calls[] = [
						'line' => $line,
						'statement_kind' => $statementKind,
						'call_kind' => 'function',
						'name' => $name,
						'args' => $this->describeArgs($node->children['args'] ?? null, $line),
					];
				}
			}
		} elseif ($node->kind === AstKind::STATIC_CALL) {
			$classNode = $node->children['class'] ?? null;
			$method = (string) ($node->children['method'] ?? '');
			if (is_object($classNode) && isset($classNode->kind, $classNode->children) && is_array($classNode->children) && $classNode->kind === AstKind::NAME && $method !== '') {
				$className = trim((string) ($classNode->children['name'] ?? ''));
				if ($className !== '') {
					$calls[] = [
						'line' => $line,
						'statement_kind' => $statementKind,
						'call_kind' => 'static_method',
						'class_name' => ltrim($className, '\\'),
						'method_name' => $method,
						'args' => $this->describeArgs($node->children['args'] ?? null, $line),
					];
				}
			}
		} elseif ($node->kind === AstKind::METHOD_CALL) {
			$method = (string) ($node->children['method'] ?? '');
			if ($method !== '') {
				$calls[] = [
					'line' => $line,
					'statement_kind' => $statementKind,
					'call_kind' => 'method',
					'method_name' => $method,
					'receiver' => $this->describeExpression($node->children['expr'] ?? null, $line),
					'args' => $this->describeArgs($node->children['args'] ?? null, $line),
				];
			}
		}

		foreach ($node->children as $child) {
			if (is_array($child)) {
				foreach ($child as $nested) {
					$this->collectCallSitesFromNode($nested, $line, $calls, $statementKind);
				}
				continue;
			}
			$this->collectCallSitesFromNode($child, $line, $calls, $statementKind);
		}
	}

	/** @param list<array<string,mixed>> $reads */
	private function collectPropertyReadsFromNode(mixed $node, int $line, array &$reads, string $statementKind): void
	{
		if (!is_object($node) || !isset($node->kind, $node->children) || !is_array($node->children)) {
			return;
		}

		if ($node->kind === AstKind::PROP || $node->kind === AstKind::NULLSAFE_PROP) {
			$chain = $this->extractChainDescriptor($node, $line);
			if ($chain !== null && (($chain['segments'] ?? []) !== [])) {
				$reads[] = [
					'line' => $line,
					'statement_kind' => $statementKind,
					'chain' => $chain,
				];
			}
		}

		foreach ($node->children as $child) {
			if (is_array($child)) {
				foreach ($child as $nested) {
					$this->collectPropertyReadsFromNode($nested, $line, $reads, $statementKind);
				}
				continue;
			}
			$this->collectPropertyReadsFromNode($child, $line, $reads, $statementKind);
		}
	}

	/** @param list<array<string,mixed>> $reads */
	private function collectStaticPropertyReadsFromNode(mixed $node, int $line, array &$reads, string $statementKind): void
	{
		if (!is_object($node) || !isset($node->kind, $node->children) || !is_array($node->children)) {
			return;
		}

		$access = $this->describeStaticPropertyAccess($node, $line);
		if ($access !== null) {
			$access['statement_kind'] = $statementKind;
			$reads[] = $access;
		}

		foreach ($node->children as $child) {
			if (is_array($child)) {
				foreach ($child as $nested) {
					$this->collectStaticPropertyReadsFromNode($nested, $line, $reads, $statementKind);
				}
				continue;
			}
			$this->collectStaticPropertyReadsFromNode($child, $line, $reads, $statementKind);
		}
	}

	/** @param list<array<string,mixed>> $accesses */
	private function collectClassConstantAccessesFromNode(mixed $node, int $line, array &$accesses, string $statementKind): void
	{
		if (!is_object($node) || !isset($node->kind, $node->children) || !is_array($node->children)) {
			return;
		}

		$access = $this->describeClassConstantAccess($node, $line);
		if ($access !== null) {
			$access['statement_kind'] = $statementKind;
			$accesses[] = $access;
		}

		foreach ($node->children as $child) {
			if (is_array($child)) {
				foreach ($child as $nested) {
					$this->collectClassConstantAccessesFromNode($nested, $line, $accesses, $statementKind);
				}
				continue;
			}
			$this->collectClassConstantAccessesFromNode($child, $line, $accesses, $statementKind);
		}
	}

	/** @return array{line:int,class_name:string,property_name:string}|null */
	private function describeStaticPropertyAccess(mixed $node, int $line): ?array
	{
		if (!is_object($node) || !isset($node->kind, $node->children) || !is_array($node->children) || $node->kind !== AstKind::STATIC_PROP) {
			return null;
		}
		$classNode = $node->children['class'] ?? null;
		if (!is_object($classNode) || ($classNode->kind ?? null) !== AstKind::NAME) {
			return null;
		}
		$className = trim((string) ($classNode->children['name'] ?? ''));
		$propertyName = trim((string) ($node->children['prop'] ?? ''));
		if ($className === '' || $propertyName === '') {
			return null;
		}
		return [
			'line' => $line,
			'class_name' => ltrim($className, '\\'),
			'property_name' => $propertyName,
		];
	}

	/** @return array{line:int,class_name:string,constant_name:string}|null */
	private function describeClassConstantAccess(mixed $node, int $line): ?array
	{
		if (!is_object($node) || !isset($node->kind, $node->children) || !is_array($node->children) || $node->kind !== AstKind::CLASS_CONST) {
			return null;
		}
		$classNode = $node->children['class'] ?? null;
		if (!is_object($classNode) || ($classNode->kind ?? null) !== AstKind::NAME) {
			return null;
		}
		$className = trim((string) ($classNode->children['name'] ?? ''));
		$constantName = trim((string) ($node->children['const'] ?? ''));
		if ($className === '' || $constantName === '') {
			return null;
		}
		return [
			'line' => $line,
			'class_name' => ltrim($className, '\\'),
			'constant_name' => $constantName,
		];
	}

	/** @return list<array<string,mixed>> */
	private function describeArgs(mixed $argsNode, int $line): array
	{
		$args = [];
		$children = is_object($argsNode) && isset($argsNode->children) && is_array($argsNode->children)
			? array_values($argsNode->children)
			: [];
		foreach ($children as $child) {
			$args[] = $this->describeExpression($child, $line);
		}
		return $args;
	}

	/** @return array<string,mixed> */
	private function describeExpression(mixed $expr, int $line): array
	{
		$element = $this->describeElementExpression($expr, $line);
		if ($element !== null) {
			return $element;
		}
		$asyncWait = $this->describeAsyncWaitExpression($expr, $line);
		if ($asyncWait !== null) {
			return $asyncWait;
		}
		$chain = $this->extractChainDescriptor($expr, $line);
		if ($chain !== null) {
			return ['kind' => 'chain', 'chain' => $chain];
		}
		$coalesce = $this->describeCoalesceExpression($expr, $line);
		if ($coalesce !== null) {
			return $coalesce;
		}
		$conditional = $this->describeConditionalExpression($expr, $line);
		if ($conditional !== null) {
			return $conditional;
		}
		$cast = $this->describeCastExpression($expr);
		if ($cast !== null) {
			return $cast;
		}
		$unaryBoolean = $this->describeUnaryBooleanExpression($expr, $line);
		if ($unaryBoolean !== null) {
			return $unaryBoolean;
		}
		$stringConcat = $this->describeStringConcatExpression($expr, $line);
		if ($stringConcat !== null) {
			return $stringConcat;
		}
		$boolean = $this->describeBooleanExpression($expr);
		if ($boolean !== null) {
			return $boolean;
		}
		$arithmetic = $this->describeArithmeticExpression($expr, $line);
		if ($arithmetic !== null) {
			return $arithmetic;
		}
		$type = $this->inferLiteralType($expr);
		if ($type !== null) {
			return ['kind' => 'type', 'type' => $type];
		}
		$type = $this->inferConstructedClassType($expr);
		if ($type !== null) {
			return ['kind' => 'type', 'type' => $type];
		}
		$varName = $this->extractAssignedVariableName($expr);
		if ($varName !== null) {
			return ['kind' => 'alias', 'source' => $varName];
		}
		return ['kind' => 'unknown'];
	}

	/** @return array<string,mixed>|null */
	private function describeElementExpression(mixed $expr, int $line): ?array
	{
		if (!is_object($expr) || !isset($expr->kind, $expr->children) || !is_array($expr->children)) {
			return null;
		}
		if ($expr->kind !== AstKind::DIM) {
			return null;
		}
		$source = $this->describeExpression($expr->children['expr'] ?? null, $line);
		if (($source['kind'] ?? 'unknown') === 'unknown') {
			return null;
		}
		return [
			'kind' => 'element',
			'source' => $source,
		];
	}

	/** @return array<string,mixed>|null */
	private function describeAsyncWaitExpression(mixed $expr, int $line): ?array
	{
		if (!is_object($expr) || !isset($expr->kind, $expr->children) || !is_array($expr->children)) {
			return null;
		}
		if ($expr->kind !== AstKind::CALL || strtolower((string) ($this->extractDirectFunctionCallName($expr) ?? '')) !== 'async_wait') {
			return null;
		}
		$args = isset($expr->children['args']->children) && is_array($expr->children['args']->children)
			? array_values($expr->children['args']->children)
			: [];
		if (count($args) !== 1) {
			return null;
		}
		$inner = $this->describeExpression($args[0], $line);
		return (($inner['kind'] ?? 'unknown') === 'unknown') ? null : $inner;
	}

	private function extractDirectFunctionCallName(mixed $expr): ?string
	{
		if (!is_object($expr) || !isset($expr->kind, $expr->children) || !is_array($expr->children)) {
			return null;
		}
		if ($expr->kind !== AstKind::CALL) {
			return null;
		}
		$callee = $expr->children['expr'] ?? null;
		if (!is_object($callee) || !isset($callee->kind, $callee->children) || !is_array($callee->children)) {
			return null;
		}
		if ($callee->kind !== AstKind::NAME) {
			return null;
		}
		$name = trim((string) ($callee->children['name'] ?? ''));
		return $name === '' ? null : $name;
	}

	/** @return array<string,mixed>|null */
	private function describeConditionalExpression(mixed $expr, int $line): ?array
	{
		if (!is_object($expr) || !isset($expr->kind, $expr->children) || !is_array($expr->children)) {
			return null;
		}
		if ($expr->kind !== AstKind::CONDITIONAL) {
			return null;
		}
		$ifTrue = $this->describeExpression($expr->children['true'] ?? null, $line);
		$ifFalse = $this->describeExpression($expr->children['false'] ?? null, $line);
		if (($ifTrue['kind'] ?? 'unknown') === 'unknown' || ($ifFalse['kind'] ?? 'unknown') === 'unknown') {
			return null;
		}
		return [
			'kind' => 'conditional',
			'if_true' => $ifTrue,
			'if_false' => $ifFalse,
		];
	}

	/** @return array<string,mixed>|null */
	private function describeCoalesceExpression(mixed $expr, int $line): ?array
	{
		if (!is_object($expr) || !isset($expr->kind, $expr->children) || !is_array($expr->children)) {
			return null;
		}
		if ($expr->kind !== AstKind::BINARY_OP || (int) ($expr->flags ?? 0) !== AstKind::BINARY_COALESCE) {
			return null;
		}
		$left = $this->describeExpression($expr->children['left'] ?? null, $line);
		$right = $this->describeExpression($expr->children['right'] ?? null, $line);
		if (($left['kind'] ?? 'unknown') === 'unknown' || ($right['kind'] ?? 'unknown') === 'unknown') {
			return null;
		}
		return [
			'kind' => 'conditional',
			'if_true' => $left,
			'if_false' => $right,
		];
	}

	/** @return array<string,mixed>|null */
	private function describeStringConcatExpression(mixed $expr, int $line): ?array
	{
		if (!is_object($expr) || !isset($expr->kind, $expr->children) || !is_array($expr->children)) {
			return null;
		}
		if ($expr->kind !== AstKind::BINARY_OP || (int) ($expr->flags ?? 0) !== AstKind::BINARY_CONCAT) {
			return null;
		}
		$left = $this->describeExpression($expr->children['left'] ?? null, $line);
		$right = $this->describeExpression($expr->children['right'] ?? null, $line);
		if (($left['kind'] ?? 'unknown') === 'unknown' || ($right['kind'] ?? 'unknown') === 'unknown') {
			return null;
		}
		return ['kind' => 'type', 'type' => 'string'];
	}

	/** @return array<string,mixed>|null */
	private function describeCastExpression(mixed $expr): ?array
	{
		if (!is_object($expr) || !isset($expr->kind)) {
			return null;
		}
		if ($expr->kind !== AstKind::CAST) {
			return null;
		}
		$flag = (int) ($expr->flags ?? 0);
		$type = match ($flag) {
			AstKind::TYPE_LONG => 'int',
			AstKind::TYPE_STRING => 'string',
			AstKind::TYPE_BOOL => 'bool',
			AstKind::TYPE_DOUBLE => 'float',
			AstKind::TYPE_OBJECT => 'mixed',
			default => null,
		};
		if ($type === null) {
			return null;
		}
		return ['kind' => 'type', 'type' => $type];
	}

	/** @return array<string,mixed>|null */
	private function describeUnaryBooleanExpression(mixed $expr, int $line): ?array
	{
		if (!is_object($expr) || !isset($expr->kind, $expr->children) || !is_array($expr->children)) {
			return null;
		}
		if ($expr->kind !== AstKind::UNARY_OP) {
			return null;
		}
		$flag = (int) ($expr->flags ?? 0);
		if ($flag !== AstKind::UNARY_BOOL_NOT) {
			return null;
		}
		$inner = $this->describeExpression($expr->children['expr'] ?? null, $line);
		if (($inner['kind'] ?? 'unknown') === 'unknown') {
			return null;
		}
		return ['kind' => 'type', 'type' => 'bool'];
	}

	/** @return array<string,mixed>|null */
	private function describeArithmeticExpression(mixed $expr, int $line): ?array
	{
		if (!is_object($expr) || !isset($expr->kind, $expr->children) || !is_array($expr->children)) {
			return null;
		}
		if ($expr->kind !== AstKind::BINARY_OP) {
			return null;
		}
		$flag = (int) ($expr->flags ?? 0);
		if (!in_array($flag, [AstKind::PLUS, AstKind::MINUS], true)) {
			return null;
		}
		$left = $this->describeExpression($expr->children['left'] ?? null, $line);
		$right = $this->describeExpression($expr->children['right'] ?? null, $line);
		if (($left['kind'] ?? 'unknown') === 'unknown' || ($right['kind'] ?? 'unknown') === 'unknown') {
			return null;
		}
		return [
			'kind' => 'arithmetic',
			'operator' => $flag === AstKind::PLUS ? '+' : '-',
			'left' => $left,
			'right' => $right,
		];
	}

	/** @return array<string,mixed>|null */
	private function describeBooleanExpression(mixed $expr): ?array
	{
		if (!is_object($expr) || !isset($expr->kind)) {
			return null;
		}
		if ($expr->kind === AstKind::BINARY_OP) {
			$flag = (int) ($expr->flags ?? 0);
			if (in_array($flag, [
				AstKind::BINARY_IS_IDENTICAL,
				AstKind::BINARY_IS_NOT_IDENTICAL,
				AstKind::BINARY_IS_EQUAL,
				AstKind::BINARY_IS_NOT_EQUAL,
				AstKind::BINARY_IS_SMALLER,
				AstKind::BINARY_IS_SMALLER_OR_EQUAL,
				AstKind::BINARY_IS_GREATER,
				257,
				AstKind::BINARY_BOOL_AND,
				AstKind::BINARY_BOOL_OR,
			], true)) {
				return ['kind' => 'type', 'type' => 'bool'];
			}
		}
		return null;
	}

	/** @param mixed $branchStatements @return array<string,array<string,mixed>|null> */
	private function summarizeBranchAssignmentMap(mixed $branchStatements, int $fallbackLine): array
	{
		$map = [];
		if (!is_array($branchStatements)) {
			return $map;
		}
		foreach ($branchStatements as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement) {
				continue;
			}
			if ($statement->kind !== 'assign') {
				continue;
			}
			$payload = $statement->payload;
			if (!is_array($payload)) {
				continue;
			}
			$target = $this->extractAssignedVariableName($payload['var'] ?? null);
			if ($target === null) {
				continue;
			}
			$expr = $payload['expr'] ?? null;
			$descriptor = $this->describeExpression($expr, $statement->line ?: $fallbackLine);
			$map[$target] = (($descriptor['kind'] ?? 'unknown') === 'unknown') ? null : $descriptor;
		}
		return $map;
	}

	/** @param mixed $branchStatements @return array<string,array<string,mixed>|null> */
	private function summarizeBranchPropertyAssignmentMap(mixed $branchStatements, int $fallbackLine): array
	{
		$map = [];
		if (!is_array($branchStatements)) {
			return $map;
		}
		foreach ($branchStatements as $statement) {
			if (!$statement instanceof \Scpp\S2S\IR\Statement || $statement->kind !== 'assign') {
				continue;
			}
			$payload = $statement->payload;
			if (!is_array($payload)) {
				continue;
			}
			$targetChain = $this->extractChainDescriptor($payload['var'] ?? null, $statement->line);
			if ($targetChain === null || !$this->isDirectSelfPropertyChain($targetChain)) {
				continue;
			}
			$segments = $targetChain['segments'] ?? [];
			$lastSegment = is_array($segments) ? ($segments[count($segments) - 1] ?? null) : null;
			if (!is_array($lastSegment)) {
				continue;
			}
			$propertyName = (string) ($lastSegment['name'] ?? '');
			if ($propertyName === '') {
				continue;
			}

			$expr = $payload['expr'] ?? null;
			$descriptor = $this->describeExpression($expr, $statement->line ?: $fallbackLine);
			$map[$propertyName] = (($descriptor['kind'] ?? 'unknown') === 'unknown') ? null : $descriptor;
		}
		return $map;
	}

	/** @param array<string,mixed> $chain */
	private function isDirectSelfPropertyChain(array $chain): bool
	{
		$segments = $chain['segments'] ?? [];
		return (($chain['root_kind'] ?? '') === 'variable')
			&& (($chain['root_name'] ?? '') === 'this')
			&& is_array($segments)
			&& count($segments) === 1
			&& is_array($segments[0] ?? null)
			&& (($segments[0]['kind'] ?? '') === 'property');
	}

	/** @param list<array<string,mixed>> $clauses */
	private function findBranchMergeLine(array $clauses, int $fallbackLine): int
	{
		$maxLine = $fallbackLine;
		foreach ($clauses as $clause) {
			if (!is_array($clause) || !is_array($clause['stmts'] ?? null)) {
				continue;
			}
			foreach ($this->flattenStatements($clause['stmts']) as $statement) {
				if ($statement instanceof \Scpp\S2S\IR\Statement) {
					$maxLine = max($maxLine, $statement->line);
				}
			}
		}
		return $maxLine;
	}

	private function extractNullGuardName(mixed $node): ?string
	{
		if (!is_object($node) || !isset($node->kind, $node->children) || !is_array($node->children)) {
			return null;
		}
		if ($node->kind !== AstKind::BINARY_OP) {
			return null;
		}
		$flag = (int) ($node->flags ?? 0);
		if ($flag !== AstKind::BINARY_IS_IDENTICAL) {
			return null;
		}
		$left = $node->children['left'] ?? null;
		$right = $node->children['right'] ?? null;
		$leftVar = $this->extractAssignedVariableName($left);
		$rightVar = $this->extractAssignedVariableName($right);
		$leftNull = $this->inferLiteralType($left) === 'null';
		$rightNull = $this->inferLiteralType($right) === 'null';
		if ($leftVar !== null && $rightNull) {
			return $leftVar;
		}
		if ($rightVar !== null && $leftNull) {
			return $rightVar;
		}
		return null;
	}

	private function extractFalseGuardName(mixed $node): ?string
	{
		if (!is_object($node) || !isset($node->kind, $node->children) || !is_array($node->children)) {
			return null;
		}
		if ($node->kind !== AstKind::BINARY_OP) {
			return null;
		}
		$flag = (int) ($node->flags ?? 0);
		if ($flag === AstKind::BINARY_BOOL_OR) {
			$leftGuard = $this->extractFalseGuardName($node->children['left'] ?? null);
			if ($leftGuard !== null) {
				return $leftGuard;
			}
			return $this->extractFalseGuardName($node->children['right'] ?? null);
		}
		if ($flag !== AstKind::BINARY_IS_IDENTICAL) {
			return null;
		}
		$left = $node->children['left'] ?? null;
		$right = $node->children['right'] ?? null;
		$leftVar = $this->extractAssignedVariableName($left);
		$rightVar = $this->extractAssignedVariableName($right);
		$leftFalse = $this->isFalseLiteral($left);
		$rightFalse = $this->isFalseLiteral($right);
		if ($leftVar !== null && $rightFalse) {
			return $leftVar;
		}
		if ($rightVar !== null && $leftFalse) {
			return $rightVar;
		}
		return null;
	}

	private function isFalseLiteral(mixed $expr): bool
	{
		if ($expr === false) {
			return true;
		}
		if (!is_object($expr) || !isset($expr->kind, $expr->children) || !is_array($expr->children)) {
			return false;
		}
		if ($expr->kind === AstKind::CONST) {
			$nameNode = $expr->children['name'] ?? null;
			if (is_object($nameNode) && isset($nameNode->kind, $nameNode->children) && is_array($nameNode->children) && $nameNode->kind === AstKind::NAME) {
				return strtolower(trim((string) ($nameNode->children['name'] ?? ''))) === 'false';
			}
		}
		if ($expr->kind === AstKind::NAME) {
			return strtolower(trim((string) ($expr->children['name'] ?? ''))) === 'false';
		}
		return false;
	}

	/** @param list<\Scpp\S2S\IR\Statement> $branchStatements */
	private function branchAlwaysTerminates(array $branchStatements): bool
	{
		$flat = $this->flattenStatements($branchStatements);
		if ($flat === []) {
			return false;
		}
		$last = $flat[count($flat) - 1] ?? null;
		return $last instanceof \Scpp\S2S\IR\Statement
			&& in_array($last->kind, ['return', 'throw', 'break', 'continue'], true);
	}

	/** @return array<string,mixed>|null */
	private function describeForLoopInitLocal(mixed $node, int $line): ?array
	{
		if (!is_object($node) || !isset($node->kind, $node->children) || !is_array($node->children)) {
			return null;
		}
		if ($node->kind !== AstKind::ASSIGN) {
			return null;
		}
		$target = $this->extractAssignedVariableName($node->children['var'] ?? null);
		if ($target === null) {
			return null;
		}
		$descriptor = $this->describeExpression($node->children['expr'] ?? null, $line);
		return [
			'line' => $line,
			'name' => $target,
			'source' => $descriptor,
		];
	}

	private function loadSourceLines(string $path, ?string $sourceCode = null): array
	{
		$contents = $sourceCode;
		if (!is_string($contents)) {
			if ($path === '' || !is_file($path)) {
				return [];
			}
			$contents = file_get_contents($path);
		}
		if (!is_string($contents) || $contents === '') {
			return [];
		}
		return preg_split("/\\r\\n|\\n|\\r/", $contents) ?: [];
	}

	private function extractInlineTypedAssignmentType(int $line, string $name, array $sourceLines): ?string
	{
		if ($line <= 0) {
			return null;
		}
		$sourceLine = $sourceLines[$line - 1] ?? null;
		if (!is_string($sourceLine) || $sourceLine === '') {
			return null;
		}
		$pattern = '/\\$' . preg_quote($name, '/') . '\\s+([^=;]+?)\\s*=/';
		if (preg_match($pattern, $sourceLine, $matches) !== 1) {
			return null;
		}
		$type = trim((string) ($matches[1] ?? ''));
		if (preg_match('/^\/\*\*\s*(.+?)\s*\*\/$/', $type, $commentMatches) === 1) {
			$type = trim((string) ($commentMatches[1] ?? ''));
		}
		return $type !== '' ? $type : null;
	}
}
