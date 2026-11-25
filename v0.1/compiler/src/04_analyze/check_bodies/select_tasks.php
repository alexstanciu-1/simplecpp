<?php
declare(strict_types=1);
namespace check_bodies;
/** Fixed callable task. Construction does not execute body checking. */
final class Body_Task {
    public function __construct(public readonly \resolve_types\Callable_Input $input,
        public readonly \resolve_symbols\Symbol_Resolution $names, public readonly \resolve_types\Type_Resolution $types) {}
}
/** Selection owns exact source inputs and deterministic current-signature order. */
final class Body_Plan {
    private array $inputs /** vector<\resolve_types\Callable_Input> */ = [];
    private array $tasks /** vector<Body_Task> */ = [];
    private array $selected /** hash<int,int> */ = [];
    public function __construct(public readonly \collect_symbols\Symbol_Store $symbols,
        public readonly \resolve_symbols\Resolution_Set $names, public readonly \resolve_types\Type_Resolution $types,
        public readonly Body_Set $previous, bool $full) {
        if ($types->names !== $names) { throw new \LogicException('Body plan requires the type snapshot name owner'); }
        foreach ($types->body_signatures() as $signature) {
            $input = $signature->input;
            $this->require_input($input);
            $bindings = $names->for_symbol($input->owner->symbol_id);
            if ($bindings === null) { throw new \LogicException('Body selection requires current callable name bindings'); }
            if ($bindings->owner !== $input->owner) { throw new \LogicException('Body selection requires current callable name bindings'); }
            $this->inputs[] = $input;
            $reuse = false;
            if (!$full) {
                $old = $previous->for_callable($input->callable_id);
                if ($old !== null) { $reuse = Body_Validity::is_current($old,$input,$names,$types); }
            }
            if (!$reuse) {
                $this->selected[$input->callable_id] = q_count($this->tasks);
                $this->tasks[] = new Body_Task($input,$bindings,$types);
            }
        }
    }
    public function require_input(\resolve_types\Callable_Input $input): void {
        $owner = $input->owner;
        if (!$owner->is_source()) { throw new \LogicException('Body plan requires a source callable'); }
        if (!$this->symbols->contains($owner->symbol_id)) { throw new \LogicException('Stale body declaration'); }
        if ($this->symbols->symbol_by_id($owner->symbol_id) !== $owner) { throw new \LogicException('Stale body declaration'); }
        if ((int)$owner->source_fact()->body_node_id === 0) { throw new \LogicException('Body plan requires a callable body'); }
        $signature = $this->types->for_callable($input->callable_id);
        if ($signature === null) { throw new \LogicException('Stale body signature'); }
        if (($signature->input->owner !== $owner) || ($signature->input->instance !== $input->instance)) { throw new \LogicException('Stale body signature'); }
        if ($input->instance !== null) {
            if ($this->types->instances->context_for($input->callable_id) !== $input->instance) { throw new \LogicException('Stale body instance'); }
        }
    }
    public function input_count(): int { return q_count($this->inputs); }
    public function input_at(int $position): \resolve_types\Callable_Input {
        if (($position < 0) || ($position >= q_count($this->inputs))) { throw new \OutOfBoundsException('Missing body input position'); }
        return $this->inputs[$position];
    }
    public function task_count(): int { return q_count($this->tasks); }
    public function task_at(int $position): Body_Task {
        if (($position < 0) || ($position >= q_count($this->tasks))) { throw new \OutOfBoundsException('Missing body task position'); }
        return $this->tasks[$position];
    }
    public function selected_task(int $id): ?Body_Task {
        if (!isset($this->selected[$id])) { return null; }
        return $this->tasks[$this->selected[$id]];
    }
}
