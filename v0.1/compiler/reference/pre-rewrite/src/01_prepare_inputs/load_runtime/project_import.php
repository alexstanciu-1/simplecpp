<?php
declare(strict_types=1);

/*
 * Role: Validate the compiler-facing project module receipt and its source payload bindings.
 * Call map: Package_Adapter -> Project_Import::validate(); source_type()
 * Output: current source definitions and exact required lifecycle exports; ordinary import remains closed.
 */
namespace load_runtime;

final class Project_Import
{
    /** Validate the receipt against current exports, including the prepared physical ABI and semantic effects. */
    public static function validate(project_binding $binding, string $receipt, array $target): array
    {
        if ($receipt !== $binding->receipt) {
            throw new \RuntimeException('Project module receipt changed before compiler acceptance');
        }
        $data = json_decode($receipt, true, flags: JSON_THROW_ON_ERROR);
        if ((($data['schema_version'] ?? null) !== 1)
            || (array_keys($data['contract']['sources'] ?? []) !== array_keys($binding->exports))
            || (array_keys($data['required_imports'] ?? []) !== ['runtime.ll', 'runtime.bc', 'runtime.lto.bc', 'runtime.thin.bc'])) {
            throw new \RuntimeException('Project module source membership mismatch');
        }
        // Nominal identity and physical storage must agree before operation imports can be authorized.
        $authorized = [];
        foreach ($binding->exports as $key => $export)
        {
            \prepare_backend\Source_Export_Preparation::validate($export);
            $task = $export->task;
            $source = $data['contract']['sources'][$key];
            if ((($source['profile'] ?? null) !== \prepare_backend\source_type_export::PROFILE)
                || (array_keys($source['states'] ?? []) !== array_keys($export->operations))
                || (array_keys($source['operations'] ?? []) !== array_keys($export->operations))
                || ($task->identity->key !== $key) || ($task->project->project_key !== ($data['contract']['project'] ?? null))
                || (($source['key'] ?? null) !== $key) || (($source['project'] ?? null) !== $task->project->project_key)
                || (($source['size'] ?? null) !== $task->layout->size) || (($source['alignment'] ?? null) !== $task->layout->alignment)
                || (($source['target'] ?? null) !== $target)
                || ($task->layout->configuration->target_triple !== $target['triple'])
                || ($task->layout->configuration->data_layout !== $target['data_layout'])) {
                throw new \RuntimeException('Project source identity/layout/target mismatch');
            }
            // The compiler owns role semantics; the receipt cannot strengthen or replace their contracts.
            foreach ($export->operations as $role => $operation)
            {
                if (($source['states'][$role] ?? null) !== $operation->capability->state->value) {
                    throw new \RuntimeException('Project source capability mismatch');
                }
                $row = $source['operations'][$role] ?? null;
                if ($operation->import === null) {
                    if ($row !== null) {
                        throw new \RuntimeException('Unavailable source capability acquired a project import');
                    }
                    continue;
                }
                $abi = $operation->import;
                $parameters = array_map(static fn($parameter) => ['type' => $parameter->type,
                    'extension' => $parameter->extension->value], $abi->parameters);
                if ((($row['symbol'] ?? null) !== $abi->link_name) || (($row['role'] ?? null) !== $role)
                    || (($row['semantics'] ?? null) !== $operation->capability->role->semantics())
                    || (($row['abi'] ?? null) !== ['calling_convention' => $abi->calling_convention,
                        'return_type' => $abi->return_type, 'parameters' => $parameters, 'return_extension' => $abi->return_extension->value])) {
                    throw new \RuntimeException('Project source import ABI/effects mismatch');
                }
                if (isset($authorized[$abi->link_name])) {
                    throw new \RuntimeException('Duplicate project source export identity');
                }
                $authorized[$abi->link_name] = [$row, $operation];
            }
        }
        // Every artifact variant names a subset of the same authorized complete operations.
        $required = [];
        foreach ($data['required_imports'] as $imports)
        {
            foreach ($imports as $symbol => $row) {
                if (!isset($authorized[$symbol]) || ($row !== $authorized[$symbol][0])) {
                    throw new \RuntimeException('Unauthorized project source import');
                }
                $required[$symbol] = $authorized[$symbol][1];
            }
        }
        ksort($required);
        return $required;
    }

    /** Bind source storage to its exact existing definition, never to the distinct C++ adapter type. */
    public static function source_type(array $row, \prepare_backend\source_type_export $export,
        runtime_storage $storage): \type_model\named_type_definition
    {
        $layout = $export->task->layout;
        if (($row['source_payload'] ?? null) !== $export->task->identity->key
            || ($storage->size_bytes !== $layout->size) || ($storage->alignment_bytes !== $layout->alignment)
            || (($row['kind'] ?? null) !== 'runtime_value') || (($row['lifecycle'] ?? null) !== [])
            || isset($row['language_type']) || isset($row['native_import']) || isset($row['resource'])
            || isset($row['storage_family']) || isset($row['struct_field'])) {
            throw new \RuntimeException('Source payload does not match its accepted compiler definition');
        }
        return $layout->definition;
    }
}
