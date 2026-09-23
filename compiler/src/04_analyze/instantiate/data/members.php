<?php
declare(strict_types=1);
namespace instantiate;
/** A bound call occurrence or a selected method declaration, in one concrete environment. */
final class Member_Task {
    public function __construct(public readonly Instance_Context $context,
        public readonly int $use_node_id, public readonly int $receiver_node_id,
        public readonly ?\collect_symbols\Symbol_Record $declaration = null) {
        if ($declaration === null) {
            if (($use_node_id < 1) || ($receiver_node_id < 1)) { throw new \LogicException('Member call requires a bound occurrence and receiver'); }
        } else {
            if (($use_node_id !== 0) || ($receiver_node_id !== 0)) { throw new \LogicException('Member request requires a call or a declaration'); }
        }
    }
    public static function occurrence(Instance_Context $context, \resolve_symbols\Member_Call_Binding $use): Member_Task {
        return new Member_Task($context,(int)$use->use_node_id,(int)$use->receiver_node_id);
    }
    public static function definition(Instance_Context $context, \collect_symbols\Symbol_Record $declaration): Member_Task {
        return new Member_Task($context,0,0,$declaration);
    }
    public function key(): string {
        if ($this->declaration !== null) { return $this->context->context_id . ':definition:' . $this->declaration->symbol_id; }
        return $this->context->context_id . ':call:' . $this->use_node_id;
    }
    public function source_node(): int {
        if ($this->declaration !== null) { return (int)$this->declaration->source_fact()->declaration_node_id; }
        return $this->use_node_id;
    }
}
/** Pending receiver, or an exact concrete receiver/method pair with inherited arguments. */
final class Member_Result {
    private array $ordered_arguments /** vector<Template_Argument> */ = [];
    public function __construct(public readonly Member_Task $task,
        public readonly ?\type_model\Named_Definition $receiver,
        public readonly ?\collect_symbols\Symbol_Record $definition,
        array $arguments /** vector<Template_Argument> */) {
        if ($receiver === null) {
            if (($definition !== null) || (q_count($arguments) !== 0)) { throw new \LogicException('Pending member result contains a concrete target'); }
        } else {
            if ($definition === null) { throw new \LogicException('Ready member result requires its method'); }
        }
        foreach ($arguments as $argument) { $this->ordered_arguments[] = $argument; }
    }
    public function arguments(): array /** vector<Template_Argument> */ { return $this->ordered_arguments; }
    public function argument_count(): int { return q_count($this->ordered_arguments); }
    public function argument_at(int $index): Template_Argument {
        if (($index < 0) || ($index >= q_count($this->ordered_arguments))) { throw new \OutOfBoundsException('Missing member argument'); }
        return $this->ordered_arguments[$index];
    }
}
