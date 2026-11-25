<?php
declare(strict_types=1);
namespace resolve_types;
/** A fixed source context or normalized provider declaration, sharing authoritative read inputs. */
final class Record_Task {
    public function __construct(public readonly \instantiate\Bindings $reader,
        public readonly \collect_symbols\Symbol_Store $symbols,
        public readonly ?\instantiate\Instance_Context $context = null,
        public readonly ?\type_model\Record_Declaration $provided = null) {
        if (($context === null) === ($provided === null)) { throw new \LogicException('Record task requires a source context or provider declaration'); }
        if ($context !== null) {
            if (!$context->definition->is_source()) { throw new \LogicException('Source record task requires source syntax'); }
        }
    }
    public function name(): string {
        if ($this->provided !== null) { return $this->provided->name; }
        $context = $this->context; if ($context === null) { throw new \LogicException('Missing record context'); }
        if ($context->instance_id !== 0) { return $context->type_name(); }
        return $context->definition->name;
    }
    public function namespace_name(): string {
        if ($this->provided !== null) { return $this->provided->namespace_name; }
        $context = $this->context; if ($context === null) { throw new \LogicException('Missing record context'); }
        if ($context->instance_id !== 0) { return $context->type_namespace(); }
        return $context->definition->namespace_name;
    }
    public function key(): string {
        $namespace_name = $this->namespace_name();
        return string_byte_len($namespace_name) . ':' . $namespace_name . $this->name();
    }
}
final class Record_Result {
    public function __construct(public readonly Record_Task $task, public readonly \type_model\Record_Declaration $declaration) {}
}
