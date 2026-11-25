<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Dependency-ready batches, exact input capture and acceptance before dependents are released. */
final class Ownership_Queue {
    private array $nodes /** hash<Ownership_Queue_Node> */ = [];
    private array $order /** vector<string> */ = [];
    private array $previous /** hash<Ownership_Result> */ = [];
    private bool $started = false;
    private ?\resolve_types\Annotation_Diagnostic $failure = null;
    public function __construct(array $requests /** vector<Ownership_Request> */, array $previous /** hash<Ownership_Result> */,
        private readonly bool $full) {
        foreach ($requests as $request) {
            $key = $request->key; if (isset($this->nodes[$key])) { throw new \LogicException('Duplicate ownership request'); }
            $this->nodes[$key] = new Ownership_Queue_Node($request); $this->order[] = $key;
        }
        foreach ($this->order as $key) {
            foreach ($this->nodes[$key]->request->dependencies() as $dependency) {
                if (!isset($this->nodes[$dependency])) { throw new \LogicException('Missing ownership preparation input: ' . $key . ' -> ' . $dependency); }
                $this->nodes[$dependency]->users[] = $key;
            }
        }
        foreach ($previous as $key => $result) {
            if ($key !== $result->task->key) { throw new \LogicException('Previous ownership result key mismatch'); }
            $this->previous[$key] = $result;
        }
    }
    public function diagnostic(): ?\resolve_types\Annotation_Diagnostic { return $this->failure; }
    public function run(): array /** hash<Ownership_Result> */ {
        if ($this->started) { throw new \LogicException('Ownership queue is one-shot'); } $this->started = true;
        $ready /** vector<string> */ = []; $accepted /** hash<Ownership_Result> */ = [];
        foreach ($this->order as $key) { if ($this->nodes[$key]->remaining === 0) { $ready[] = $key; } }
        while (q_count($ready) !== 0) {
            $batch = $ready; $empty_ready /** vector<string> */ = []; $ready = $empty_ready;
            $tasks /** vector<Ownership_Task> */ = [];
            foreach ($batch as $key) {
                $request = $this->nodes[$key]->request; $inputs /** hash<Ownership_Summary> */ = [];
                foreach ($request->dependencies() as $dependency) { $inputs[$dependency] = $accepted[$dependency]->summary; }
                $reuse = false;
                if (!$this->full) {
                    if (isset($this->previous[$key])) {
                        $old = $this->previous[$key];
                        $reuse = Ownership_Reuse::subject($request,$old->task) && Ownership_Reuse::dependencies($old->task,$inputs);
                    }
                }
                if ($reuse) { $accepted[$key] = $this->previous[$key]; }
                else { $tasks[] = new Ownership_Task($key,$request->body,$request->lifecycle,$inputs); }
            }
            $outputs /** vector<Ownership_Result> */ = [];
            foreach ($tasks as $task) {
                $worker = new Ownership_Worker($task);
                try { $outputs[] = $worker->run(); }
                catch (\RuntimeException $error) { $this->failure = $worker->diagnostic(); throw $error; }
            }
            $joined = (new Ownership_Join($tasks,$this->previous))->join($outputs);
            foreach ($joined as $key => $result) { $accepted[$key] = $result; }
            foreach ($batch as $key) {
                foreach ($this->nodes[$key]->users as $user) {
                    $this->nodes[$user]->remaining = $this->nodes[$user]->remaining-1;
                    if ($this->nodes[$user]->remaining === 0) { $ready[] = $user; }
                }
            }
        }
        if (q_count($accepted) !== q_count($this->nodes)) {
            $names = ''; $separator = '';
            foreach ($this->order as $key) { if (!isset($accepted[$key])) { $names .= $separator . $key; $separator = ', '; } }
            throw new \RuntimeException('Recursive ownership summary dependencies are unsupported: ' . $names);
        }
        return $accepted;
    }
}
