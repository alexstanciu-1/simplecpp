<?php
declare(strict_types=1);
namespace runtime_preparation\families;

use runtime_preparation\native_type;

/** Native binding fields are not compiler semantic contracts. */
final class operation_binding
{
    /** @param list<\type_model\type_reference> $template_arguments
     * @param list<array<string, string>> $parameter_adaptations */
    public function __construct(public readonly \type_model\family_operation $semantic,
        public readonly string $kind, public readonly string $cpp_name, public readonly string $header,
        public readonly array $template_arguments, public readonly array $parameter_adaptations,
        public readonly ?\type_model\type_reference $object_type, public readonly ?string $source_result = null)
    {
    }
}

final class family_binding {
    /** @param array<string, operation_binding> $operations */
    public function __construct(public readonly \type_model\family_definition $semantic,
        public readonly string $cpp_name, public readonly string $header, public readonly array $operations,
        public readonly array $source_profiles = [])
    {
    }
}

final class family_catalog {
    /** @param array<string, native_type> $types @param array<string, family_binding> $families */
    public function __construct(public readonly string $provider, public readonly array $types,
        public readonly array $families)
    {
    }
}

/** Caller supplies immutable catalog/context snapshots; operation coverage is not identity. */
final class specialization_request {
    /** @param list<string|native_type> $arguments Catalog identities or accepted native bindings; no compiler IDs.
     * @param list<string> $operations */
    public function __construct(public readonly family_catalog $catalog, public readonly string $family,
        public readonly array $arguments, public readonly array $operations, public readonly string $scope,
        public readonly array $configuration, public readonly array $dependency_hashes = [])
    {
    }
}

/** Selected work is fixed before Clang runs and owns one package reservation. */
final class preparation_task
{
    /** @param list<string> $coverage */
    public function __construct(public readonly string $key, public readonly family_binding $family,
        public readonly array $arguments, public readonly array $coverage, public readonly string $contract,
        public readonly string $configuration, public readonly string $receipt, public readonly array $input_hashes,
        public readonly \runtime_preparation\Package_Reservation $reservation,
        public readonly ?\runtime_preparation\project\module_contract $project = null)
    {
    }
}

/** Worker output is private until the matching selected-task join accepts it. */
final class preparation_result {
    public function __construct(public readonly preparation_task $task,
        public readonly \runtime_preparation\Package_Candidate $candidate)
    {
    }
}

/** Accepted preparation facts stay separate from any compiler canonical IDs. */
final class accepted_specialization {
    public function __construct(public readonly preparation_task $task, public readonly array $types,
        public readonly array $operations, public readonly array $manifest,
        public readonly \runtime_preparation\Package_Candidate $candidate)
    {
    }
}
