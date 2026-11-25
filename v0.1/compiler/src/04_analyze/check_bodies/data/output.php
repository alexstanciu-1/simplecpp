<?php
declare(strict_types=1);
namespace check_bodies;

/** Private checking row: a location awaits consumer-selected access, or a value is complete. */
final class Body_Value_Row {
    public function __construct(public readonly int $source_node_id, public readonly int $type_id,
        public readonly ?Place $pending, public readonly ?Typed_Value $completed) {
        if (($pending === null) === ($completed === null)) { throw new \LogicException('Expected one checking value state'); }
        if ($completed !== null) {
            if (($completed->source_node_id !== $source_node_id) || ($completed->type_id !== $type_id)) {
                throw new \LogicException('Inconsistent checking value identity');
            }
        }
    }
}
final class Body_Argument_Slot {
    public function __construct(public readonly ?Typed_Argument $value = null) {}
}
final class Body_Scope_Slot {
    public function __construct(public readonly ?Typed_Scope $value = null) {}
}

/** Per-worker output ownership. IDs are one-based; contiguous range offsets are zero-based.
 * Scope/argument slots and unresolved places never escape into a Checked_Body.
 * Type retention and source-level permissions belong to Body_Worker, not this buffer.
 */
final class Body_Output {
    private array $values /** vector<Body_Value_Row> */ = [];
    private array $calls /** vector<Typed_Call> */ = [];
    private array $statements /** vector<Typed_Statement> */ = [];
    private array $arguments /** vector<Body_Argument_Slot> */ = [];
    private array $scopes /** vector<Body_Scope_Slot> */ = [];
    private int $pending_places = 0;
    private int $pending_arguments = 0;
    private int $pending_scopes = 0;

    public function __construct(int $scope_count) {
        if ($scope_count < 0) { throw new \LogicException('Invalid scope count'); }
        for ($i = 0; $i < $scope_count; $i++) { $this->scopes[] = new Body_Scope_Slot(); }
        $this->pending_scopes = $scope_count;
    }
    public function value_count(): int { return q_count($this->values); }
    public function call_count(): int { return q_count($this->calls); }
    public function statement_count(): int { return q_count($this->statements); }
    public function argument_count(): int { return q_count($this->arguments); }
    public function append_value(Typed_Value $value): int {
        $this->values[] = new Body_Value_Row($value->source_node_id, $value->type_id, null, $value);
        return q_count($this->values);
    }
    public function append_place(int $node, int $type, Place $location): int {
        $this->values[] = new Body_Value_Row($node, $type, $location, null);
        $this->pending_places++;
        return q_count($this->values);
    }
    public function value_for(int $id): Body_Value_Row {
        if (($id < 1) || ($id > q_count($this->values))) { throw new \OutOfBoundsException('Unknown checking value'); }
        return $this->values[$id - 1];
    }
    /** A second identical access is harmless; changing a selected access is an error. */
    public function select_place_access(int $id, bool $borrow): void {
        if ($id === 0) { return; }
        $row = $this->value_for($id);
        $kind = $borrow ? \check_bodies\VALUE_LOCAL_BORROW : \check_bodies\VALUE_LOCAL_READ;
        $location = $row->pending;
        if ($location !== null) {
            $value = new Typed_Value($row->source_node_id, $row->type_id, $kind, '', 0, $location);
            $this->values[$id - 1] = new Body_Value_Row($row->source_node_id, $row->type_id, null, $value);
            $this->pending_places = $this->pending_places - 1;
            return;
        }
        $value = $row->completed;
        if ($value === null) { throw new \LogicException('Missing checking value'); }
        $selected = ($value->kind === \check_bodies\VALUE_LOCAL_READ) || ($value->kind === \check_bodies\VALUE_LOCAL_BORROW);
        if ($selected) {
            if ($value->kind !== $kind) { throw new \LogicException('Location access was already selected by another consumer'); }
        }
    }
    /** Reserve the parent's argument range before checking nested calls. */
    public function reserve_arguments(int $count): int {
        if ($count < 0) { throw new \LogicException('Invalid argument reservation'); }
        $start = q_count($this->arguments);
        for ($i = 0; $i < $count; $i++) { $this->arguments[] = new Body_Argument_Slot(); }
        $this->pending_arguments = $this->pending_arguments + $count;
        return $start;
    }
    public function complete_argument(int $offset, Typed_Argument $value): void {
        if (($offset < 0) || ($offset >= q_count($this->arguments))) { throw new \OutOfBoundsException('Unknown argument slot'); }
        if ($this->arguments[$offset]->value !== null) { throw new \LogicException('Argument slot already completed'); }
        $this->arguments[$offset] = new Body_Argument_Slot($value);
        $this->pending_arguments = $this->pending_arguments - 1;
    }
    public function argument_at(int $offset): Typed_Argument {
        if (($offset < 0) || ($offset >= q_count($this->arguments))) { throw new \OutOfBoundsException('Unknown argument slot'); }
        $value = $this->arguments[$offset]->value;
        if ($value === null) { throw new \LogicException('Argument slot is unfinished'); }
        return $value;
    }
    public function complete_scope(int $id, Typed_Scope $value): void {
        if (($id < 1) || ($id > q_count($this->scopes))) { throw new \OutOfBoundsException('Unknown scope slot'); }
        if ($this->scopes[$id - 1]->value !== null) { throw new \LogicException('Scope slot already completed'); }
        $this->scopes[$id - 1] = new Body_Scope_Slot($value);
        $this->pending_scopes = $this->pending_scopes - 1;
    }
    public function append_call(Typed_Call $value): int {
        $this->calls[] = $value;
        return q_count($this->calls);
    }
    public function call_for(int $id): Typed_Call {
        if (($id < 1) || ($id > q_count($this->calls))) { throw new \OutOfBoundsException('Unknown checking call'); }
        return $this->calls[$id - 1];
    }
    public function append_statement(Typed_Statement $value): void { $this->statements[] = $value; }
    public function require_complete(): void {
        if ($this->pending_places !== 0) { throw new \LogicException('Checked body has unresolved location access'); }
        if ($this->pending_arguments !== 0) { throw new \LogicException('Checked body has unfinished arguments'); }
        if ($this->pending_scopes !== 0) { throw new \LogicException('Checked body has unfinished scopes'); }
    }
    public function completed_values(): array /** vector<Typed_Value> */ {
        $this->require_complete();
        $result /** vector<Typed_Value> */ = [];
        foreach ($this->values as $row) {
            $value = $row->completed;
            if ($value === null) { throw new \LogicException('Missing completed value'); }
            $result[] = $value;
        }
        return $result;
    }
    public function completed_arguments(): array /** vector<Typed_Argument> */ {
        $this->require_complete();
        $result /** vector<Typed_Argument> */ = [];
        foreach ($this->arguments as $slot) {
            $value = $slot->value;
            if ($value === null) { throw new \LogicException('Missing completed argument'); }
            $result[] = $value;
        }
        return $result;
    }
    public function completed_scopes(): array /** vector<Typed_Scope> */ {
        $this->require_complete();
        $result /** vector<Typed_Scope> */ = [];
        foreach ($this->scopes as $slot) {
            $value = $slot->value;
            if ($value === null) { throw new \LogicException('Missing completed scope'); }
            $result[] = $value;
        }
        return $result;
    }
    public function completed_calls(): array /** vector<Typed_Call> */ { $this->require_complete(); return $this->calls; }
    public function completed_statements(): array /** vector<Typed_Statement> */ { $this->require_complete(); return $this->statements; }
}
