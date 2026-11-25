<?php
declare(strict_types=1);
namespace check_bodies;
/** Completed value/call IDs are the consumer view; operand position is iterator-private state. */
final class Evaluation_Cursor {
    public int $next_operand = 0;
    public function __construct(public readonly int $value_id, public readonly int $call_id,
        public readonly int $operand_count, public readonly int $call_limit) {}
}
/** Streaming postorder of a completed checked expression; no lifetime actions or event list. */
final class Expression_Order {
    private array $pending /** vector<Evaluation_Cursor> */ = [];
    private int $depth = 0;
    private int $next_call = 0;
    private bool $started = false;
    private bool $done = false;
    public function __construct(private readonly Checked_Body $body, private readonly int $root,
        private readonly int $start, private readonly int $limit) { $this->next_call = $start; }
    public static function steps(Checked_Body $body, int $root, int $start, int $limit): Expression_Order {
        return new Expression_Order($body,$root,$start,$limit);
    }
    private function push(Evaluation_Cursor $frame): void {
        if ($this->depth === q_count($this->pending)) { $this->pending[] = $frame; }
        else { $this->pending[$this->depth] = $frame; }
        $this->depth++;
    }
    public function next(): ?Evaluation_Cursor {
        if ($this->done) { return null; }
        if (!$this->started) {
            $this->started = true;
            if (($this->start < 0) || ($this->limit < $this->start) || ($this->limit > $this->body->call_count())) {
                throw new \LogicException('Invalid checked expression call segment');
            }
            $call = 0;
            if ($this->root === 0) { if ($this->start < $this->limit) { $call = $this->limit; } }
            if ($call !== 0) {
                if ($this->body->call_for($call)->result_value_id !== 0) { throw new \LogicException('Missing checked expression result'); }
            }
            if (($this->root === 0) && ($call === 0)) { $this->done = true; return null; }
            $this->push($this->frame($this->root,$call,$this->limit+1));
        }
        while ($this->depth > 0) {
            $frame = $this->pending[$this->depth-1];
            if ($frame->next_operand < $frame->operand_count) {
                $index = $frame->next_operand; $frame->next_operand++;
                $id = 0;
                if ($frame->call_id !== 0) { $id = $this->body->argument_for($frame->call_id,$index+1)->value_id; }
                else {
                    $value = $this->body->value_for($frame->value_id);
                    if ($value->kind === \check_bodies\VALUE_CONVERSION) { $id = $this->body->conversion_for($frame->value_id)->input_value_id; }
                    elseif (($value->kind === \check_bodies\VALUE_LOCAL_READ) || ($value->kind === \check_bodies\VALUE_LOCAL_BORROW)) {
                        $projection = $value->place()->at($index);
                        if ($projection->kind === \check_bodies\PROJECTION_FIELD) { continue; }
                        $id = $projection->operand;
                    } else {
                        $operation = $this->body->operation_for($frame->value_id);
                        $id = $index === 0 ? $operation->left : $operation->right;
                    }
                }
                if ($id < 1) { throw new \LogicException('Invalid or cyclic checked operand'); }
                if ($frame->value_id !== 0) {
                    if ($id >= $frame->value_id) { throw new \LogicException('Invalid or cyclic checked operand'); }
                }
                $this->push($this->frame($id,0,$frame->call_limit));
                continue;
            }
            if ($frame->call_id !== 0) {
                $this->next_call++;
                if ($frame->call_id !== $this->next_call) { throw new \LogicException('Checked call evaluation order is inconsistent'); }
            }
            $this->depth = $this->depth-1;
            return $frame;
        }
        if ($this->next_call !== $this->limit) { throw new \LogicException('Incomplete checked expression call segment'); }
        $this->done = true;
        return null;
    }
    private function frame(int $value_id, int $call_id, int $bound): Evaluation_Cursor {
        $count = 0;
        if ($value_id !== 0) {
            if (($value_id < 1) || ($value_id > $this->body->value_count())) { throw new \LogicException('Missing checked value'); }
            $value = $this->body->value_for($value_id);
            if ($value->kind === \check_bodies\VALUE_CALL_RESULT) {
                $call_id = $value->call_id();
                if ($call_id > $this->body->call_count()) { throw new \LogicException('Inconsistent checked call result'); }
                if ($this->body->call_for($call_id)->result_value_id !== $value_id) { throw new \LogicException('Inconsistent checked call result'); }
            } elseif ($value->kind === \check_bodies\VALUE_CONVERSION) {
                $this->body->conversion_for($value_id); $count = 1;
            } elseif ($value->kind === \check_bodies\VALUE_OPERATION) {
                $this->body->operation_for($value_id); $count = 2;
            } elseif (($value->kind === \check_bodies\VALUE_LOCAL_READ) || ($value->kind === \check_bodies\VALUE_LOCAL_BORROW)) { $count = $value->place()->size(); }
        }
        if ($call_id !== 0) {
            if (($call_id < 1) || ($call_id >= $bound)) { throw new \LogicException('Call is repeated, cyclic or outside its expression segment'); }
            if ($call_id > $this->body->call_count()) { throw new \LogicException('Missing checked call'); }
            $call = $this->body->call_for($call_id); $signature = $this->body->signature_for($call->target_callable_id);
            $return_type = $signature->representation->signature_return();
            $is_void = $this->body->definition_for($return_type)->representation->kind() === \type_model\REPRESENTATION_VOID;
            if (($call->result_value_id === 0) !== $is_void) { throw new \LogicException('Inconsistent checked call result presence'); }
            if ($value_id !== 0) {
                if ($this->body->value_for($value_id)->type_id !== $return_type) { throw new \LogicException('Inconsistent checked call result type'); }
            }
            if (($call->argument_start < 0) || ($call->argument_count !== $signature->parameter_count())
                || ($call->argument_count > ($this->body->argument_count()-$call->argument_start))) { throw new \LogicException('Invalid checked call argument range'); }
            $count = $call->argument_count; $bound = $call_id;
        }
        return new Evaluation_Cursor($value_id,$call_id,$count,$bound);
    }
}
