<?php
declare(strict_types=1);
namespace type_model;
const COPY_UNAVAILABLE = 0;
const COPY_VALUE = 1;
const COPY_CONSTRUCT = 2;
const CLEANUP_NONE = 0;
const CLEANUP_DESTROY = 1;
const CONSTRUCTION_UNAVAILABLE = 0;
const CONSTRUCTION_ZERO = 1;
const CONSTRUCTION_CONSTRUCT = 2;
const ASSIGNMENT_UNAVAILABLE = 0;
const ASSIGNMENT_VALUE = 1;
const ASSIGNMENT_CALL = 2;
const EXPIRING_UNAVAILABLE = 0;
const EXPIRING_VALUE = 1;
const EXPIRING_COPY = 2;
const EXPIRING_CONSTRUCT = 3;

/** Producer input; a contract copies these scalar facts and retains only valid operation bindings. */
/** @scpp-struct */
final class Lifetime_Policy {
    public int $copy /** uint32 */ = 0;
    public int $cleanup /** uint32 */ = 0;
    public int $construction /** uint32 */ = 0;
    public int $assignment /** uint32 */ = 0;
    public int $expiring /** uint32 */ = 0;
}

/** Shared language permissions, independent of any particular value or native ownership state. */
final class Lifetime_Contract {
    private int $copy = 0;
    private int $cleanup = 0;
    private int $construction = 0;
    private int $assignment = 0;
    private int $expiring = 0;
    private array $operations /** hash<Lifecycle_Operation,int> */ = [];
    public function __construct(Lifetime_Policy $policy, array $operations /** vector<Lifecycle_Operation> */) {
        $this->copy = (int)$policy->copy; $this->cleanup = (int)$policy->cleanup;
        $this->construction = (int)$policy->construction; $this->assignment = (int)$policy->assignment; $this->expiring = (int)$policy->expiring;
        if (($this->copy < 0) || ($this->copy > 2) || ($this->cleanup < 0) || ($this->cleanup > 1)
            || ($this->construction < 0) || ($this->construction > 2) || ($this->assignment < 0) || ($this->assignment > 2)
            || ($this->expiring < 0) || ($this->expiring > 3)) { throw new \InvalidArgumentException('Invalid lifetime policy'); }
        foreach ($operations as $operation) {
            $kind = $operation->kind;
            if (isset($this->operations[$kind])) { throw new \InvalidArgumentException('Duplicate lifecycle role binding'); }
            $this->operations[$kind] = $operation;
        }
        $this->require_binding(\type_model\LIFECYCLE_COPY, $this->copy === \type_model\COPY_CONSTRUCT);
        $this->require_binding(\type_model\LIFECYCLE_DESTROY, $this->cleanup === \type_model\CLEANUP_DESTROY);
        $this->require_binding(\type_model\LIFECYCLE_DEFAULT, $this->construction === \type_model\CONSTRUCTION_CONSTRUCT);
        $this->require_binding(\type_model\LIFECYCLE_ASSIGN, $this->assignment === \type_model\ASSIGNMENT_CALL);
        $this->require_binding(\type_model\LIFECYCLE_MOVE, $this->expiring === \type_model\EXPIRING_CONSTRUCT);
        if ($this->expiring === \type_model\EXPIRING_COPY) {
            if ($this->copy === \type_model\COPY_UNAVAILABLE) { throw new \InvalidArgumentException('Expiring copy requires copy capability'); }
        }
    }
    private function require_binding(int $kind, bool $required): void {
        if ($required !== isset($this->operations[$kind])) { throw new \InvalidArgumentException('Lifecycle permission and implementation binding disagree'); }
    }
    public function policy(): Lifetime_Policy {
        $out = new Lifetime_Policy();
        $out->copy = $this->copy; $out->cleanup = $this->cleanup; $out->construction = $this->construction;
        $out->assignment = $this->assignment; $out->expiring = $this->expiring; return $out;
    }
    public function has_operation(int $kind): bool { Lifecycle_Roles::require_role($kind); return isset($this->operations[$kind]); }
    public function operation(int $kind): Lifecycle_Operation {
        if (!$this->has_operation($kind)) { throw new \LogicException('Lifecycle role has no implementation'); }
        return $this->operations[$kind];
    }
}
