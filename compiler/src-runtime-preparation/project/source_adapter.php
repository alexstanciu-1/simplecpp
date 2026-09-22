<?php
declare(strict_types=1);
namespace runtime_preparation\project;

use runtime_preparation as native;

/** Compiler/preparation adapter: portable facts cross this boundary, never ASTs or mutable compiler stores. */
final class Source_Adapter
{
    /** Project one accepted compiler export without serializing lineage-local operation names or type IDs. */
    public static function argument(\prepare_backend\source_type_export $export): native\native_type
    {
        \prepare_backend\Source_Export_Preparation::validate($export);
        $task = $export->task;
        $layout = $task->layout;
        $states = [];
        $operations = [];
        foreach ($export->operations as $role => $operation)
        {
            $states[$role] = $operation->capability->state->value;
            $abi = $operation->import;
            $operations[$role] = $abi === null ? null : new source_import($abi->link_name, $role,
                ['calling_convention' => $abi->calling_convention, 'return_type' => $abi->return_type,
                    'parameters' => array_map(static fn($parameter) => ['type' => $parameter->type,
                        'extension' => $parameter->extension->value], $abi->parameters),
                    'return_extension' => $abi->return_extension->value], $operation->capability->role->semantics());
        }
        // Each node is visited once; dependencies remain portable even when the compiler reallocates IDs.
        $nodes = [];
        $pending = [$layout->dependency];
        while ($pending !== [])
        {
            $node = array_pop($pending);
            $key = $task->identities[$node->type_id]->key;
            if (isset($nodes[$key])) {
                continue;
            }
            $definition = $node->definition;
            $life = $definition->lifetime;
            $imports = [];
            foreach (\type_model\lifecycle_operation_kind::cases() as $role)
            {
                $operation = $life?->operation($role);
                if ($operation instanceof \type_model\runtime_lifecycle_operation) {
                    $imports[$role->value] = [$operation->provider, $operation->id, $operation->link_name, $operation->calling_convention];
                }
            }
            $shape = $definition->representation;
            $payload = match ($shape->kind) {
                \type_model\representation_kind::structure => null,
                \type_model\representation_kind::fixed_array => ['count' => $shape->payload->count,
                    'element' => $task->identities[$shape->payload->element_type]->key],
                default => get_object_vars($shape->payload),
            };
            $nodes[$key] = ['kind' => $shape->kind->name, 'shape' => $payload, 'signed' => $definition->signed,
                'fields' => array_map(static fn($field) => [$field->name, $task->identities[$field->type_id]->key, $field->writable], $node->fields),
                'lifetime' => [$life?->construction->value, $life?->copy->value, $life?->assignment->value, $life?->cleanup->value],
                'runtime_operations' => $imports];
            array_push($pending, ...array_values($node->children));
        }
        ksort($nodes);
        $source = new source_type($task->project->project_key, $task->identity->key,
            ['triple' => $layout->configuration->target_triple, 'data_layout' => $layout->configuration->data_layout],
            $layout->size, $layout->alignment, ['offsets' => $layout->offsets, 'dependencies' => $nodes], $states, $operations);
        Adapter::validate($source);
        $cpp = Adapter::name($source);
        $identity = new \type_model\provider_type_reference(native\Symbols::name(['source', $source->project]), $source->key);
        $row = ['id' => 'source_argument', 'kind' => 'runtime_value', 'cpp_name' => $cpp, 'header' => 'source_types.hpp',
            'storage' => 'inline', 'lifecycle' => [], 'source_payload' => $source->key];
        $baseline = ($source->states['copy_construct'] === 'available') && ($source->states['copy_assign'] === 'available')
            && ($source->states['destroy'] === 'available');
        return new native\native_type($identity, $row, $baseline, ['cstddef'], [$cpp => Adapter::declaration($source)],
            json_encode($source, JSON_THROW_ON_ERROR), sources: [$source->key => $source]);
    }
}
