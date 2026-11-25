<?php
declare(strict_types=1);
namespace check_bodies;
/** Fixed project membership in publication order. Rows are shared immutable bodies. */
final class Body_Set {
    private array $rows /** vector<Checked_Body> */ = [];
    private array $index /** hash<int,int> */ = [];
    public function __construct(array $bodies /** vector<Checked_Body> */) {
        foreach ($bodies as $body) {
            $id = $body->callable_id;
            if (isset($this->index[$id])) { throw new \LogicException('Duplicate checked body'); }
            $this->index[$id] = q_count($this->rows); $this->rows[] = $body;
        }
    }
    public function size(): int { return q_count($this->rows); }
    public function at(int $position): Checked_Body {
        if (($position < 0) || ($position >= q_count($this->rows))) { throw new \OutOfBoundsException('Missing checked body position'); }
        return $this->rows[$position];
    }
    public function for_callable(int $id): ?Checked_Body {
        if (!isset($this->index[$id])) { return null; }
        return $this->rows[$this->index[$id]];
    }
    public function for_symbol(int $id): ?Checked_Body {
        if ($id > \collect_symbols\MAX_SYMBOL_ID) { return null; }
        return $this->for_callable($id);
    }
    public function bodies(): array /** vector<Checked_Body> */ { return $this->rows; }
}
