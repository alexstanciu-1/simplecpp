<?php
declare(strict_types=1);
namespace resolve_types;
/** Fixed signature membership in one canonical store; not a completed type stage.
 * The coordinator must stop mutating the store before publishing to consumers.
 */
final class Signature_Set {
    private array $rows /** vector<Callable_Signature> */ = [];
    private array $index /** hash<int,int> */ = [];
    public function __construct(public readonly \type_model\Type_Store $types,
        array $signatures /** vector<Callable_Signature> */) {
        foreach ($signatures as $signature) {
            $id = $signature->callable_id;
            if (isset($this->index[$id])) { throw new \LogicException('Duplicate signature owner'); }
            $shape = $types->representation_by_id($signature->representation_id);
            if ($shape->kind() !== \type_model\REPRESENTATION_SIGNATURE) { throw new \LogicException('Expected callable signature representation'); }
            $types->definition_for_type($shape->signature_return());
            for ($i = 0; $i < $shape->member_count(); $i++) { $types->definition_for_type($types->member_at($shape->member_first()+$i)->type_id); }
            $this->index[$id] = q_count($this->rows); $this->rows[] = $signature;
        }
    }
    public function size(): int { return q_count($this->rows); }
    public function at(int $position): Callable_Signature {
        if (($position < 0) || ($position >= q_count($this->rows))) { throw new \OutOfBoundsException('Missing signature position'); }
        return $this->rows[$position];
    }
    public function for_callable(int $id): ?Callable_Signature {
        if (!isset($this->index[$id])) { return null; }
        return $this->rows[$this->index[$id]];
    }
}
