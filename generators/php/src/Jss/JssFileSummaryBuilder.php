<?php
declare(strict_types=1);

namespace Scpp\S2S\Jss;

final class JssFileSummaryBuilder
{
	/** @return array<string,mixed> */
	public function useDeclaration(string $kind, string $name, mixed $alias, int $line): array
	{
		return [
			'kind' => $kind !== '' ? $kind : 'class',
			'name' => $name,
			'alias' => $alias,
			'line' => $line,
			'is_grouped' => false,
		];
	}

	/** @return array<string,mixed> */
	public function constantDeclaration(string $name, ?string $namespace, int $line): array
	{
		return [
			'name' => $name,
			'namespace' => $namespace,
			'line' => $line,
			'is_lib_export' => false,
		];
	}

	/**
	 * @param list<array{name:string,type:string,default:mixed}> $params
	 * @param list<array{name:string,type:string,line:int}> $typedLocals
	 * @return array<string,mixed>
	 */
	public function functionDeclaration(
		string $name,
		?string $namespace,
		array $params,
		mixed $returnType,
		array $typedLocals,
		int $statementCount,
		int $line,
		bool $isStatic = false,
	): array {
		$summary = [
			'name' => $name,
			'namespace' => $namespace,
			'params' => $params,
			'return_type' => $returnType,
			'typed_locals' => $typedLocals,
			'return_chains' => [],
			'return_values' => [],
			'expression_chains' => [],
			'property_reads' => [],
			'call_sites' => [],
			'local_alias_assignments' => [],
			'local_literal_assignments' => [],
			'local_type_assignments' => [],
			'local_constructed_assignments' => [],
			'local_descriptor_assignments' => [],
			'local_branch_assignments' => [],
			'non_null_guards' => [],
			'non_false_guards' => [],
			'foreach_locals' => [],
			'for_loop_locals' => [],
			'property_assignments' => [],
			'property_branch_assignments' => [],
			'local_invalidations' => [],
			'statement_count' => $statementCount,
			'line' => $line,
			'returns_by_reference' => false,
			'is_lib_export' => false,
		];
		if ($isStatic) {
			$summary['is_static'] = true;
		}
		return $summary;
	}

	/**
	 * @param list<array<string,mixed>> $methods
	 * @param list<array<string,mixed>> $properties
	 * @param list<array<string,mixed>> $constants
	 * @return array<string,mixed>
	 */
	public function classDeclaration(
		string $name,
		?string $namespace,
		int $line,
		mixed $parentClass,
		array $methods,
		array $properties,
		array $constants,
		bool $isStruct = false,
		bool $isUnion = false,
	): array {
		return [
			'name' => $name,
			'namespace' => $namespace,
			'line' => $line,
			'parent_class' => $parentClass,
			'interfaces' => [],
			'is_interface' => false,
			'is_abstract' => false,
			'is_enum' => false,
			'is_struct' => $isStruct,
			'is_union' => $isUnion,
			'declaration_kind' => $isUnion ? 'union' : ($isStruct ? 'struct' : 'class'),
			'is_lib_export' => false,
			'methods' => $methods,
			'properties' => $properties,
			'constants' => $constants,
		];
	}

	/** @return array<string,mixed> */
	public function propertyDeclaration(string $name, string $type, int $line, bool $isStatic, bool $hasDefault): array
	{
		return [
			'name' => $name,
			'type' => $type,
			'line' => $line,
			'is_static' => $isStatic,
			'has_default' => $hasDefault,
		];
	}

	/** @return array<string,mixed> */
	public function classConstantDeclaration(string $name, int $line): array
	{
		return [
			'name' => $name,
			'line' => $line,
		];
	}

	/** @return array{name:string,type:string,default:mixed} */
	public function parameter(string $name, string $type, mixed $default): array
	{
		return [
			'name' => $name,
			'type' => $type,
			'default' => $default,
		];
	}

	/** @return array{name:string,type:string,line:int} */
	public function typedLocal(string $name, string $type, int $line): array
	{
		return [
			'name' => $name,
			'type' => $type,
			'line' => $line,
		];
	}

	/** @param list<array<string,mixed>> $rootUses
	 * @param list<array<string,mixed>> $rootConstants
	 * @param list<array<string,mixed>> $rootFunctions
	 * @param list<array<string,mixed>> $rootClasses
	 * @param array<string,array<string,mixed>> $namespaces
	 * @param list<array<string,mixed>> $requests
	 * @return array<string,mixed>
	 */
	public function build(
		string $path,
		array $rootUses,
		array $rootConstants,
		array $rootFunctions,
		array $rootClasses,
		array $namespaces,
		array $requests,
	): array {
		return [
			'path' => $path,
			'frontend' => [
				'language' => 'jss',
				'profile' => 'strict',
				'summary_version' => 3,
			],
			'prologue_includes' => [],
			'root_uses' => $rootUses,
			'root_constants' => $rootConstants,
			'root_functions' => $rootFunctions,
			'root_classes' => $rootClasses,
			'namespaces' => array_values($namespaces),
			'build_errors' => [],
			'scanner_annotations' => [],
			'dependencies' => $this->collectDependencies($rootClasses),
			'frontend_classification_requests' => $requests,
		];
	}

	/** @param array<string,array<string,mixed>> $namespaces */
	public function ensureNamespace(array &$namespaces, string $name): void
	{
		if (isset($namespaces[$name])) {
			return;
		}
		$namespaces[$name] = [
			'name' => $name,
			'uses' => [],
			'constants' => [],
			'functions' => [],
			'classes' => [],
		];
	}

	/**
	 * @param list<array<string,mixed>> $classes
	 * @return list<array{kind:string,target:string,owner:?string}>
	 */
	private function collectDependencies(array $classes): array
	{
		$dependencies = [];
		foreach ($classes as $class) {
			$parent = $class['parent_class'] ?? null;
			if (is_string($parent) && $parent !== '') {
				$dependencies[] = [
					'kind' => 'extends',
					'target' => $parent,
					'owner' => (string) ($class['name'] ?? ''),
				];
			}
		}
		return $dependencies;
	}
}
