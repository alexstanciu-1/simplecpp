<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Converge resource relations first, validate stable entries second, then infer a fixed caller contract. */
final class Allocation_Flow {
    private array $dependencies /** hash<Ownership_Summary> */ = [];
    private array $locations /** hash<Resource_Location> */ = [];
    private array $parameters /** hash<bool> */ = [];
    private ?Ownership_Observations $observations = null;
    private ?\resolve_types\Annotation_Diagnostic $failure = null;
    private bool $started = false;
    private bool $completed = false;
    public function __construct(private readonly \check_bodies\Checked_Body $body, array $dependencies /** hash<Ownership_Summary> */) {
        foreach ($dependencies as $key => $summary) { $this->dependencies[$key] = $summary; }
    }
    public function diagnostic(): ?\resolve_types\Annotation_Diagnostic { return $this->failure; }
    private function fail(int $node, string $reason): void {
        $this->failure = Resource_Locations::diagnostic($this->body,$node,$reason); throw new \RuntimeException($reason);
    }
    private function initial_state(): Resource_Flow_State {
        $this->locations = Resource_Locations::locals($this->body); $initial = new Resource_Flow_State();
        foreach ($this->locations as $key => $location) {
            if ($location->local < $this->body->entry_parameter_count()+1) {
                if ((!\type_model\Semantic_Modes::is_borrow($this->body->local_passing($location->local))) || ($location->size() === 0)) {
                    $this->fail((int)$this->body->names->local_for($location->local)->declaration_node_id,'Allocation owner parameters require an ownership contract');
                }
                $initial->states[$key] = \analyze_lifetimes\RESOURCE_IDENTITY; $this->parameters[$key] = true;
            }
        }
        return $initial;
    }
    public function analyze(): ?Allocation_Analysis {
        if ($this->started) { throw new \LogicException('Allocation flow is one-shot'); } $this->started = true;
        $initial = $this->initial_state();
        if ((q_count($this->locations) === 0) && (q_count($this->dependencies) === 0)) { $this->completed = true; return null; }
        $entries = $this->solve($initial); $required /** hash<int> */ = [];
        foreach ($this->parameters as $key => $present) { $required[$key] = \analyze_lifetimes\RESOURCE_EITHER; }
        $observations = new Ownership_Observations($required);
        $contracts = new Resource_Calls($this->locations,$this->parameters,$observations); $bindings = new Resource_Bindings($this->body);
        $runtime = new Allocation_Calls($contracts);
        $traversal = new Allocation_Traversal($this->body,$this->dependencies,$contracts,$runtime,$bindings,$observations);
        $pass = new Allocation_Pass($this->body,$this->dependencies,$this->locations,$contracts,$bindings,$traversal,$observations);
        $published /** hash<Allocation_Entry,int> */ = [];
        try {
            for ($id = 1; $id < $this->body->block_count()+1; $id++) {
                if (isset($entries[$id])) { $pass->block($id,$entries[$id]); $published[$id] = new Allocation_Entry($entries[$id]->states); }
            }
        } catch (\RuntimeException $error) { $this->failure = $pass->diagnostic(); throw $error; }
        $this->observations = $observations; $this->completed = true;
        return new Allocation_Analysis($this->body,$published);
    }
    private static function same(Resource_Flow_State $left, Resource_Flow_State $right): bool {
        if ((q_count($left->states) !== q_count($right->states)) || (q_count($left->mutations) !== q_count($right->mutations))) { return false; }
        foreach ($left->states as $key => $state) {
            if (!isset($right->states[$key])) { return false; }
            if ($right->states[$key] !== $state) { return false; }
        }
        foreach ($left->mutations as $key => $present) { if (!isset($right->mutations[$key])) { return false; } }
        return true;
    }
    private function solve(Resource_Flow_State $initial): array /** hash<Resource_Flow_State,int> */ {
        $contracts = new Resource_Calls($this->locations,$this->parameters,null); $bindings = new Resource_Bindings($this->body);
        $runtime = new Allocation_Calls($contracts);
        $traversal = new Allocation_Traversal($this->body,$this->dependencies,$contracts,$runtime,$bindings,null);
        $pass = new Allocation_Pass($this->body,$this->dependencies,$this->locations,$contracts,$bindings,$traversal,null);
        $entries /** hash<Resource_Flow_State,int> */ = []; $entries[1] = $initial;
        $pending /** vector<int> */ = [1]; $depth = 1; $queued /** hash<bool,int> */ = []; $queued[1] = true;
        try {
            while ($depth > 0) {
                $depth = $depth-1; $id = $pending[$depth]; unset($queued[$id]);
                $exit = $pass->block($id,$entries[$id]);
                foreach (\check_bodies\Flow_Graph::successors($this->body->block_at($id-1)) as $target) {
                    $next = $pass->retain($exit->states,$this->body->block_at($target-1)->scope_id);
                    $merged = new Resource_Flow_State(); if (isset($entries[$target])) { $merged = $entries[$target]->copy(); }
                    foreach ($next as $key => $state) {
                        $previous = 0; if (isset($merged->states[$key])) { $previous = $merged->states[$key]; }
                        $merged->states[$key] = Resource_States::join($previous,$state);
                    }
                    foreach ($exit->mutations as $key => $present) { $merged->mutations[$key] = true; }
                    $changed = true; if (isset($entries[$target])) { $changed = !Allocation_Flow::same($merged,$entries[$target]); }
                    if ($changed) {
                        $entries[$target] = $merged;
                        if (!isset($queued[$target])) {
                            $queued[$target] = true;
                            if ($depth === q_count($pending)) { $pending[] = $target; } else { $pending[$depth] = $target; }
                            $depth++;
                        }
                    }
                }
            }
        } catch (\RuntimeException $error) { $this->failure = $pass->diagnostic(); throw $error; }
        return $entries;
    }
    public function summary(): Ownership_Summary {
        if (!$this->completed) { throw new \LogicException('Ownership summary requires completed allocation analysis'); }
        $rows /** vector<Parameter_Effects> */ = []; $pairs /** vector<Distinct_Endpoints> */ = []; $result /** hash<int> */ = [];
        $observations = $this->observations;
        if ($observations === null) { return new Ownership_Summary($rows,$pairs,$result); }
        for ($position = 0; $position < $this->body->entry_parameter_count(); $position++) {
            $fields /** hash<Resource_Transition> */ = [];
            foreach (Resource_Locations::paths($this->body->definition_for($this->body->local_type_for($position+1))) as $path) {
                $location = new Resource_Location($position+1,$path); $key = $location->key();
                $state = \analyze_lifetimes\RESOURCE_IDENTITY; if (isset($observations->returns[$key])) { $state = $observations->returns[$key]; }
                $required = Resource_States::intersect($observations->required[$key],Resource_States::deterministic($state));
                $node = (int)$this->body->names->local_for($location->local)->declaration_node_id;
                if ($required === 0) { $this->fail($node,'No consistent ownership contract across normal exits'); }
                $mutates = isset($observations->mutated[$key]);
                if ($this->body->local_passing($location->local) === \type_model\PASS_BORROW_CONST) {
                    if ($mutates || ($state !== \analyze_lifetimes\RESOURCE_IDENTITY)) { $this->fail($node,'Const borrowed resource state must remain unchanged'); }
                }
                $path_key = Resource_Locations::path_key($path); $fields[$path_key] = new Resource_Transition($required,$state,$mutates,isset($observations->accessed[$key]));
            }
            if (q_count($fields) !== 0) { $rows[] = new Parameter_Effects($position,$fields); }
        }
        $contracts = new Resource_Calls($this->locations,$this->parameters,$observations);
        $writers /** vector<Resource_Location> */ = [];
        foreach ($observations->mutated as $key => $present) { if (isset($this->parameters[$key])) { $writers[] = $this->locations[$key]; } }
        for ($left = 0; $left < q_count($writers); $left++) {
            for ($right = $left+1; $right < q_count($writers); $right++) {
                if ($writers[$left]->local !== $writers[$right]->local) {
                    $node = (int)$this->body->names->local_for($writers[$left]->local)->declaration_node_id;
                    $contracts->exclude($writers[$left],$writers[$right],$node);
                }
            }
        }
        foreach ($observations->distinct as $pair) { $pairs[] = $pair; }
        foreach ($observations->result as $path => $state) {
            if (($state !== \analyze_lifetimes\RESOURCE_EMPTY_VALUE) && ($state !== \analyze_lifetimes\RESOURCE_OWNED_VALUE)) {
                $this->fail((int)$this->body->input->owner->source_fact()->body_node_id,'Owned result requires a consistent field state across returns');
            }
            $result[$path] = $state;
        }
        return new Ownership_Summary($rows,$pairs,$result);
    }
}
