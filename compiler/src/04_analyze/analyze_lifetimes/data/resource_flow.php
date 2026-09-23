<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Private flow scratch. Copy before traversing a block or a sibling exit. */
final class Resource_Flow_State {
    public array $states /** hash<int> */ = [];
    public array $mutations /** hash<bool> */ = [];
    public function copy(): Resource_Flow_State {
        $result = new Resource_Flow_State();
        foreach ($this->states as $key => $state) { $result->states[$key] = $state; }
        foreach ($this->mutations as $key => $mutated) { $result->mutations[$key] = $mutated; }
        return $result;
    }
}
/** Validation-only scratch; the fixed-point solver must never receive this owner. */
final class Ownership_Observations {
    public array $required /** hash<int> */ = [];
    public array $mutated /** hash<bool> */ = [];
    public array $returns /** hash<int> */ = [];
    public array $result /** hash<int> */ = [];
    public array $accessed /** hash<bool> */ = [];
    public array $distinct /** hash<Distinct_Endpoints> */ = [];
    public function __construct(array $incoming /** hash<int> */) {
        foreach ($incoming as $key => $mask) {
            if (($mask < 0) || ($mask > 3)) { throw new \InvalidArgumentException('Invalid resource state mask'); }
            $this->required[$key] = $mask;
        }
    }
}
