<?php
declare(strict_types=1);
namespace runtime_preparation\families;

use runtime_preparation\native_type;
use runtime_preparation\Symbols;
use type_model as model;

/** Bind formal references at declared type positions and project to the concrete generator boundary. */
final class Requests
{
    /** Formation is checked independently of requested operations, before invoking Clang. */
    public static function arguments(family_catalog $catalog, family_binding $family, array $ids): array
    {
        if (!array_is_list($ids) || (count($ids) !== count($family->semantic->parameters))) {
            throw new \RuntimeException('Family type-argument arity mismatch');
        }
        $arguments = Arguments::resolve($catalog, $ids);
        foreach ($arguments as $slot => $argument) {
            if (isset($argument->definition['source_payload']) && !in_array(\runtime_preparation\project\source_type::PROFILE, $family->source_profiles[$slot] ?? [], true)) {
                throw new \RuntimeException('Family parameter does not authorize the source adapter profile');
            }
        }
        return $arguments;
    }

    /** Lifecycle implementations are required dependencies, not a caller-selected optional capability. */
    public static function coverage(family_binding $family, array $selected): array
    {
        foreach ($selected as $id) {
            if (!is_string($id)) {
                throw new \RuntimeException('Operation demands require exact identities');
            }
        }
        if (!array_is_list($selected) || (count(array_unique($selected)) !== count($selected))) {
            throw new \RuntimeException('Invalid or duplicate operation demand');
        }
        $selected = array_values(array_unique([...$selected, ...array_values($family->semantic->lifecycle)]));
        foreach ($selected as $id) {
            if (!is_string($id) || !isset($family->operations[$id])) {
                throw new \RuntimeException('Unknown demanded operation');
            }
        }
        sort($selected);
        return $selected;
    }

    /** Allocate a package-local generated type name without reserving a user catalog spelling. */
    public static function instance_id(family_catalog $catalog): string
    {
        $id = 'instance';
        $next = 1;
        while (isset($catalog->types[$id])) {
            $id = 'instance_' . $next++;
        }
        return $id;
    }

    /** Produce ordinary definition rows only at the existing concrete reader boundary.
     * @param list<native_type> $arguments @param list<string> $coverage */
    public static function export(family_binding $family, array $arguments, array $coverage, string $provider, string $id): array
    {
        $cpp = Symbols::name(['specialization', $provider, $id]);
        $declaration = 'using ' . $cpp . ' = ' . $family->cpp_name . '<'
            . implode(', ', array_map(static fn($type) => $type->definition['cpp_name'], $arguments)) . ">;\n";
        $lifecycle = [];
        foreach ($family->semantic->lifecycle as $role => $operation) {
            $lifecycle[$role] = $id . '.' . $operation;
        }
        // An explicitly declared empty constructor also supplies implicit default construction.
        $constructor = $family->semantic->operations[$family->semantic->lifecycle['construct']];
        if ($constructor->signature->parameters === []) {
            $lifecycle['default_construct'] = $lifecycle['construct'];
        }
        $type = ['id' => $id, 'cpp_name' => $cpp, 'header' => 'source_types.hpp',
            'kind' => 'runtime_value', 'storage' => 'inline', 'lifecycle' => $lifecycle];
        $operations = [];
        $bindings = [];
        foreach ($coverage as $selected)
        {
            $binding = $family->operations[$selected];
            $operation = ['id' => $id . '.' . $selected, 'kind' => $binding->kind, 'error_policy' => 'terminate'];
            if ($binding->object_type !== null) {
                $operation['type'] = self::resolve($binding->object_type, $family, $arguments, $id);
            }
            if (in_array($binding->kind, ['construct', 'free_function'], true))
            {
                $operation['parameters'] = [];
                foreach ($binding->semantic->signature->parameters as $index => $parameter)
                {
                    $resolved = self::resolve($parameter->type, $family, $arguments, $id);
                    $adaptation = $binding->parameter_adaptations[$index] ?? [];
                    if ($parameter->passing->is_borrow())
                    {
                        $operation['parameters'][] = ['type' => $resolved, 'passing' =>
                            $parameter->passing === model\argument_passing::borrow_const ? 'const_address' : 'mutable_address',
                            'borrow_scope' => 'call'];
                        if (isset($arguments[$parameter->type->slot ?? -1]->definition['source_payload']))
                        {
                            if (($adaptation['source_payload'] ?? null) !== 'copy_in') {
                                throw new \RuntimeException('Family operation does not authorize source payload copy-in');
                            }
                            $operation['parameters'][array_key_last($operation['parameters'])]['source_payload'] = 'copy_in';
                        }
                    }
                    else {
                        $operation['parameters'][] = $adaptation === [] ? $resolved
                            : ['type' => $resolved, 'passing' => 'direct', ...$adaptation];
                    }
                }
            }
            if ($binding->kind === 'free_function')
            {
                $operation['cpp_name'] = $binding->cpp_name;
                $operation['header'] = $binding->header;
                $operation['cpp_template_arguments'] = array_map(static fn($ref) => self::resolve($ref, $family, $arguments, $id),
                    $binding->template_arguments);
                $reference = $binding->semantic->signature->result->type;
                $operation['result_type'] = self::resolve($reference, $family, $arguments, $id);
                if (($reference instanceof model\parameter_type_reference) && isset($arguments[$reference->slot]->definition['source_payload']))
                {
                    if ($binding->source_result !== 'copy_out') {
                        throw new \RuntimeException('Family operation does not authorize source payload copy-out');
                    }
                    $operation['source_payload_result'] = 'copy_out';
                }
            }
            $operations[] = $operation;
            $bindings[$selected] = $operation['id'];
        }
        return ['declaration' => $declaration, 'type' => $type, 'operations' => $operations, 'bindings' => $bindings];
    }

    /** A formal belongs to one definition; matching layout or slot alone never establishes identity. */
    public static function resolve(model\type_reference $reference, family_binding $family, array $arguments, string $self): string
    {
        if ($reference instanceof model\provider_type_reference) {
            if ($reference->provider !== $family->semantic->provider) {
                throw new \RuntimeException('Foreign provider type reference');
            }
            return $reference->id;
        }
        if ($reference instanceof model\parameter_type_reference) {
            if (($reference->owner !== $family->semantic->key()) || !isset($arguments[$reference->slot])) {
                throw new \RuntimeException('Foreign or unavailable formal type reference');
            }
            return $arguments[$reference->slot]->definition['id'];
        }
        if (($reference instanceof model\family_type_reference) && ($reference->family === $family->semantic->key())) {
            return $self;
        }
        throw new \RuntimeException('Unsupported family type application');
    }
}
