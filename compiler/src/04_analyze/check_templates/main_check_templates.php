<?php
declare(strict_types=1);
namespace check_templates;
/** Execute the selected workers without publishing a partial permission set. */
final class Template_Checker {
    public static function check(\collect_symbols\Symbol_Store $symbols, \resolve_symbols\Resolution_Set $names,
        \type_model\Type_Catalog $catalog, Template_Set $previous, bool $full): Template_Update {
        $plan = new Template_Plan($symbols,$names,$catalog,$previous,$full);
        $results /** vector<Definition_Result> */ = [];
        for ($position = 0; $position < $plan->task_count(); $position++) {
            $worker = Template_Worker::create($plan->task_at($position),$symbols,$names,$catalog);
            try { $results[] = $worker->check(); }
            catch (\RuntimeException $error) {
                $diagnostic = $worker->diagnostic();
                if ($diagnostic === null) { throw new \LogicException('Unexpected template worker runtime failure'); }
                return new Template_Update(null,$diagnostic);
            }
        }
        $join = new Template_Join($plan);
        return new Template_Update($join->join($results),null);
    }
}
/** Semantic failure carries its source diagnostic and exposes no partial results. */
final class Template_Update {
    public function __construct(private readonly ?Template_Set $value, public readonly ?Template_Diagnostic $diagnostic) {
        if (($value === null) === ($diagnostic === null)) { throw new \InvalidArgumentException('Template update requires exactly one outcome'); }
    }
    public function valid(): bool { return $this->value !== null; }
    public function result(): Template_Set {
        $result = $this->value;
        if ($result === null) { throw new \LogicException('Template definition checking failed'); }
        return $result;
    }
}
