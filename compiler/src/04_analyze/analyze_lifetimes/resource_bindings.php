<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** The checked body owns values and argument positions; resource contracts consume static local paths. */
final class Resource_Bindings {
    private ?Ownership_Failure $failure_row = null;
    public function __construct(private readonly \check_bodies\Checked_Body $body) {}
    public function failure(): ?Ownership_Failure { return $this->failure_row; }
    public function diagnostic(): ?\resolve_types\Annotation_Diagnostic {
        $failure = $this->failure_row;
        if ($failure === null) { return null; }
        return Resource_Locations::diagnostic($this->body,$failure->node,$failure->reason);
    }
    private function fail(int $node, string $reason): void {
        $this->failure_row = new Ownership_Failure($node,$reason); throw new \RuntimeException($reason);
    }
    public function operand(int $call, int $position): Resource_Location {
        $argument = $this->body->argument_for($call,$position+1); $value = $this->body->value_for($argument->value_id);
        if ($value->kind !== \check_bodies\VALUE_LOCAL_BORROW) { $this->fail($value->source_node_id,'Allocation operation requires an existing local owner'); }
        return $this->source($value);
    }
    public function source(\check_bodies\Typed_Value $value): Resource_Location {
        $place = $value->place();
        for ($i = 0; $i < $place->size(); $i++) {
            if ($place->at($i)->kind !== \check_bodies\PROJECTION_FIELD) { $this->fail($value->source_node_id,'Dynamically selected owner subobjects require an ownership contract'); }
        }
        return Resource_Locations::place($place);
    }
    public function summary_operands(int $call, Ownership_Summary $summary): array /** hash<Resource_Location,int> */ {
        $out /** hash<Resource_Location,int> */ = [];
        foreach ($summary->parameters() as $position => $parameter) { $out[$position] = $this->operand($call,$position); }
        return $out;
    }
    public function allocation_operands(int $call, \type_model\Allocation_Effect $effect): array /** hash<Resource_Location,int> */ {
        $out /** hash<Resource_Location,int> */ = []; $out[$effect->owner] = $this->operand($call,$effect->owner);
        $position = 0;
        if (take_nullable($position,$effect->destination)) { $out[$position] = $this->operand($call,$position); }
        return $out;
    }
}
