<?php
declare(strict_types=1);
namespace resolve_symbols;
/** Accepted project results. Only publish() can populate private membership. */
final class Resolution_Set {
    private array $rows /** vector<Symbol_Resolution> */ = [];
    private array $by_symbol /** hash<int,int> */ = [];
    public function __construct(private readonly ?\collect_symbols\Symbol_Store $symbols) {}
    public function size(): int { return q_count($this->rows); }
    public function at(int $position): Symbol_Resolution {
        if (($position < 0) || ($position >= q_count($this->rows))) { throw new \InvalidArgumentException('Invalid resolution position'); }
        return $this->rows[$position];
    }
    public function for_symbol(int $id): ?Symbol_Resolution {
        if (!isset($this->by_symbol[$id])) { return null; }
        return $this->rows[$this->by_symbol[$id]];
    }
    public function declaration_for(int $id): \collect_symbols\Symbol_Record {
        if ($this->symbols === null) { throw new \LogicException('Empty resolution baseline has no declarations'); }
        return $this->symbols->symbol_by_id($id);
    }
    /** Atomic full membership publication; shared accepted results avoid a second structural walk. */
    public static function publish(\collect_symbols\Symbol_Store $symbols, \type_model\Type_Catalog $catalog,
        Resolution_Set $previous, array $results /** vector<Symbol_Resolution> */): Resolution_Set {
        if (q_count($results) !== $symbols->size()) { throw new \LogicException('Incomplete resolution membership'); }
        $out = new Resolution_Set($symbols);
        foreach ($results as $result) {
            $id = $result->owner->symbol_id;
            if (isset($out->by_symbol[$id])) { throw new \LogicException('Duplicate resolution owner'); }
            if (!$symbols->contains($id)) { throw new \LogicException('Removed resolution owner'); }
            if (!Resolution_Validity::is_current($result,$symbols->symbol_by_id($id),$symbols,$catalog)) { throw new \LogicException('Stale resolution dependencies'); }
            if ($previous->for_symbol($id) !== $result) {
                if (!Binding_Coverage::complete($result)) { throw new \LogicException('Incomplete or incorrect resolution coverage'); }
            }
            $out->by_symbol[$id] = q_count($out->rows); $out->rows[] = $result;
        }
        return $out;
    }
}
