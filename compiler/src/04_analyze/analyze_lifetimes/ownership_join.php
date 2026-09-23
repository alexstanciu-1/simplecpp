<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Compare published semantic membership explicitly; PHP object equality and insertion order are not the contract. */
final class Ownership_Contracts {
    public static function same(Ownership_Summary $left, Ownership_Summary $right): bool {
        $a = $left->parameters(); $b = $right->parameters();
        if (q_count($a) !== q_count($b)) { return false; }
        foreach ($a as $position => $parameter) {
            if (!isset($b[$position])) { return false; }
            $fields = $parameter->fields(); $other = $b[$position]->fields();
            if (q_count($fields) !== q_count($other)) { return false; }
            foreach ($fields as $path => $transition) {
                if (!isset($other[$path])) { return false; } $next = $other[$path];
                if (($transition->required !== $next->required) || ($transition->result !== $next->result) || ($transition->mutates !== $next->mutates) || ($transition->accessed !== $next->accessed)) { return false; }
            }
        }
        $pairs = $left->distinct(); $other_pairs = $right->distinct();
        if (q_count($pairs) !== q_count($other_pairs)) { return false; }
        foreach ($pairs as $key => $pair) { if (!isset($other_pairs[$key])) { return false; } }
        $result = $left->result(); $other_result = $right->result();
        if (q_count($result) !== q_count($other_result)) { return false; }
        foreach ($result as $path => $state) {
            if (!isset($other_result[$path])) { return false; }
            if ($other_result[$path] !== $state) { return false; }
        }
        return true;
    }
}
/** Fixed batch acceptance. No previous result or dependency owner is writable while validating outputs. */
final class Ownership_Join {
    private array $selected /** hash<Ownership_Task> */ = [];
    private array $previous /** hash<Ownership_Result> */ = [];
    public function __construct(array $tasks /** vector<Ownership_Task> */, array $previous /** hash<Ownership_Result> */) {
        foreach ($tasks as $task) {
            $key = $task->key; if (isset($this->selected[$key])) { throw new \LogicException('Duplicate ownership task'); }
            $this->selected[$key] = $task;
        }
        foreach ($previous as $key => $result) { $this->previous[$key] = $result; }
    }
    public function join(array $results /** vector<Ownership_Result> */): array /** hash<Ownership_Result> */ {
        $accepted /** hash<Ownership_Result> */ = [];
        foreach ($results as $result) {
            $key = $result->task->key;
            if (!isset($this->selected[$key])) { throw new \LogicException('Unexpected, duplicate or stale ownership result'); }
            if (($this->selected[$key] !== $result->task) || isset($accepted[$key])) { throw new \LogicException('Unexpected, duplicate or stale ownership result'); }
            $body = $result->task->body(); $allocations = $result->allocations;
            if ($allocations !== null) { if ($allocations->body !== $body) { throw new \LogicException('Ownership allocation provenance mismatch'); } }
            $this->validate_summary($result->task,$result->summary);
            $summary = $result->summary;
            if (isset($this->previous[$key])) {
                $old = $this->previous[$key]; if (Ownership_Contracts::same($old->summary,$summary)) { $summary = $old->summary; }
            }
            $accepted[$key] = $result;
            if ($summary !== $result->summary) { $accepted[$key] = new Ownership_Result($result->task,$summary,$result->allocations); }
        }
        if (q_count($accepted) !== q_count($this->selected)) { throw new \LogicException('Incomplete ownership batch'); }
        return $accepted;
    }
    private static function paths(\type_model\Named_Definition $definition): array /** vector<vector<int>> */ {
        $out /** vector<vector<int>> */ = []; $resource = $definition->ownership;
        if ($resource !== null) { for ($i = 0; $i < $resource->path_count(); $i++) { $out[] = $resource->path_at($i); } }
        return $out;
    }
    private function validate_summary(Ownership_Task $task, Ownership_Summary $summary): void {
        $body = $task->body(); $subject = $task->lifecycle(); $moving = false;
        $expected /** vector<Parameter_Resources> */ = []; $result_paths /** vector<vector<int>> */ = [];
        if ($body !== null) {
            $expected = Resource_Locations::parameters($body);
            $result_type = $body->signature_for($body->callable_id)->representation->signature_return();
            $result_paths = Ownership_Join::paths($body->definition_for($result_type));
        } else {
            if ($subject === null) { throw new \LogicException('Missing ownership subject'); }
            $paths = Ownership_Join::paths($subject->definition); $expected[] = new Parameter_Resources(0,$paths);
            if (\type_model\Lifecycle_Roles::has_source($subject->kind)) { $expected[] = new Parameter_Resources(1,$paths); }
            $moving = $subject->kind === \type_model\LIFECYCLE_MOVE;
        }
        $parameters = $summary->parameters();
        if (q_count($parameters) !== q_count($expected)) { throw new \LogicException('Incomplete ownership summary parameters'); }
        foreach ($expected as $parameter) {
            $position = $parameter->position;
            if (!isset($parameters[$position])) { throw new \LogicException('Incomplete ownership summary parameters'); }
            $fields = $parameters[$position]->fields();
            if (q_count($fields) !== $parameter->size()) { throw new \LogicException('Incomplete ownership summary fields'); }
            for ($i = 0; $i < $parameter->size(); $i++) {
                $path = Resource_Locations::path_key($parameter->at($i));
                if (!isset($fields[$path])) { throw new \LogicException('Incomplete ownership summary fields'); }
            }
            $is_const = ($position === 1) && !$moving;
            if ($body !== null) { $is_const = $body->local_passing($position+1) === \type_model\PASS_BORROW_CONST; }
            foreach ($fields as $transition) {
                if (!$transition->accessed) {
                    if ($transition->mutates || ($transition->required !== \analyze_lifetimes\RESOURCE_EITHER) || ($transition->result !== \analyze_lifetimes\RESOURCE_IDENTITY)) { throw new \LogicException('Invalid ownership field transition'); }
                }
                if ($is_const) {
                    if ($transition->mutates || ($transition->result !== \analyze_lifetimes\RESOURCE_IDENTITY)) { throw new \LogicException('Invalid ownership field transition'); }
                }
            }
        }
        // Summary construction already requires canonical, present, unequal alias endpoints and fixed result states.
        $result_fields = $summary->result();
        if (q_count($result_fields) !== q_count($result_paths)) { throw new \LogicException('Incomplete owned result resource summary'); }
        foreach ($result_paths as $path) {
            $key = Resource_Locations::path_key($path);
            if (!isset($result_fields[$key])) { throw new \LogicException('Incomplete owned result resource summary'); }
        }
    }
}
