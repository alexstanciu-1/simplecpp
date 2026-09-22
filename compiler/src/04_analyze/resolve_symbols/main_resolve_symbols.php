<?php
declare(strict_types=1);
namespace resolve_symbols;
/** Synchronous composition of the same fixed plan, workers and atomic acceptance. */
final class Symbol_Resolver {
    public static function resolve(\collect_symbols\Symbol_Store $symbols, Resolution_Set $previous,
        \type_model\Type_Catalog $catalog, bool $full): Resolution_Update {
        $plan = new Resolution_Plan($symbols,$previous,$catalog,$full); $results /** vector<Symbol_Resolution> */ = [];
        for ($position = 0; $position < $plan->task_count(); $position++) {
            $worker = new Resolution_Worker($symbols,$plan->task_at($position),$catalog); $attempt = $worker->run();
            if (!$attempt->valid()) { return new Resolution_Update(null,$attempt->error_path,$attempt->error_start,$attempt->error_length,$attempt->error_reason); }
            $results[] = $attempt->result();
        }
        $join = new Resolution_Join($plan); return new Resolution_Update($join->join($results),'',0,0,'');
    }
}
final class Resolution_Update {
    public function __construct(private readonly ?Resolution_Set $value, public readonly string $error_path,
        public readonly int $error_start, public readonly int $error_length, public readonly string $error_reason) {}
    public function valid(): bool { return $this->value !== null; }
    public function result(): Resolution_Set {
        if ($this->value === null) { throw new \LogicException('Project name resolution failed'); }
        return $this->value;
    }
}
