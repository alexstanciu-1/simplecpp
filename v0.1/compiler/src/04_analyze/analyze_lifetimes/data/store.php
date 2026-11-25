<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Current ordered results and fixed ownership dependencies; rows retain shared identity. */
final class Lifetime_Set {
    private array $rows /** vector<Analyzed_Body> */ = [];
    private array $index /** hash<int,int> */ = [];
    private array $ownership_rows /** hash<Ownership_Result> */ = [];
    public function __construct(array $bodies /** vector<Analyzed_Body> */, array $ownership /** hash<Ownership_Result> */) {
        foreach ($bodies as $body) {
            $id = $body->body->callable_id;
            if (isset($this->index[$id])) { throw new \LogicException('Duplicate lifetime result'); }
            $this->index[$id] = q_count($this->rows); $this->rows[] = $body;
        }
        foreach ($ownership as $key => $result) { $this->ownership_rows[$key] = $result; }
    }
    public function for_callable(int $id): ?Analyzed_Body {
        if (!isset($this->index[$id])) { return null; }
        return $this->rows[$this->index[$id]];
    }
    public function for_symbol(int $id): ?Analyzed_Body {
        if ($id > \collect_symbols\MAX_SYMBOL_ID) { return null; }
        return $this->for_callable($id);
    }
    public function bodies(): array /** vector<Analyzed_Body> */ { return $this->rows; }
    public function ownership_results(): array /** hash<Ownership_Result> */ { return $this->ownership_rows; }
}
/** Fixed accepted resource inputs shared by selection and publication. */
final class Lifetime_Inputs {
    private array $rows /** hash<Ownership_Result> */ = [];
    public function __construct(array $ownership /** hash<Ownership_Result> */) {
        foreach ($ownership as $key => $result) {
            if ($key !== $result->task->key) { throw new \LogicException('Ownership input key mismatch'); }
            $this->rows[$key] = $result;
        }
    }
    public function for_body(int $id): ?Ownership_Result {
        $key = 'body:' . $id; if (!isset($this->rows[$key])) { return null; } return $this->rows[$key];
    }
    public function results(): array /** hash<Ownership_Result> */ { return $this->rows; }
}
