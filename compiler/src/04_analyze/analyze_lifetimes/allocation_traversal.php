<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Private constructed-value field facts; a named owner replaces value -> path -> state arrays. */
final class Resource_Field_State {
    public array $states /** hash<int> */ = [];
    public function __construct(array $states /** hash<int> */) {
        foreach ($states as $path => $state) { $this->states[$path] = $state; }
    }
}
/** One pass over checked evaluation order. Solver instances have no observations. */
final class Allocation_Traversal {
    private array $dependencies /** hash<Ownership_Summary> */ = [];
    private ?Ownership_Failure $failure_row = null;
    public function __construct(private readonly \check_bodies\Checked_Body $body,
        array $dependencies /** hash<Ownership_Summary> */, private readonly Resource_Calls $contracts,
        private readonly Allocation_Calls $runtime, private readonly Resource_Bindings $bindings,
        private readonly ?Ownership_Observations $observations) {
        foreach ($dependencies as $key => $summary) { $this->dependencies[$key] = $summary; }
    }
    public function failure(): ?Ownership_Failure {
        if ($this->failure_row !== null) { return $this->failure_row; }
        $binding = $this->bindings->failure(); if ($binding !== null) { return $binding; }
        return $this->runtime->failure();
    }
    public function diagnostic(): ?\resolve_types\Annotation_Diagnostic {
        $failure = $this->failure(); if ($failure === null) { return null; }
        return Resource_Locations::diagnostic($this->body,$failure->node,$failure->reason);
    }
    private function fail(int $node, string $reason): void {
        $this->failure_row = new Ownership_Failure($node,$reason); throw new \RuntimeException($reason);
    }
    private static function borrow_values(array $borrows /** hash<Resource_Location,int> */): array /** vector<Resource_Location> */ {
        $out /** vector<Resource_Location> */ = []; foreach ($borrows as $borrow) { $out[] = $borrow; } return $out;
    }
    /** Return constructed temporaries; the statement consumer owns their transfer or destruction. */
    public function expression(int $root, int $start, int $limit, Resource_Flow_State $flow,
        array $initial_borrows /** hash<Resource_Location,int> */): array /** hash<Resource_Field_State,int> */ {
        $constructed /** hash<Resource_Field_State,int> */ = [];
        $borrows /** hash<Resource_Location,int> */ = []; foreach ($initial_borrows as $id => $location) { $borrows[$id] = $location; }
        $order = \check_bodies\Expression_Order::steps($this->body,$root,$start,$limit); $step = $order->next();
        while ($step !== null) {
            if ($step->call_id !== 0) {
                $call = $this->body->call_for($step->call_id);
                $arguments /** hash<bool,int> */ = [];
                for ($i = 1; $i < $call->argument_count+1; $i++) { $id = $this->body->argument_for($step->call_id,$i)->value_id; $arguments[$id] = true; }
                $pending /** hash<Resource_Location,int> */ = [];
                foreach ($borrows as $id => $location) { if (!isset($arguments[$id])) { $pending[$id] = $location; } }
                $effect = $this->body->allocation_effect_for($call->target_callable_id);
                if ($effect !== null) {
                    $operands = $this->bindings->allocation_operands($step->call_id,$effect);
                    $owner = $operands[$effect->owner]; $node = (int)$this->body->names->local_for($owner->local)->declaration_node_id;
                    $this->runtime->apply($effect,$operands,$flow,Allocation_Traversal::borrow_values($pending),$node);
                }
                $key = 'body:' . $call->target_callable_id;
                if (isset($this->dependencies[$key])) {
                    $summary = $this->dependencies[$key]; $result = $summary->result();
                    if (q_count($result) !== 0) { $constructed[$call->result_value_id] = new Resource_Field_State($result); }
                    $operands = $this->bindings->summary_operands($step->call_id,$summary);
                    $this->contracts->apply_summary($summary,$operands,$flow,Allocation_Traversal::borrow_values($borrows),$call->source_node_id);
                }
                $borrows = $pending;
            }
            if ($step->value_id !== 0) {
                $value = $this->body->value_for($step->value_id);
                if (($value->kind === \check_bodies\VALUE_LOCAL_READ) || ($value->kind === \check_bodies\VALUE_LOCAL_BORROW)) {
                    $place = $value->place(); $location = Resource_Locations::place($place);
                    if ($place->allocation_backed()) {
                        $this->contracts->require_state($location,\analyze_lifetimes\RESOURCE_OWNED,$flow,$value->source_node_id,'Element access requires an owned allocation');
                        $this->contracts->record_access($location,$flow,$value->source_node_id);
                        if ($value->kind === \check_bodies\VALUE_LOCAL_BORROW) { $borrows[$step->value_id] = $location; }
                    }
                }
                if (($value->kind === \check_bodies\VALUE_DEFAULT_CONSTRUCT) || ($value->kind === \check_bodies\VALUE_RECORD_DEFAULT)) {
                    $key = 'construct:' . $value->type_id;
                    if (isset($this->dependencies[$key])) { $constructed[$step->value_id] = $this->construction_fields($this->dependencies[$key],$value->source_node_id); }
                }
            }
            $step = $order->next();
        }
        return $constructed;
    }
    public function construction_fields(Ownership_Summary $summary, int $node): Resource_Field_State {
        $fields /** hash<int> */ = [];
        foreach ($summary->parameter(0)->fields() as $path => $transition) {
            if ($this->observations !== null) {
                if (Resource_States::intersect($transition->required,\analyze_lifetimes\RESOURCE_EMPTY) === 0) { $this->fail($node,'Complete construction requires empty field storage'); }
            }
            $fields[$path] = Resource_States::compose(\analyze_lifetimes\RESOURCE_EMPTY_VALUE,$transition->result);
        }
        return new Resource_Field_State($fields);
    }
    public function source_construction_fields(int $value_id, Resource_Flow_State $flow, string $prefix): Resource_Field_State {
        $value = $this->body->value_for($value_id); $key = $prefix . $value->type_id;
        if (!isset($this->dependencies[$key])) { throw new \LogicException('Missing source construction summary'); }
        $summary = $this->dependencies[$key]; $source = $this->bindings->source($value); $borrows /** vector<Resource_Location> */ = [];
        $this->contracts->apply_fields($source,$summary->parameter(1),$flow,$borrows,$value->source_node_id);
        return $this->construction_fields($summary,$value->source_node_id);
    }
    public function finish_fields(int $type, Resource_Field_State $fields, int $node): void {
        $key = 'destroy:' . $type;
        if (!isset($this->dependencies[$key])) { throw new \LogicException('Missing temporary destruction summary'); }
        $parameter = $this->dependencies[$key]->parameter(0);
        foreach ($fields->states as $path => $state) {
            $transition = $parameter->at('' . $path);
            if ($this->observations !== null) {
                if (Resource_States::compatible($state,$transition->required) !== \analyze_lifetimes\RESOURCE_EITHER) { $this->fail($node,'Temporary destruction does not satisfy its ownership contract'); }
            }
        }
    }
}
