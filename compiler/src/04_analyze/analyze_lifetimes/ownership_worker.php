<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Private mutable construction rows; published summaries copy their membership. */
final class Lifecycle_Parameter_Fields {
    public array $fields /** hash<Resource_Transition> */ = [];
}
final class Lifecycle_Ownership_State {
    public array $parameters /** hash<Lifecycle_Parameter_Fields,int> */ = [];
    public array $distinct /** hash<Distinct_Endpoints> */ = [];
}
final class Ownership_Worker {
    private bool $started = false;
    private ?\resolve_types\Annotation_Diagnostic $failure = null;
    public function __construct(private readonly Ownership_Task $task) {}
    public function diagnostic(): ?\resolve_types\Annotation_Diagnostic { return $this->failure; }
    public function run(): Ownership_Result {
        if ($this->started) { throw new \LogicException('Ownership worker is one-shot'); } $this->started = true;
        $body = $this->task->body();
        if ($body !== null) {
            $flow = new Allocation_Flow($body,$this->task->dependencies());
            try { $allocations = $flow->analyze(); return new Ownership_Result($this->task,$flow->summary(),$allocations); }
            catch (\RuntimeException $error) { $this->failure = $flow->diagnostic(); throw $error; }
        }
        $subject = $this->task->lifecycle(); if ($subject === null) { throw new \LogicException('Missing ownership lifecycle subject'); }
        return new Ownership_Result($this->task,$this->lifecycle($subject),null);
    }
    private function lifecycle(Ownership_Lifecycle $subject): Ownership_Summary {
        $construct = \type_model\Lifecycle_Roles::creates_destination($subject->kind); $has_source = \type_model\Lifecycle_Roles::has_source($subject->kind);
        $state = new Lifecycle_Ownership_State(); $state->parameters[0] = new Lifecycle_Parameter_Fields();
        $resource = $subject->definition->ownership;
        if ($resource !== null) {
            for ($i = 0; $i < $resource->path_count(); $i++) {
                $path = Resource_Locations::path_key($resource->path_at($i));
                $required = \analyze_lifetimes\RESOURCE_EITHER; $result = \analyze_lifetimes\RESOURCE_IDENTITY;
                if ($construct) { $required = \analyze_lifetimes\RESOURCE_EMPTY; $result = \analyze_lifetimes\RESOURCE_EMPTY_VALUE; }
                $state->parameters[0]->fields[$path] = new Resource_Transition($required,$result,false,false);
                if ($has_source) {
                    if (!isset($state->parameters[1])) { $state->parameters[1] = new Lifecycle_Parameter_Fields(); }
                    $state->parameters[1]->fields[$path] = new Resource_Transition(\analyze_lifetimes\RESOURCE_EITHER,\analyze_lifetimes\RESOURCE_IDENTITY,false,false);
                }
            }
        }
        $order = $subject->order(); $body_id = 0; $has_body = take_nullable($body_id,$subject->body_id);
        if ($has_body && $order->body_before_members) { $this->consume($state,$this->task->dependency('body:' . $body_id),'',$subject); }
        foreach ($subject->children() as $child) { $this->consume($state,$this->task->dependency($child->dependency),'' . $child->ordinal,$subject); }
        if ($has_body && !$order->body_before_members) { $this->consume($state,$this->task->dependency('body:' . $body_id),'',$subject); }
        foreach ($state->parameters[0]->fields as $path => $transition) {
            $allowed = Resource_States::deterministic($transition->result);
            if ($subject->kind === \type_model\LIFECYCLE_DESTROY) { $allowed = Resource_States::compatible($transition->result,\analyze_lifetimes\RESOURCE_EMPTY); }
            $allowed = Resource_States::intersect($transition->required,$allowed);
            if ($allowed === 0) { throw new \RuntimeException('Complete lifecycle cannot discharge allocation field ' . $subject->definition->name . ':' . $path); }
            $state->parameters[0]->fields[$path] = new Resource_Transition($allowed,$transition->result,$transition->mutates,$transition->accessed || ($allowed !== \analyze_lifetimes\RESOURCE_EITHER));
        }
        $parameters /** vector<Parameter_Effects> */ = []; $pairs /** vector<Distinct_Endpoints> */ = []; $result /** hash<int> */ = [];
        foreach ($state->parameters as $position => $fields) { $parameters[] = new Parameter_Effects($position,$fields->fields); }
        foreach ($state->distinct as $pair) { $pairs[] = $pair; }
        return new Ownership_Summary($parameters,$pairs,$result);
    }
    private function consume(Lifecycle_Ownership_State $state, Ownership_Summary $summary, string $prefix, Ownership_Lifecycle $subject): void {
        foreach ($summary->distinct() as $pair) {
            $left = Resource_Locations::parameter_parts($pair->left); $right = Resource_Locations::parameter_parts($pair->right);
            $mapped = Resource_Locations::distinct_pair(Resource_Locations::parameter_key($left->position,Resource_Locations::prefix($prefix,$left->path)),Resource_Locations::parameter_key($right->position,Resource_Locations::prefix($prefix,$right->path)));
            $key = Resource_Locations::distinct_key($mapped); $state->distinct[$key] = $mapped;
        }
        foreach ($summary->parameters() as $position => $fields) {
            if (!isset($state->parameters[$position])) { throw new \LogicException('Lifecycle resource path mismatch'); }
            foreach ($fields->fields() as $path => $next) {
                $key = Resource_Locations::prefix($prefix,'' . $path);
                if (!isset($state->parameters[$position]->fields[$key])) { throw new \LogicException('Lifecycle resource path mismatch'); }
                $previous = $state->parameters[$position]->fields[$key];
                $required = Resource_States::intersect($previous->required,Resource_States::compatible($previous->result,$next->required));
                if ($required === 0) { throw new \RuntimeException('Unsatisfied complete lifecycle ownership requirement: ' . $subject->definition->name . ':' . $key); }
                $state->parameters[$position]->fields[$key] = new Resource_Transition($required,Resource_States::compose($previous->result,$next->result),$previous->mutates || $next->mutates,$previous->accessed || $next->accessed);
            }
        }
    }
}
