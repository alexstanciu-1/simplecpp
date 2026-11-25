<?php
declare(strict_types=1);
namespace resolve_symbols;
/** Private mutable maps; results only retain scope IDs and compact facts. */
final class Scope_Names {
    private array $locals /** hash<int> */ = [];
    private array $constants /** hash<int> */ = [];
    public function local(string $name): int { if (!isset($this->locals[$name])) { return 0; } return $this->locals[$name]; }
    public function constant(string $name): int { if (!isset($this->constants[$name])) { return 0; } return $this->constants[$name]; }
    public function add_local(string $name, int $id): void { $this->locals[$name] = $id; }
    public function add_constant(string $name, int $id): void { $this->constants[$name] = $id; }
    public function retire(): void {
        $empty /** hash<int> */ = []; $this->locals = $empty; $this->constants = $empty;
    }
}
final class Scope_Cursor {
    public function __construct(public readonly int $scope_id, public int $next_statement_id, public readonly bool $owns_scope) {}
}
final class Binding_Cursor {
    public function __construct(public readonly int $node_id, public readonly int $role,
        public readonly bool $siblings, public readonly string $description) {}
}
/** Shared scratch owner replaces PHP reference parameters; no semantic identity escapes. */
final class Expression_Stack {
    private array $rows /** vector<Binding_Cursor> */ = [];
    private int $used = 0;
    public function push(Binding_Cursor $row): void {
        if ($this->used === q_count($this->rows)) { $this->rows[] = $row; } else { $this->rows[$this->used] = $row; }
        $this->used++;
    }
    public function empty(): bool { return $this->used === 0; }
    public function pop(): Binding_Cursor {
        if ($this->used === 0) { throw new \LogicException('Empty expression stack'); }
        $this->used = $this->used - 1; return $this->rows[$this->used];
    }
}
