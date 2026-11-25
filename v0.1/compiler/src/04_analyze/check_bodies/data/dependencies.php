<?php
declare(strict_types=1);
namespace check_bodies;
/** Immutable signature and its canonical parameter IDs, independent of mutable store lifetime. */
final class Signature_Dependency {
    private array $parameters /** vector<int> */ = [];
    public function __construct(public readonly int $callable_id, public readonly int $representation_id,
        public readonly \type_model\Representation $representation, array $parameters /** vector<int> */,
        public readonly ?\type_model\Runtime_Callable $external = null,
        public readonly ?\type_model\Storage_Function $storage = null) {
        if ($representation->kind() !== \type_model\REPRESENTATION_SIGNATURE) { throw new \LogicException('Expected a resolved callable signature'); }
        if (q_count($parameters) !== $representation->member_count()) { throw new \LogicException('Incomplete checked signature parameters'); }
        foreach ($parameters as $type) {
            if ($type < 1) { throw new \LogicException('Invalid checked signature parameter'); }
            $this->parameters[] = $type;
        }
    }
    public static function capture(\type_model\Type_Store $types, \resolve_types\Callable_Signature $signature): Signature_Dependency {
        $shape = $types->representation_by_id($signature->representation_id);
        if ($shape->kind() !== \type_model\REPRESENTATION_SIGNATURE) { throw new \LogicException('Expected a resolved callable signature'); }
        $parameters /** vector<int> */ = [];
        for ($i = 0; $i < $shape->member_count(); $i++) { $parameters[] = $types->member_at($shape->member_first() + $i)->type_id; }
        return new Signature_Dependency($signature->callable_id, $signature->representation_id, $shape, $parameters, $signature->external, $signature->storage);
    }
    public function parameter_count(): int { return q_count($this->parameters); }
    public function parameter_type(int $index): int {
        if (($index < 0) || ($index >= q_count($this->parameters))) { throw new \OutOfBoundsException('Missing checked signature parameter'); }
        return $this->parameters[$index];
    }
}
