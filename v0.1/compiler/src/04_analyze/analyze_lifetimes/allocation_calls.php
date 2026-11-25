<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Runtime metadata selects semantic operand positions, independent of source parameter spelling. */
final class Allocation_Calls {
    private ?Ownership_Failure $failure_row = null;
    public function __construct(private readonly Resource_Calls $contracts) {}
    public function failure(): ?Ownership_Failure {
        if ($this->failure_row !== null) { return $this->failure_row; }
        return $this->contracts->failure();
    }
    private function fail(int $node, string $reason): void {
        $this->failure_row = new Ownership_Failure($node,$reason); throw new \RuntimeException($reason);
    }
    public function apply(\type_model\Allocation_Effect $effect, array $operands /** hash<Resource_Location,int> */,
        Resource_Flow_State $flow, array $borrows /** vector<Resource_Location> */, int $node): void {
        if (!isset($operands[$effect->owner])) { throw new \LogicException('Missing allocation owner operand'); }
        $owner = $operands[$effect->owner]; $required = \analyze_lifetimes\RESOURCE_EITHER;
        if ($effect->kind === \type_model\ALLOCATION_ACQUIRE) { $required = \analyze_lifetimes\RESOURCE_EMPTY; }
        else if (($effect->kind === \type_model\ALLOCATION_INSPECT) || ($effect->kind === \type_model\ALLOCATION_TRANSFER) || ($effect->kind === \type_model\ALLOCATION_MUTATE)) { $required = \analyze_lifetimes\RESOURCE_OWNED; }
        $result = \analyze_lifetimes\RESOURCE_IDENTITY;
        if ($effect->kind === \type_model\ALLOCATION_ACQUIRE) { $result = \analyze_lifetimes\RESOURCE_OWNED_VALUE; }
        else if (($effect->kind === \type_model\ALLOCATION_RELEASE) || ($effect->kind === \type_model\ALLOCATION_TRANSFER)) { $result = \analyze_lifetimes\RESOURCE_EMPTY_VALUE; }
        $mutates = ($effect->kind !== \type_model\ALLOCATION_INSPECT) && ($effect->kind !== \type_model\ALLOCATION_OBSERVE);
        $reason = 'Allocation operation requires an owned allocation';
        if ($required === \analyze_lifetimes\RESOURCE_EMPTY) { $reason = 'Allocation acquisition requires an empty owner'; }
        $this->contracts->require_state($owner,$required,$flow,$node,$reason);
        $position = 0;
        if (take_nullable($position,$effect->destination)) {
            if (!isset($operands[$position])) { throw new \LogicException('Missing allocation destination operand'); }
            $destination = $operands[$position];
            if (Resource_Locations::overlaps($owner,$destination)) { $this->fail($node,'Allocation transfer requires a distinct empty destination'); }
            $this->contracts->exclude($owner,$destination,$node);
            $this->contracts->require_state($destination,\analyze_lifetimes\RESOURCE_EMPTY,$flow,$node,'Allocation transfer requires a distinct empty destination');
            $destination_transition = new Resource_Transition(\analyze_lifetimes\RESOURCE_EMPTY,\analyze_lifetimes\RESOURCE_OWNED_VALUE,true,true);
            $this->contracts->record_access($destination,$flow,$node);
            $this->contracts->check_mutation($destination,$destination_transition,$borrows,$node);
            $this->contracts->apply_transition($destination,$destination_transition,$flow);
        }
        $transition = new Resource_Transition($required,$result,$mutates,true);
        $this->contracts->record_access($owner,$flow,$node);
        $this->contracts->check_mutation($owner,$transition,$borrows,$node);
        $this->contracts->apply_transition($owner,$transition,$flow);
    }
}
