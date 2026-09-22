<?php
declare(strict_types=1);

/*
 * Role: Private package types processing for Package_Adapter.
 * Used by: Package_Adapter; methods execute on that single adapter owner
 * Call map: Package_Adapter::types(); retain_bound_types() [exact unchanged imported contracts]
 * Output: validated compiler contracts; no canonical type IDs allocated here.
 */

namespace load_runtime;

use type_model\Type_Catalog;
use type_model\cleanup_kind;
use type_model\copy_kind;
use type_model\lifetime_contract;
use type_model\named_type_definition;

trait Package_Types
{
    /** Translate measured storage and language bindings; resolve lifecycle references against exact operation IDs. */
    private static function types(array $rows, Type_Catalog $catalog, array $operations, string $provider, ?package_bindings $bindings = null): array
    {
        // Lifecycle references use complete operation identities, including unexposed operations.
        $by_operation = [];
        foreach ($operations as $operation) {
            $id = self::identifier($operation['id'] ?? null);
            if (isset($by_operation[$id])) {
                throw new \RuntimeException('Duplicate runtime operation: ' . $id);
            }
            $by_operation[$id] = $operation;
        }

        $types = [];
        foreach ($rows as $row)
        {
            // Validate the measured storage independently of optional source-language exposure.
            $id = self::identifier($row['id'] ?? null);
            if (isset($types[$id])) {
                throw new \RuntimeException('Duplicate runtime type: ' . $id);
            }
            $resource = self::resource_type($row);
            $kind = match ($row['kind'] ?? null)
            {
                'integer' => runtime_storage_kind::integer,
                'address' => runtime_storage_kind::address,
                'runtime_value' => runtime_storage_kind::opaque_inline,
                'value_record' => runtime_storage_kind::record,
                'byte_span' => runtime_storage_kind::byte_span,
                'void' => runtime_storage_kind::void_type,
                default => throw new \RuntimeException('Unsupported runtime type kind'),
            };
            $size = $kind === runtime_storage_kind::void_type ? 0 : self::positive($row['size_bytes'] ?? null, 'size');
            $alignment = self::positive($row['alignment_bytes'] ?? null, 'alignment');
            if ((($alignment & ($alignment - 1)) !== 0) || (($size % $alignment) !== 0)
                || (($kind === runtime_storage_kind::opaque_inline) && (($row['storage'] ?? null) !== 'inline'))) {
                throw new \RuntimeException('Invalid runtime storage contract');
            }

            // Integer width and signedness must agree with any existing language binding.
            $bits = null;
            $signed = null;
            if ($kind === runtime_storage_kind::integer) {
                $bits = self::positive($row['value_bits'] ?? null, 'integer width');
                $signed = $row['signed'] ?? null;
                if (!is_bool($signed) || ($bits > ($size * 8))) {
                    throw new \RuntimeException('Invalid runtime integer contract');
                }
            }

            // Exposed opaque types introduce definitions with their own lifetime contracts.
            $language = null;
            $binding = $bindings?->types[$id] ?? null;
            if (($binding !== null) && !($binding instanceof \type_model\named_type_reference)) {
                throw new \RuntimeException('Invalid compiler runtime type binding');
            }
            $import = $bindings?->imports[$id] ?? null;
            if (isset($row['source_payload']) || isset($bindings?->sources[$id]))
            {
                if (($binding !== null) || ($import !== null)) {
                    throw new \RuntimeException('Source payload cannot introduce another type owner');
                }
                $export = $bindings?->sources[$id] ?? throw new \RuntimeException('Source payload requires an explicit compiler export binding');
                $language = Project_Import::source_type($row, $export, new runtime_storage($kind, $size, $alignment));
                $kind = runtime_storage_kind::record;
            }
            elseif (isset($row['native_import']) || ($import !== null))
            {
                if (!($import instanceof runtime_type_import) || ($binding !== null) || isset($row['language_type'])
                    || (($row['native_import'] ?? null) !== ['provider' => $import->provider, 'id' => $import->type_id])
                    || ($kind !== runtime_storage_kind::opaque_inline) || (($row['lifecycle'] ?? null) !== [])
                    || ($import->type->storage != new runtime_storage($kind, $size, $alignment))
                    || ($import->type->id !== $import->type_id) || ($import->type->language_type === null)
                    || isset($row['resource']) || isset($row['storage_family']) || isset($row['struct_field'])) {
                    throw new \RuntimeException('Native type import does not match its accepted owner');
                }
                $language = $import->type->language_type;
                if ((($row['cpp_traits']['copy_constructible'] ?? null) !== true)
                    || (($row['cpp_traits']['copy_assignable'] ?? null) !== true)
                    || (\type_model\Generic_Contracts::missing($language, \type_model\generic_contract::copyable_value) !== null)) {
                    throw new \RuntimeException('Native type import lost its generic capabilities');
                }
            }
            elseif (isset($row['language_type']) || ($binding !== null))
            {
                [$name, $namespace] = $binding === null ? self::language_name($row['language_type'])
                    : [$binding->name, $binding->namespace_name];
                $language = $catalog->find_type($name, $namespace);
                if ($kind === runtime_storage_kind::record) {
                    if ($language !== null) {
                        throw new \RuntimeException('Exposed records require a new language name');
                    }
                }
                elseif ($kind === runtime_storage_kind::opaque_inline)
                {
                    if ($language !== null) {
                        throw new \RuntimeException('Exposed inline types require a new language name');
                    }
                    $lifetime = self::lifetime($row, $by_operation, $provider);
                    $language = new named_type_definition($name, $namespace,
                        new \type_model\representation_record(\type_model\representation_kind::opaque_inline,
                            new \type_model\opaque_representation($size, $alignment)),
                        $lifetime, struct_field: self::field_eligibility($row), resource: $resource);
                }
                elseif ($kind === runtime_storage_kind::byte_span)
                {
                    if ($language !== null) {
                        throw new \RuntimeException('A byte span requires a new language name');
                    }
                    $language = new named_type_definition($name, $namespace,
                        new \type_model\representation_record(\type_model\representation_kind::byte_span, null),
                        new lifetime_contract(copy_kind::value, cleanup_kind::none, assignment: \type_model\assignment_kind::value));
                }
                elseif ($kind === runtime_storage_kind::void_type) {
                    if (($language?->representation->kind !== \type_model\representation_kind::void_type)
                        || (($row['size_bytes'] ?? null) !== 0)) {
                        throw new \RuntimeException('Invalid runtime void binding');
                    }
                }
                elseif (($language === null) || ($language->representation->kind !== \type_model\representation_kind::integer)
                    || ($language->representation->payload->bit_width !== $bits) || ($language->signed !== $signed)) {
                    throw new \RuntimeException('Runtime type does not match language binding: ' . $name);
                }
            }

            $types[$id] = new runtime_type($id, new runtime_storage($kind, $size, $alignment), $bits, $signed, $language);
        }
        foreach ($bindings?->types ?? [] as $id => $binding) {
            if (!isset($types[$id]) || !($binding instanceof \type_model\named_type_reference)) {
                throw new \RuntimeException('Unknown or invalid compiler runtime type binding');
            }
        }
        foreach ($bindings?->imports ?? [] as $id => $import) {
            if (!isset($types[$id]) || !($import instanceof runtime_type_import)) {
                throw new \RuntimeException('Unknown compiler runtime type import');
            }
        }
        foreach ($bindings?->sources ?? [] as $id => $export) {
            if (!isset($types[$id]) || ($types[$id]->language_type !== $export->task->layout->definition)) {
                throw new \RuntimeException('Unknown compiler source payload binding');
            }
        }
        return $types;
    }

    /** Reuse exact normalized contracts, never inferred identity from layout equality alone. */
    private static function retain_bound_types(array $types, package_bindings $bindings, Runtime_Package $previous): array
    {
        foreach ($bindings->types as $id => $binding)
        {
            $old = $previous->types()[$id] ?? null;
            if (($old !== null) && (($previous->bindings?->types[$id] ?? null) == $binding)) {
                if ($old != $types[$id]) {
                    throw new \RuntimeException('Prepared family type contract changed; a fresh compiler type context is required');
                }
                $types[$id] = $old;
            }
        }
        foreach ($bindings?->sources ?? [] as $id => $export) {
            if (!isset($types[$id]) || ($types[$id]->language_type !== $export->task->layout->definition)) {
                throw new \RuntimeException('Unknown compiler source payload binding');
            }
        }
        return $types;
    }

    /** Eligibility is provider permission; imported operations still determine capability availability. */
    private static function field_eligibility(array $row): bool
    {
        $allowed = array_key_exists('struct_field', $row) ? $row['struct_field'] : false;
        if (!is_bool($allowed)) {
            throw new \RuntimeException('Invalid runtime field eligibility');
        }
        return $allowed;
    }

}
