<?php
declare(strict_types=1);
namespace type_model;

const ELEMENT_SAFE_INPUT = 1;
const ELEMENT_INVALIDATE = 2;

final class Family_Parameter {
    public function __construct(public readonly string $name, public readonly int $contract) {
        if ($contract !== \type_model\GENERIC_COPYABLE_VALUE) { throw new \InvalidArgumentException('Unknown family parameter contract'); }
    }
}
final class Capability_Requirement {
    public function __construct(public readonly int $slot, public readonly int $operation) {
        Lifecycle_Roles::require_role($operation);
    }
}
final class Element_Effect {
    public function __construct(public readonly int $kind, public readonly int $receiver, public readonly ?int $safe_input = null) {
        if (($kind !== \type_model\ELEMENT_SAFE_INPUT) && ($kind !== \type_model\ELEMENT_INVALIDATE)) { throw new \InvalidArgumentException('Unknown element effect'); }
    }
}

/** Semantic operation; requirements and overlap effects do not assert ABI readiness. */
final class Family_Operation {
    private array $requirements /** vector<Capability_Requirement> */ = [];
    private array $effects /** vector<Element_Effect> */ = [];
    public function __construct(public readonly string $id, public readonly Semantic_Signature $signature,
        array $requirements /** vector<Capability_Requirement> */, array $effects /** vector<Element_Effect> */,
        public readonly ?int $receiver = null, public readonly ?Type_Reference $expose_as = null) {
        foreach ($requirements as $requirement) { $this->requirements[] = $requirement; }
        foreach ($effects as $effect) { $this->effects[] = $effect; }
        if ($expose_as !== null) {
            if ($expose_as->kind !== \type_model\TYPE_REFERENCE_NAMED) { throw new \InvalidArgumentException('Source exposure requires a language name'); }
        }
    }
    public function requirement_count(): int { return q_count($this->requirements); }
    public function requirement_at(int $index): Capability_Requirement {
        if (($index < 0) || ($index >= q_count($this->requirements))) { throw new \OutOfBoundsException('Missing family requirement'); }
        return $this->requirements[$index];
    }
    public function effect_count(): int { return q_count($this->effects); }
    public function effect_at(int $index): Element_Effect {
        if (($index < 0) || ($index >= q_count($this->effects))) { throw new \OutOfBoundsException('Missing family effect'); }
        return $this->effects[$index];
    }
}

/** Fixed declaration snapshot; maps index identities, vectors preserve declared order. */
final class Family_Definition {
    private array $parameters /** vector<Family_Parameter> */ = [];
    private array $operations /** hash<Family_Operation> */ = [];
    private array $operation_ids /** vector<string> */ = [];
    private array $lifecycle /** hash<string> */ = [];
    public function __construct(public readonly string $provider, public readonly string $id,
        array $parameters /** vector<Family_Parameter> */, array $operations /** hash<Family_Operation> */,
        array $lifecycle /** hash<string> */, public readonly ?Type_Reference $language_type = null) {
        $ordinal = 0;
        foreach ($parameters as $index => $parameter) {
            if ($index !== $ordinal) { throw new \InvalidArgumentException('Family parameters require an ordered list'); }
            $this->parameters[] = $parameter; $ordinal = $ordinal + 1;
        }
        foreach ($operations as $key => $operation) {
            if ($operation->id !== $key) { throw new \InvalidArgumentException('Invalid family operation identity'); }
            $this->operations[$key] = $operation; $this->operation_ids[] = $key;
        }
        foreach ($lifecycle as $role => $operation) { $this->lifecycle[$role] = $operation; }
        if ($language_type !== null) {
            if ($language_type->kind !== \type_model\TYPE_REFERENCE_NAMED) { throw new \InvalidArgumentException('Source exposure requires a language name'); }
        }
    }
    /** Preserve the preparation tool's JSON tuple key exactly. */
    public function key(): string { return '[' . json_quote($this->provider) . ',' . json_quote($this->id) . ']'; }
    public function parameter_count(): int { return q_count($this->parameters); }
    public function parameter_at(int $index): Family_Parameter {
        if (($index < 0) || ($index >= q_count($this->parameters))) { throw new \OutOfBoundsException('Missing family parameter'); }
        return $this->parameters[$index];
    }
    public function operation_count(): int { return q_count($this->operation_ids); }
    public function operation_at(int $index): Family_Operation {
        if (($index < 0) || ($index >= q_count($this->operation_ids))) { throw new \OutOfBoundsException('Missing family operation'); }
        $key = $this->operation_ids[$index]; return $this->operations[$key];
    }
    public function find_operation(string $id): ?Family_Operation {
        if (isset($this->operations[$id])) { return $this->operations[$id]; }
        return null;
    }
    public function lifecycle_bindings(): array /** hash<string> */ {
        $copy /** hash<string> */ = [];
        foreach ($this->lifecycle as $role => $operation) { $copy[$role] = $operation; }
        return $copy;
    }
}
