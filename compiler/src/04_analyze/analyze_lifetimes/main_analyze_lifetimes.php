<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Ownership acceptance precedes lifetime selection; publication requires the complete batch. */
final class Lifetime_Analyzer {
    private bool $used = false;
    private ?Lifetime_Inputs $inputs = null;
    public function __construct(private readonly \check_bodies\Body_Set $bodies,
        private readonly ?\type_model\Type_Store $types, private readonly Lifetime_Set $previous,
        private readonly bool $full) {}
    public function analyze(): Lifetime_Update {
        if ($this->used) { throw new \LogicException('Lifetime analyzer is one-shot'); } $this->used = true;
        $requests = (new Ownership_Selection($this->bodies,$this->types))->select();
        $queue = new Ownership_Queue($requests,$this->previous->ownership_results(),$this->full);
        try { $this->inputs = new Lifetime_Inputs($queue->run()); }
        catch (\RuntimeException $error) {
            $diagnostic = $queue->diagnostic();
            if ($diagnostic === null) { throw $error; }
            return new Lifetime_Update(null,$diagnostic);
        }
        $inputs = $this->inputs; if ($inputs === null) { throw new \LogicException('Missing accepted ownership phase'); }
        $tasks = Lifetime_Selection::select($this->bodies,$this->previous,$inputs,$this->full);
        $results /** vector<Analyzed_Body> */ = [];
        foreach ($tasks as $task) {
            $worker = new Lifetime_Worker($task,$inputs->for_body($task->callable_id));
            try { $results[] = $worker->analyze(); }
            catch (\RuntimeException $error) {
                $diagnostic = $worker->diagnostic();
                if ($diagnostic === null) { throw $error; }
                return new Lifetime_Update(null,$diagnostic);
            }
        }
        return new Lifetime_Update((new Lifetime_Join($this->bodies,$this->previous,$tasks,$inputs))->join($results),null);
    }
}
/** Exactly one complete result or attributed semantic failure, never partial results. */
final class Lifetime_Update {
    public function __construct(private readonly ?Lifetime_Set $value, public readonly ?\resolve_types\Annotation_Diagnostic $diagnostic) {
        if (($value === null) === ($diagnostic === null)) { throw new \InvalidArgumentException('Lifetime update requires exactly one outcome'); }
    }
    public function valid(): bool { return $this->value !== null; }
    public function result(): Lifetime_Set {
        $value = $this->value; if ($value === null) { throw new \LogicException('Lifetime analysis failed'); } return $value;
    }
}
