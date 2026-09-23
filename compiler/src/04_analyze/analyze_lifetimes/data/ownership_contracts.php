<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Access and mutation remain separate from the relation: mutation may preserve state. */
final class Resource_Transition {
    public function __construct(public readonly int $required, public readonly int $result,
        public readonly bool $mutates, public readonly bool $accessed) {
        if (($required < 1) || ($required > 3) || ($result < 1) || ($result > 15)) {
            throw new \InvalidArgumentException('Invalid resource transition');
        }
    }
}
final class Bound_Resource_Effect {
    public function __construct(public readonly Resource_Location $location, public readonly Resource_Transition $transition) {}
}
/** A zero-based parameter and its immutable relative field contracts. */
final class Parameter_Effects {
    private array $field_rows /** hash<Resource_Transition> */ = [];
    public function __construct(public readonly int $position, array $fields /** hash<Resource_Transition> */) {
        if ($position < 0) { throw new \InvalidArgumentException('Invalid ownership parameter'); }
        foreach ($fields as $path => $transition) {
            $key = '' . $path;
            Resource_Locations::parameter_parts(Resource_Locations::parameter_key($position,$key));
            $this->field_rows[$key] = $transition;
        }
    }
    public function fields(): array /** hash<Resource_Transition> */ { return $this->field_rows; }
    public function has(string $path): bool { return isset($this->field_rows[$path]); }
    public function at(string $path): Resource_Transition {
        if (!isset($this->field_rows[$path])) { throw new \OutOfBoundsException('Missing ownership field'); }
        return $this->field_rows[$path];
    }
}
/** Semantic contracts only; accepted task/result owners will retain dependency provenance. */
final class Ownership_Summary {
    private array $parameter_rows /** hash<Parameter_Effects,int> */ = [];
    private array $distinct_rows /** hash<Distinct_Endpoints> */ = [];
    private array $result_rows /** hash<int> */ = [];
    public function __construct(array $parameters /** vector<Parameter_Effects> */,
        array $distinct /** vector<Distinct_Endpoints> */, array $result /** hash<int> */) {
        foreach ($parameters as $parameter) {
            $position = $parameter->position;
            if (isset($this->parameter_rows[$position])) { throw new \InvalidArgumentException('Duplicate ownership parameter'); }
            $this->parameter_rows[$position] = $parameter;
        }
        foreach ($distinct as $pair) {
            if ($pair->left === $pair->right) { throw new \InvalidArgumentException('Ownership exclusion requires distinct endpoints'); }
            $this->require_endpoint($pair->left); $this->require_endpoint($pair->right);
            $canonical = Resource_Locations::distinct_pair($pair->left,$pair->right);
            $key = Resource_Locations::distinct_key($canonical); $this->distinct_rows[$key] = $canonical;
        }
        foreach ($result as $path => $state) {
            $key = '' . $path; Resource_Locations::parameter_parts(Resource_Locations::parameter_key(0,$key));
            if (($state !== \analyze_lifetimes\RESOURCE_EMPTY_VALUE) && ($state !== \analyze_lifetimes\RESOURCE_OWNED_VALUE)) {
                throw new \InvalidArgumentException('Invalid owned result state');
            }
            $this->result_rows[$key] = $state;
        }
    }
    private function require_endpoint(string $key): void {
        $endpoint = Resource_Locations::parameter_parts($key);
        if (!isset($this->parameter_rows[$endpoint->position])) { throw new \InvalidArgumentException('Missing distinct ownership parameter'); }
        if (!$this->parameter_rows[$endpoint->position]->has($endpoint->path)) { throw new \InvalidArgumentException('Missing distinct ownership field'); }
    }
    public function parameters(): array /** hash<Parameter_Effects,int> */ { return $this->parameter_rows; }
    public function parameter(int $position): Parameter_Effects {
        if (!isset($this->parameter_rows[$position])) { throw new \OutOfBoundsException('Missing ownership parameter'); }
        return $this->parameter_rows[$position];
    }
    public function distinct(): array /** hash<Distinct_Endpoints> */ { return $this->distinct_rows; }
    public function result(): array /** hash<int> */ { return $this->result_rows; }
}
