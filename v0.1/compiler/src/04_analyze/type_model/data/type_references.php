<?php
declare(strict_types=1);
namespace type_model;

const TYPE_REFERENCE_NAMED = 1;
const TYPE_REFERENCE_PROVIDER = 2;
const TYPE_REFERENCE_PARAMETER = 3;
const TYPE_REFERENCE_FAMILY = 4;

/** Unresolved declaration identity; never a concrete layout or a resolved type ID.
 * identity/scope mean name/namespace, id/provider, owner/empty, or family-key/empty.
 * Factories and guarded accessors keep those four meanings explicit to consumers. */
final class Type_Reference {
    private array $arguments /** vector<Type_Reference> */ = [];
    public function __construct(public readonly int $kind, private readonly string $identity,
        private readonly string $scope, private readonly int $slot,
        array $arguments /** vector<Type_Reference> */) {
        if (($kind < \type_model\TYPE_REFERENCE_NAMED) || ($kind > \type_model\TYPE_REFERENCE_FAMILY)) {
            throw new \InvalidArgumentException('Unknown declaration type reference');
        }
        if ($slot < 0) { throw new \InvalidArgumentException('Negative type parameter slot'); }
        if ($kind !== \type_model\TYPE_REFERENCE_PARAMETER) {
            if ($slot !== 0) { throw new \InvalidArgumentException('Only parameters have slots'); }
        }
        if (($kind === \type_model\TYPE_REFERENCE_PARAMETER) || ($kind === \type_model\TYPE_REFERENCE_FAMILY)) {
            if ($scope !== '') { throw new \InvalidArgumentException('Unexpected reference qualifier'); }
        }
        if ($kind !== \type_model\TYPE_REFERENCE_FAMILY) {
            if (q_count($arguments) !== 0) { throw new \InvalidArgumentException('Only families have arguments'); }
        }
        $ordinal = 0;
        foreach ($arguments as $index => $argument) {
            if ($index !== $ordinal) { throw new \InvalidArgumentException('Family arguments require an ordered list'); }
            $this->arguments[] = $argument; $ordinal = $ordinal + 1;
        }
    }
    public static function named(string $name, string $namespace_name): Type_Reference {
        $empty /** vector<Type_Reference> */ = [];
        return new Type_Reference(\type_model\TYPE_REFERENCE_NAMED, $name, $namespace_name, 0, $empty);
    }
    public static function provided(string $provider, string $id): Type_Reference {
        $empty /** vector<Type_Reference> */ = [];
        return new Type_Reference(\type_model\TYPE_REFERENCE_PROVIDER, $id, $provider, 0, $empty);
    }
    public static function parameter(string $owner, int $slot): Type_Reference {
        $empty /** vector<Type_Reference> */ = [];
        return new Type_Reference(\type_model\TYPE_REFERENCE_PARAMETER, $owner, '', $slot, $empty);
    }
    public static function family(string $key, array $arguments /** vector<Type_Reference> */): Type_Reference {
        return new Type_Reference(\type_model\TYPE_REFERENCE_FAMILY, $key, '', 0, $arguments);
    }
    private function require_kind(int $kind): void {
        if ($this->kind !== $kind) { throw new \LogicException('Wrong declaration type reference accessor'); }
    }
    public function name(): string { $this->require_kind(\type_model\TYPE_REFERENCE_NAMED); return $this->identity; }
    public function namespace_name(): string { $this->require_kind(\type_model\TYPE_REFERENCE_NAMED); return $this->scope; }
    public function provider(): string { $this->require_kind(\type_model\TYPE_REFERENCE_PROVIDER); return $this->scope; }
    public function id(): string { $this->require_kind(\type_model\TYPE_REFERENCE_PROVIDER); return $this->identity; }
    public function owner(): string { $this->require_kind(\type_model\TYPE_REFERENCE_PARAMETER); return $this->identity; }
    public function parameter_slot(): int { $this->require_kind(\type_model\TYPE_REFERENCE_PARAMETER); return $this->slot; }
    public function family_key(): string { $this->require_kind(\type_model\TYPE_REFERENCE_FAMILY); return $this->identity; }
    public function argument_count(): int { $this->require_kind(\type_model\TYPE_REFERENCE_FAMILY); return q_count($this->arguments); }
    public function argument_at(int $index): Type_Reference {
        $this->require_kind(\type_model\TYPE_REFERENCE_FAMILY);
        if (($index < 0) || ($index >= q_count($this->arguments))) { throw new \OutOfBoundsException('Missing family type argument'); }
        return $this->arguments[$index];
    }
}
