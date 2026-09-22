<?php
declare(strict_types=1);

/*
 * Role: Read contract for fixed preparation batches and retained instance results.
 * Used by: specialization/record workers and their joins
 * Flow: private registry between batches -> fixed worker reads -> retained snapshot
 */
namespace instantiate;

/** Queries expose no mutation; the coordinator fixes the registry for each worker batch. */
interface Instance_View
{
    public function template_checks(): \check_templates\Template_Set;
    public function context_for(int $id): ?instance_context;
    public function constant_for(int $id): ?template_argument;
    public function instance_type(int $id): ?\type_model\named_type_definition;
    public function type_context(\type_model\named_type_definition $type): ?instance_context;
    public function application(instance_context $context, int $node): ?instance_context;
    public function type_for(instance_context $context, int $node): ?\type_model\named_type_definition;
}

/** Shared lookup semantics over the registry's owned arrays and the retained snapshot's readonly arrays. */
trait Instance_Lookup
{
    public function context_for(int $id): ?instance_context
    {
        return $this->contexts[$id] ?? null;
    }

    public function constant_for(int $id): ?template_argument
    {
        return $this->constants[$id] ?? null;
    }

    public function instance_type(int $id): ?\type_model\named_type_definition
    {
        return $this->concrete_types[$id] ?? null;
    }

    public function type_context(\type_model\named_type_definition $type): ?instance_context
    {
        $id = $this->type_contexts[self::type_key($type)] ?? 0;
        return $id === 0 ? null : $this->context_for(\collect_symbols\MAX_SYMBOL_ID + $id);
    }

    public function application(instance_context $context, int $node): ?instance_context
    {
        return $this->context_for($this->uses[$context->context_id][$node] ?? 0);
    }

    public function type_for(instance_context $context, int $node): ?\type_model\named_type_definition
    {
        $instance = $this->application($context, $node);
        return $instance === null ? null : $this->instance_type($instance->instance_id);
    }

    private static function type_key(\type_model\named_type_definition $type): string
    {
        return strlen($type->namespace_name) . ':' . $type->namespace_name . $type->name;
    }
}
