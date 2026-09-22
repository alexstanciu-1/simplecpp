<?php
declare(strict_types=1);
namespace type_model;
const RESOURCE_NONE = 0;
const RESOURCE_ALLOCATION = 1;
const ALLOCATION_ACQUIRE = 1;
const ALLOCATION_RELEASE = 2;
const ALLOCATION_TRANSFER = 3;
const ALLOCATION_INSPECT = 4;
const ALLOCATION_MUTATE = 5;
const ALLOCATION_OBSERVE = 6;

/** Effects on an allocation, distinct from the containing object's lifecycle. */
final class Allocation_Effects {
    public static function require_kind(int $kind): void {
        if (($kind < \type_model\ALLOCATION_ACQUIRE) || ($kind > \type_model\ALLOCATION_OBSERVE)) { throw new \InvalidArgumentException('Unknown allocation effect'); }
    }
    public static function name(int $kind): string {
        Allocation_Effects::require_kind($kind); $name='acquire';
        if ($kind===\type_model\ALLOCATION_RELEASE) { $name='release'; }
        elseif ($kind===\type_model\ALLOCATION_TRANSFER) { $name='transfer'; }
        elseif ($kind===\type_model\ALLOCATION_INSPECT) { $name='inspect'; }
        elseif ($kind===\type_model\ALLOCATION_MUTATE) { $name='mutate'; }
        elseif ($kind===\type_model\ALLOCATION_OBSERVE) { $name='observe'; }
        return $name;
    }
    public static function parse(string $name): int {
        $kind=0;
        if ($name==='acquire') { $kind=\type_model\ALLOCATION_ACQUIRE; }
        elseif ($name==='release') { $kind=\type_model\ALLOCATION_RELEASE; }
        elseif ($name==='transfer') { $kind=\type_model\ALLOCATION_TRANSFER; }
        elseif ($name==='inspect') { $kind=\type_model\ALLOCATION_INSPECT; }
        elseif ($name==='mutate') { $kind=\type_model\ALLOCATION_MUTATE; }
        elseif ($name==='observe') { $kind=\type_model\ALLOCATION_OBSERVE; }
        else { throw new \InvalidArgumentException('Unknown allocation effect'); }
        return $kind;
    }
}

/** Zero-based semantic parameter positions; only transfer has a destination. */
final class Allocation_Effect {
    public function __construct(public readonly int $kind, public readonly int $owner, public readonly ?int $destination = null) {
        Allocation_Effects::require_kind($kind);
        if (($owner < 0) || (($kind===\type_model\ALLOCATION_TRANSFER) !== ($destination!==null))) { throw new \InvalidArgumentException('Invalid allocation effect positions'); }
        if ($destination!==null) {
            if (($destination < 0) || ($destination===$owner)) { throw new \InvalidArgumentException('Transfer requires distinct nonnegative positions'); }
        }
    }
}

/** Immutable obligations: direct allocation ownership or static owning leaf paths. */
final class Resource_Obligations {
    private array $paths /** vector<vector<int>> */ = [];
    public function __construct(public readonly int $kind, array $paths /** vector<vector<int>> */) {
        if (($kind!==\type_model\RESOURCE_NONE) && ($kind!==\type_model\RESOURCE_ALLOCATION)) { throw new \InvalidArgumentException('Unknown resource kind'); }
        $seen /** hash<bool> */ = [];
        foreach ($paths as $path) {
            if (q_count($path)===0) { throw new \InvalidArgumentException('Resource field paths must name static subobjects'); }
            $copy /** vector<int> */ = []; $key='';
            foreach ($path as $ordinal) {
                if ($ordinal < 0) { throw new \InvalidArgumentException('Resource field paths require nonnegative ordinals'); }
                $key=$key . $ordinal . '.'; $copy[]=$ordinal;
            }
            if (isset($seen[$key])) { throw new \InvalidArgumentException('Duplicate resource field path'); }
            $seen[$key]=true; $this->paths[]=$copy;
        }
    }
    public function has_owners(): bool { return ($this->kind!==\type_model\RESOURCE_NONE) || (q_count($this->paths)!==0); }
    public function path_count(): int { return q_count($this->paths); }
    public function path_at(int $index): array /** vector<int> */ {
        if (($index < 0) || ($index >= q_count($this->paths))) { throw new \InvalidArgumentException('Invalid resource path index'); }
        $copy /** vector<int> */ = []; foreach ($this->paths[$index] as $ordinal) { $copy[]=$ordinal; } return $copy;
    }
    public function validate(Representation $representation, Lifetime_Contract $lifetime): void {
        $policy=$lifetime->policy();
        if ($this->kind!==\type_model\RESOURCE_NONE) {
            if (($representation->kind()!==\type_model\REPRESENTATION_OPAQUE) || ((int)$policy->copy!==\type_model\COPY_UNAVAILABLE)) { throw new \InvalidArgumentException('Allocation owners require noncopyable inline storage'); }
        }
        if (q_count($this->paths)!==0) {
            if ($representation->kind()!==\type_model\REPRESENTATION_STRUCTURE) { throw new \InvalidArgumentException('Owning fields require structural storage'); }
            if (((int)$policy->copy!==\type_model\COPY_UNAVAILABLE) && ((int)$policy->copy!==\type_model\COPY_CONSTRUCT)) { throw new \InvalidArgumentException('Owning fields require explicit copying or no copy capability'); }
        }
        if ($this->has_owners()) {
            if ((int)$policy->assignment===\type_model\ASSIGNMENT_VALUE) { throw new \InvalidArgumentException('Owning resources require explicit assignment or no assignment capability'); }
        }
    }
}
