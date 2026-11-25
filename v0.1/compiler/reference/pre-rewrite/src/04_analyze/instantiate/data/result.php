<?php
declare(strict_types=1);

/*
 * Role: Accepted concrete contexts, application targets and literal constants.
 * Used by: preparation workers; annotation consumers; body checking
 * Flow: fixed batch joins -> Instance_Set -> read-only consumers
 */
namespace instantiate;

/** Immutable retained result published once after concrete preparation completes. */
final class Instance_Set implements \compile\Step_Result, Instance_View
{
    use Instance_Lookup;

    private readonly array $type_contexts;

    /** Keep registry history and allocation watermark even when current demands disappear. */
    public function __construct(public readonly array $contexts = [], public readonly array $uses = [],
        public readonly array $concrete_types = [], public readonly array $constants = [],
        public readonly array $keys = [], public readonly int $next_id = 1,
        public readonly array $constant_owners = [], public readonly array $source_bindings = [],
        public readonly ?\type_model\type_lineage $lineage = null,
        private readonly \check_templates\Template_Set $templates = new \check_templates\Template_Set())
    {
        $index = [];
        foreach ($concrete_types as $id => $definition) {
            $index[self::type_key($definition)] = $id;
        }
        $this->type_contexts = $index;
    }

    public function template_checks(): \check_templates\Template_Set
    {
        return $this->templates;
    }

    /** Enumerate concrete function instances only; type instances have no executable body. */
    public function functions(): array
    {
        return array_values(array_filter($this->contexts, static fn($context) =>
            ($context->definition->kind === \collect_symbols\symbol_kind::template_function)
            || ($context->definition->owner_symbol_id !== 0)));
    }

    /** Export concrete provenance on demand without duplicating retained type definitions. */
    public function to_array(): array
    {
        return ['instances' => array_map(static fn($context) => ['instance_id' => $context->instance_id,
            'definition_id' => $context->definition->symbol_id, 'context_id' => $context->context_id,
            'receiver' => $context->receiver_type === null ? null : ['name' => $context->receiver_type->name,
                'namespace' => $context->receiver_type->namespace_name],
            'arguments' => array_map(static fn($argument) => ['type' => $argument->type->name,
                'namespace' => $argument->type->namespace_name, 'value' => $argument->value], $context->arguments)],
            array_values($this->contexts)), 'applications' => $this->uses, 'template_checks' => $this->templates->to_array()];
    }
}
