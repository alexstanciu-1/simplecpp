<?php
declare(strict_types=1);

/*
 * Role: Fixed compiler specialization demands, internal bindings and accepted native coverage.
 * Used by: Concrete_Preparation; native family bridge; Family_Preparation_Join
 * Flow: semantic instance/operations -> private package -> accepted storage, lifecycle and callables
 */
namespace load_runtime;

/** Compiler-owned names bind measured types without rewriting published provider metadata. */
final class package_bindings {
    /** Bind compiler-internal names without changing shared generated package files.
     * @param array<string, \type_model\named_type_reference> $types Package-local type IDs.
     * @param array<string, \type_model\named_type_reference> $callables Package-local operation IDs.
     * @param array<string, runtime_type_import> $imports Package-local references to existing native owners.
     * @param array<string, \prepare_backend\source_type_export> $sources Package-local payload references to existing source records. */
    public function __construct(public readonly array $types = [], public readonly array $callables = [],
        public readonly array $imports = [], public readonly array $sources = [])
    {
    }
}

/** Exact accepted owner of a package-local type reference; never a second concrete definition. */
final class runtime_type_import {
    public function __construct(public readonly string $provider, public readonly string $type_id,
        public readonly runtime_type $type, public readonly string $target_triple, public readonly string $data_layout)
    {
    }
}

final class family_preparation_task {
    /** @param list<string> $operations Complete required operation IDs for this frontier; lifecycle coverage is implicit.
     * @param array<int, \prepare_backend\source_type_export> $sources Accepted source arguments by formal position. */
    public function __construct(public readonly \instantiate\instance_context $context, public readonly array $operations = [],
        public readonly array $sources = [])
    {
    }
}

final class family_preparation_result {
    /** @param array<string, \type_model\runtime_callable> $operations Validated source-facing operation coverage. */
    public function __construct(public readonly family_preparation_task $task, public readonly Runtime_Package $package,
        public readonly string $type_id, public readonly array $operations = [])
    {
    }
}

/** Preparation is a coordinator service; semantic workers neither implement nor invoke it. */
interface Family_Preparer {
    /** @param list<family_preparation_task> $tasks @param array<int, family_preparation_result> $previous
     * @param array<string, Runtime_Package> $packages Accepted ordinary packages, shared read-only.
     * @return list<family_preparation_result> */
    public function prepare(array $tasks, \type_model\Type_Catalog $catalog, array $previous, array $packages = []): array;
}
