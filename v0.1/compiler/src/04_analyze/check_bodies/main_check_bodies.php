<?php
declare(strict_types=1);
namespace check_bodies;
/** Execute selected bodies into a private batch; no partial publication on source failure. */
final class Body_Checker {
    public static function check(\collect_symbols\Symbol_Store $symbols, \resolve_symbols\Resolution_Set $names,
        \resolve_types\Type_Resolution $types, Body_Set $previous, bool $full): Body_Update {
        $plan = new Body_Plan($symbols,$names,$types,$previous,$full);
        $results /** vector<Checked_Body> */ = [];
        for ($i = 0; $i < $plan->task_count(); $i++) {
            $task = $plan->task_at($i);
            $worker = Body_Worker::prepare($task->input,$task->names,$task->types);
            try { $results[] = $worker->check(); }
            catch (\RuntimeException $error) {
                $diagnostic = $worker->diagnostic();
                if ($diagnostic === null) { throw new \LogicException('Unexpected body worker runtime failure'); }
                return new Body_Update(null,$diagnostic);
            }
        }
        return new Body_Update((new Body_Join($plan))->join($results),null);
    }
}
/** Exactly one complete result or attributed semantic failure. */
final class Body_Update {
    public function __construct(private readonly ?Body_Set $value, public readonly ?\resolve_types\Annotation_Diagnostic $diagnostic) {
        if (($value === null) === ($diagnostic === null)) { throw new \InvalidArgumentException('Body update requires exactly one outcome'); }
    }
    public function valid(): bool { return $this->value !== null; }
    public function result(): Body_Set {
        $value = $this->value;
        if ($value === null) { throw new \LogicException('Body checking failed'); }
        return $value;
    }
}
