<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Source attribution is attached by the body worker; this owner retains the exact failing node. */
final class Ownership_Failure {
    public function __construct(public readonly int $node, public readonly string $reason) {}
}
/** Apply already bound resource contracts. No checked-expression traversal or global lookup occurs here. */
final class Resource_Calls {
    private array $locations /** hash<Resource_Location> */ = [];
    private array $parameters /** hash<bool> */ = [];
    private ?Ownership_Failure $failure_row = null;
    public function __construct(array $locations /** hash<Resource_Location> */,
        array $parameters /** hash<bool> */, private readonly ?Ownership_Observations $observations) {
        foreach ($locations as $key => $location) { $this->locations[$key] = $location; }
        foreach ($parameters as $key => $present) {
            if (!isset($this->locations[$key])) { throw new \LogicException('Unknown resource parameter location'); }
            $this->parameters[$key] = $present;
            if ($observations !== null) {
                if (!isset($observations->required[$key])) { throw new \LogicException('Missing initial ownership requirement'); }
            }
        }
    }
    public function failure(): ?Ownership_Failure { return $this->failure_row; }
    private function fail(int $node, string $reason): void {
        $this->failure_row = new Ownership_Failure($node,$reason); throw new \RuntimeException($reason);
    }
    /** Every operand is checked against the same pre-call state; only then commit one effect per leaf. */
    public function apply_summary(Ownership_Summary $summary, array $operands /** hash<Resource_Location,int> */,
        Resource_Flow_State $flow, array $borrows /** vector<Resource_Location> */, int $node): void {
        $mapped /** hash<Bound_Resource_Effect> */ = [];
        foreach ($summary->parameters() as $position => $parameter) {
            if (!isset($operands[$position])) { throw new \LogicException('Missing bound ownership operand'); }
            foreach ($parameter->fields() as $path => $transition) {
                $relative = '' . $path; $endpoint = Resource_Locations::parameter_key($position,$relative);
                $mapped[$endpoint] = new Bound_Resource_Effect(Resource_Locations::project($operands[$position],$relative),$transition);
            }
        }
        $effects /** hash<Bound_Resource_Effect> */ = [];
        foreach ($mapped as $effect) {
            $location = $effect->location; $transition = $effect->transition;
            $this->require_state($location,$transition->required,$flow,$node,'Source call ownership requirements are not satisfied');
            if ($transition->accessed) { $this->record_access($location,$flow,$node); }
            $key = $location->key();
            if (isset($effects[$key])) {
                $previous = $effects[$key]->transition;
                if ($this->observations !== null) {
                    if ($previous->mutates && $transition->mutates) { $this->fail($node,'Aliased arguments require a single resource writer'); }
                }
            }
            if (!isset($effects[$key]) || $transition->mutates) { $effects[$key] = $effect; }
        }
        foreach ($summary->distinct() as $pair) { $this->exclude($mapped[$pair->left]->location,$mapped[$pair->right]->location,$node); }
        foreach ($effects as $effect) { $this->check_mutation($effect->location,$effect->transition,$borrows,$node); }
        foreach ($effects as $effect) { $this->apply_transition($effect->location,$effect->transition,$flow); }
    }
    /** Independent leaves of one object retain their explicit field order. */
    public function apply_fields(Resource_Location $base, Parameter_Effects $fields, Resource_Flow_State $flow,
        array $borrows /** vector<Resource_Location> */, int $node): void {
        foreach ($fields->fields() as $path => $transition) {
            $location = Resource_Locations::project($base,'' . $path);
            $this->require_state($location,$transition->required,$flow,$node,'Source call ownership requirements are not satisfied');
            if ($transition->accessed) { $this->record_access($location,$flow,$node); }
            $this->check_mutation($location,$transition,$borrows,$node); $this->apply_transition($location,$transition,$flow);
        }
    }
    public function require_state(Resource_Location $location, int $required, Resource_Flow_State $flow, int $node, string $reason): void {
        if ($this->observations !== null) {
            $key = $location->key(); $state = 0; if (isset($flow->states[$key])) { $state = $flow->states[$key]; }
            $this->accept($key,Resource_States::compatible($state,$required),$node,$reason);
        }
    }
    public function accept(string $key, int $compatible, int $node, string $reason): void {
        $observations = $this->observations;
        if ($observations === null) { return; }
        if (isset($this->parameters[$key])) {
            $observations->required[$key] = Resource_States::intersect($observations->required[$key],$compatible);
            $compatible = 0; if ($observations->required[$key] !== 0) { $compatible = \analyze_lifetimes\RESOURCE_EITHER; }
        }
        if ($compatible !== \analyze_lifetimes\RESOURCE_EITHER) { $this->fail($node,$reason); }
    }
    public function record_access(Resource_Location $location, Resource_Flow_State $flow, int $node): void {
        $observations = $this->observations; if ($observations === null) { return; }
        $key = $location->key(); if (!isset($this->parameters[$key])) { return; }
        foreach ($flow->mutations as $previous => $mutated) {
            $other = $this->locations[$previous];
            if ($other->local !== $location->local) { $this->exclude($other,$location,$node); }
        }
        $observations->accessed[$key] = true;
    }
    public function exclude(Resource_Location $left, Resource_Location $right, int $node): void {
        $observations = $this->observations; if ($observations === null) { return; }
        if (Resource_Locations::overlaps($left,$right)) { $this->fail($node,'Aliased arguments violate resource access order or allocation stability'); }
        $left_key = $left->key(); $right_key = $right->key();
        if (($left->local !== $right->local) && isset($this->parameters[$left_key]) && isset($this->parameters[$right_key])) {
            $pair = Resource_Locations::distinct_pair(Resource_Locations::parameter_key($left->local-1,Resource_Locations::path_key($left->path())),Resource_Locations::parameter_key($right->local-1,Resource_Locations::path_key($right->path())));
            $key = Resource_Locations::distinct_key($pair); $observations->distinct[$key] = $pair;
        }
    }
    public function check_mutation(Resource_Location $location, Resource_Transition $transition,
        array $borrows /** vector<Resource_Location> */, int $node): void {
        $observations = $this->observations; if ($observations === null) { return; }
        if (!$transition->mutates) { return; }
        foreach ($borrows as $borrow) {
            if (Resource_Locations::overlaps($location,$borrow)) { $this->fail($node,'Allocation mutation would invalidate an active call borrow'); }
            $this->exclude($location,$borrow,$node);
        }
        $observations->mutated[$location->key()] = true;
    }
    public function apply_transition(Resource_Location $location, Resource_Transition $transition, Resource_Flow_State $flow): void {
        $key = $location->key(); $state = \analyze_lifetimes\RESOURCE_EMPTY_VALUE;
        if (isset($flow->states[$key])) { $state = $flow->states[$key]; }
        $flow->states[$key] = Resource_States::compose($state,$transition->result);
        if ($transition->accessed && $transition->mutates && isset($this->parameters[$key])) { $flow->mutations[$key] = true; }
    }
}
