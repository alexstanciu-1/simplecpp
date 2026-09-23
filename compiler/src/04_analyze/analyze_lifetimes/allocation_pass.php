<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Private index of a root binding's resource leaves. */
final class Local_Resource_Leaves {
    public array $locations /** vector<Resource_Location> */ = [];
}
/** Block transfer and validation share this traversal, with separate observation ownership per pass. */
final class Allocation_Pass {
    private array $dependencies /** hash<Ownership_Summary> */ = [];
    private array $locations /** hash<Resource_Location> */ = [];
    private array $locals /** hash<Local_Resource_Leaves,int> */ = [];
    public function __construct(private readonly \check_bodies\Checked_Body $body,
        array $dependencies /** hash<Ownership_Summary> */, array $locations /** hash<Resource_Location> */,
        private readonly Resource_Calls $contracts, private readonly Resource_Bindings $bindings,
        private readonly Allocation_Traversal $traversal, private readonly ?Ownership_Observations $observations) {
        foreach ($dependencies as $key => $summary) { $this->dependencies[$key] = $summary; }
        foreach ($locations as $key => $location) {
            $this->locations[$key] = $location; $local = $location->local;
            if (!isset($this->locals[$local])) { $this->locals[$local] = new Local_Resource_Leaves(); }
            $this->locals[$local]->locations[] = $location;
        }
    }
    public function diagnostic(): ?\resolve_types\Annotation_Diagnostic {
        $failure = $this->contracts->failure();
        if ($failure !== null) { return Resource_Locations::diagnostic($this->body,$failure->node,$failure->reason); }
        $binding = $this->bindings->diagnostic(); if ($binding !== null) { return $binding; }
        return $this->traversal->diagnostic();
    }
    public function retain(array $states /** hash<int> */, int $scope): array /** hash<int> */ {
        $retained /** hash<int> */ = [];
        foreach ($states as $key => $state) {
            $location = $this->locations[$key]; $local = $this->body->names->local_for($location->local);
            if (Local_Flow::contains($this->body,(int)$local->scope_id,$scope)) { $retained[$key] = $state; }
        }
        return $retained;
    }
    /** Only preceding mutations propagate from the private exit; sibling edges keep their original states. */
    public function leave(Resource_Flow_State $flow, int $scope): array /** hash<int> */ {
        $retained = $this->retain($flow->states,$scope); $exiting /** hash<bool,int> */ = [];
        foreach ($flow->states as $key => $state) { if (!isset($retained[$key])) { $local = $this->locations[$key]->local; $exiting[$local] = true; } }
        if (q_count($exiting) === 0) { return $retained; }
        $exit = $flow->copy(); $borrows /** vector<Resource_Location> */ = []; $root /** vector<int> */ = [];
        foreach ($exiting as $local => $present) {
            if ($local < $this->body->entry_parameter_count()+1) { continue; }
            $key = 'destroy:' . $this->body->local_type_for($local); $node = (int)$this->body->names->local_for($local)->declaration_node_id;
            if (isset($this->dependencies[$key])) { $this->contracts->apply_fields(new Resource_Location($local,$root),$this->dependencies[$key]->parameter(0),$exit,$borrows,$node); }
            foreach ($this->locals[$local]->locations as $location) {
                $this->contracts->require_state($location,\analyze_lifetimes\RESOURCE_EMPTY,$exit,$node,'Allocation must be released or transferred before its owner leaves scope');
                $this->contracts->record_access($location,$exit,$node);
            }
        }
        $flow->mutations = $exit->mutations; return $retained;
    }
    public function block(int $id, Resource_Flow_State $entry): Resource_Flow_State {
        $flow = $entry->copy(); $block = $this->body->block_at($id-1);
        if ($this->observations !== null) {
            foreach ($flow->states as $key => $state) {
                $node = (int)$this->body->names->local_for($this->locations[$key]->local)->declaration_node_id;
                $this->contracts->accept('' . $key,Resource_States::deterministic($state),$node,'Allocation ownership differs across control-flow paths');
            }
        }
        for ($index = $block->statement_start; $index < $block->statement_start+$block->statement_count; $index++) { $this->statement($this->body->statement_at($index),$flow); }
        $successors = \check_bodies\Flow_Graph::successors($block);
        if (q_count($successors) === 0) {
            $this->leave($flow,0); $observations = $this->observations;
            if ($observations !== null) {
                foreach ($observations->required as $key => $required) {
                    $previous = 0; if (isset($observations->returns[$key])) { $previous = $observations->returns[$key]; }
                    $observations->returns[$key] = Resource_States::join($previous,$flow->states[$key]);
                }
            }
        } else {
            foreach ($successors as $target) { $this->leave($flow,$this->body->block_at($target-1)->scope_id); }
        }
        return $flow;
    }
    private function statement(\check_bodies\Typed_Statement $statement, Resource_Flow_State $flow): void {
        $flow->states = $this->leave($flow,$statement->scope_id);
        $next = $statement->call_start; $target = $statement->target; $borrows /** hash<Resource_Location,int> */ = [];
        if ($target !== null) {
            for ($i = 0; $i < $target->size(); $i++) {
                $projection = $target->at($i);
                if ($projection->kind !== \check_bodies\PROJECTION_FIELD) {
                    $this->traversal->expression($projection->operand,$next,$projection->call_end,$flow,$borrows); $next = $projection->call_end;
                }
            }
            if ($target->allocation_backed()) {
                $location = Resource_Locations::place($target);
                $this->contracts->require_state($location,\analyze_lifetimes\RESOURCE_OWNED,$flow,$statement->source_node_id,'Element access requires an owned allocation');
                $this->contracts->record_access($location,$flow,$statement->source_node_id); $borrows[-1] = $location;
            }
        }
        $constructed = $this->traversal->expression($statement->value_id,$next,$statement->call_start+$statement->call_count,$flow,$borrows);
        if ($statement->write_kind === \check_bodies\WRITE_COPY_ASSIGN) {
            $source = $this->body->value_for($statement->value_id); $key = 'assign:' . $source->type_id;
            if (isset($this->dependencies[$key])) {
                if ($target === null) { throw new \LogicException('Missing checked assignment target'); }
                $operands /** hash<Resource_Location,int> */ = []; $operands[0] = Resource_Locations::place($target); $operands[1] = $this->bindings->source($source);
                $active /** vector<Resource_Location> */ = []; foreach ($borrows as $borrow) { $active[] = $borrow; }
                $this->contracts->apply_summary($this->dependencies[$key],$operands,$flow,$active,$statement->source_node_id);
            }
        }
        $local = 0;
        if ($statement->kind === \check_bodies\STATEMENT_LOCAL_DECLARATION) {
            if ($target === null) { throw new \LogicException('Missing checked declaration target'); }
            $local = $target->local_id;
        }
        if ($local !== 0) {
            $copy_key = 'copy:' . $this->body->local_type_for($local);
            if ($statement->write_kind === \check_bodies\WRITE_COPY_CONSTRUCT) {
                if (isset($this->dependencies[$copy_key])) { $constructed[$statement->value_id] = $this->traversal->source_construction_fields($statement->value_id,$flow,'copy:'); }
            }
            if (isset($this->locals[$local])) {
                foreach ($this->locals[$local]->locations as $location) {
                    $state = \analyze_lifetimes\RESOURCE_EMPTY_VALUE; $path = Resource_Locations::path_key($location->path());
                    if (isset($constructed[$statement->value_id])) {
                        $fields = $constructed[$statement->value_id]->states; if (isset($fields[$path])) { $state = $fields[$path]; }
                    }
                    $key = $location->key(); $flow->states[$key] = $state;
                }
            }
            $value_id = $statement->value_id; unset($constructed[$value_id]);
        }
        if ($statement->kind === \check_bodies\STATEMENT_RETURN) {
            if ($statement->value_id !== 0) {
                $source = $this->body->value_for($statement->value_id);
                $resource = $this->body->definition_for($source->type_id)->ownership; $has_fields = false;
                if ($resource !== null) { $has_fields = $resource->path_count() !== 0; }
                if ($has_fields) {
                    $empty /** hash<int> */ = []; $fields = new Resource_Field_State($empty);
                    if ($statement->return_mode === \check_bodies\RETURN_DIRECT_CONSTRUCT) {
                        if (!isset($constructed[$statement->value_id])) { throw new \LogicException('Owned result requires prepared resource facts'); }
                        $fields = $constructed[$statement->value_id];
                    } else if ($statement->return_mode === \check_bodies\RETURN_COPY_CONSTRUCT) { $fields = $this->traversal->source_construction_fields($statement->value_id,$flow,'copy:'); }
                    else if ($statement->return_mode === \check_bodies\RETURN_MOVE_CONSTRUCT) { $fields = $this->traversal->source_construction_fields($statement->value_id,$flow,'move:'); }
                    else { throw new \LogicException('Resource-owning result requires construction'); }
                    $observations = $this->observations;
                    if ($observations !== null) {
                        foreach ($fields->states as $path => $state) {
                            $previous = 0; if (isset($observations->result[$path])) { $previous = $observations->result[$path]; }
                            $observations->result[$path] = Resource_States::join($previous,$state);
                        }
                    }
                    if ($statement->return_mode === \check_bodies\RETURN_DIRECT_CONSTRUCT) { $value_id = $statement->value_id; unset($constructed[$value_id]); }
                }
            }
        }
        $temporary_ids /** vector<int> */ = []; foreach ($constructed as $id => $fields) { $temporary_ids[] = $id; }
        for ($index = q_count($temporary_ids); $index > 0; $index = $index-1) {
            $id = $temporary_ids[$index-1]; $temporary = $this->body->value_for($id);
            $this->traversal->finish_fields($temporary->type_id,$constructed[$id],$temporary->source_node_id);
        }
    }
}
