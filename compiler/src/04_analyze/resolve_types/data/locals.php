<?php
declare(strict_types=1);
namespace resolve_types;
/** Body-local suffix only; parameter IDs belong to the accepted signature. */
final class Local_Type_Request {
    private array $definitions /** vector<\type_model\Named_Definition> */ = [];
    public function __construct(public readonly Callable_Input $input,
        public readonly \resolve_symbols\Symbol_Resolution $names,
        array $definitions /** vector<\type_model\Named_Definition> */) {
        if ($names->owner !== $input->owner) { throw new \LogicException('Local request requires its exact binding owner'); }
        foreach ($definitions as $definition) { $this->definitions[] = $definition; }
    }
    public function size(): int { return q_count($this->definitions); }
    public function at(int $position): \type_model\Named_Definition {
        if (($position < 0) || ($position >= q_count($this->definitions))) { throw new \OutOfBoundsException('Missing body-local definition'); }
        return $this->definitions[$position];
    }
}
