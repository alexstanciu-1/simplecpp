<?php
declare(strict_types=1);

/*
 * Role: Concrete contexts and private explicit-application work.
 * Used by: Concrete_Preparation; type workers; checking
 * Flow: declaration + ordered arguments -> concrete context -> ordinary consumers
 */
namespace instantiate;

/** Exact typed argument; null value denotes a type argument, decimal text an integer argument. */
final class template_argument {
    public function __construct(public readonly \type_model\named_type_definition $type,
        public readonly ?string $value = null)
    {
    }
}

/** Original source provenance and instance arguments; no syntax or binding rows are copied. */
final class instance_context
{
    public readonly int $context_id;

    /** Ordinary contexts encode domain zero; instances encode domain one and an allocated ID. */
    public function __construct(public readonly \collect_symbols\symbol_record $definition,
        public readonly int $instance_id = 0, public readonly array $arguments = [],
        public readonly ?\type_model\named_type_definition $receiver_type = null)
    {
        if (($instance_id < 0) || ($instance_id > \collect_symbols\MAX_SYMBOL_ID)
            || (($instance_id === 0) && (($arguments !== []) || $definition->is_template()))
            || (($instance_id !== 0) && !$definition->is_template() && ($definition->owner_symbol_id === 0))) {
            throw new \LogicException('Invalid concrete instance context');
        }
        if (($receiver_type !== null) && (($instance_id === 0) || ($definition->owner_symbol_id === 0))) {
            throw new \LogicException('Concrete receiver requires an owned callable instance');
        }
        if (!array_is_list($arguments)) {
            throw new \LogicException('Instance arguments must be an ordered list');
        }
        foreach ($arguments as $argument) {
            if (!$argument instanceof template_argument) {
                throw new \LogicException('Invalid instance argument');
            }
        }
        $this->context_id = $instance_id === 0 ? $definition->symbol_id : \collect_symbols\MAX_SYMBOL_ID + $instance_id;
    }

    public function type_name(): string
    {
        return 'instance_' . $this->instance_id;
    }

    public function type_namespace(): string
    {
        return "\0template_instance";
    }
}

/** One source application in one fixed concrete argument environment. */
final class application_task {
    public function __construct(public readonly instance_context $context,
        public readonly \resolve_symbols\template_application_binding $application)
    {
    }
}

/** Private normalized arguments; null means a required record or type application is not ready. */
final class application_result
{
    public function __construct(public readonly application_task $task, public readonly ?array $arguments,
        public readonly array $prerequisites = [])
    {
        if (($arguments === null) !== ($prerequisites !== [])) {
            throw new \LogicException('Application result requires arguments or explicit prerequisites');
        }
    }
}

/** Fixed member occurrence or ordinary declaration; both prepare a concrete receiver/target. */
final class member_task
{
    public function __construct(public readonly instance_context $context,
        public readonly ?\resolve_symbols\member_call_binding $use = null,
        public readonly ?\collect_symbols\symbol_record $declaration = null)
    {
        if (($use === null) === ($declaration === null)) {
            throw new \LogicException('Member request requires an occurrence or an ordinary declaration');
        }
    }

    public function key(): string
    {
        return $this->context->context_id . ':' . ($this->use === null
            ? 'definition:' . $this->declaration->symbol_id : 'call:' . $this->use->use_node_id);
    }
}

final class member_result
{
    public function __construct(public readonly member_task $task,
        public readonly ?\type_model\named_type_definition $receiver,
        public readonly ?\collect_symbols\symbol_record $definition = null,
        public readonly array $arguments = [])
    {
    }
}
