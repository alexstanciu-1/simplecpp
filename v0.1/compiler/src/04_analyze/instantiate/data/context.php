<?php
declare(strict_types=1);
namespace instantiate;
/** Null denotes a type argument; non-null text is an already checked exact integer value. */
final class Template_Argument {
    public function __construct(public readonly \type_model\Named_Definition $type, public readonly ?string $value = null) {}
}

/** Fixed source provenance and concrete arguments; syntax/binding rows are never cloned. */
final class Instance_Context {
    public readonly int $context_id;
    private array $arguments /** vector<Template_Argument> */ = [];
    public function __construct(public readonly \collect_symbols\Symbol_Record $definition,
        public readonly int $instance_id, array $arguments /** vector<Template_Argument> */,
        public readonly ?\type_model\Named_Definition $receiver_type = null) {
        if (($instance_id<0) || ($instance_id>\collect_symbols\MAX_SYMBOL_ID)) { throw new \LogicException('Invalid concrete instance context'); }
        if ($instance_id===0) {
            if ((q_count($arguments)!==0) || $definition->is_template()) { throw new \LogicException('Ordinary context cannot carry template arguments or a template declaration'); }
        } else {
            if (!$definition->is_template()) {
                if ($definition->owner_symbol_id===0) { throw new \LogicException('Concrete instance requires a template or an owned declaration'); }
            }
        }
        if ($receiver_type!==null) {
            if (($instance_id===0) || ($definition->owner_symbol_id===0)) { throw new \LogicException('Concrete receiver requires an owned callable instance'); }
        }
        foreach ($arguments as $argument) { $this->arguments[]=$argument; }
        $this->context_id=$instance_id===0 ? $definition->symbol_id : \collect_symbols\MAX_SYMBOL_ID+$instance_id;
    }
    public static function ordinary(\collect_symbols\Symbol_Record $definition): Instance_Context {
        $none /** vector<Template_Argument> */ = []; return new Instance_Context($definition,0,$none);
    }
    public function argument_count(): int { return q_count($this->arguments); }
    public function argument_at(int $index): Template_Argument {
        if (($index<0) || ($index>=q_count($this->arguments))) { throw new \LogicException('Missing concrete template argument'); }
        return $this->arguments[$index];
    }
    public function type_name(): string { return 'instance_' . $this->instance_id; }
    public function type_namespace(): string { return (string_byte_from_int(0) . 'template_instance'); }
}
