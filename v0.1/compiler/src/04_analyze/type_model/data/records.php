<?php
declare(strict_types=1);
namespace type_model;
const RECORD_LAYOUT_TARGET = 0;
const RECORD_LAYOUT_NATIVE_VERIFIED = 1;

/** Named value or fixed-array recipe. Zero extent means the named value itself. */
final class Field_Type {
    public function __construct(public readonly Named_Definition $element, public readonly int $extent) {
        if ($extent < 0) { throw new \InvalidArgumentException('Negative field array extent'); }
        if ($extent > 0) {
            if ($element->ownership !== null) {
                if ($element->ownership->has_owners()) { throw new \InvalidArgumentException('Arrays of allocation owners require dynamic subobject ownership contracts'); }
            }
        }
    }
    public static function named(Named_Definition $definition): Field_Type { return new Field_Type($definition,0); }
    public static function fixed_array(Named_Definition $element, int $count): Field_Type {
        if ($count < 1) { throw new \InvalidArgumentException('Array extent must be positive'); }
        return new Field_Type($element,$count);
    }
}

final class Field_Declaration {
    public function __construct(public readonly string $name, public readonly Field_Type $definition, public readonly bool $writable) {}
}

/** Producer-neutral structural input; shared immutable fields and captured source body IDs. */
final class Record_Declaration {
    private array $fields /** vector<Field_Declaration> */ = [];
    public function __construct(public readonly string $name, public readonly string $namespace_name,
        array $fields /** vector<Field_Declaration> */, public readonly bool $automatic_lifecycle,
        public readonly int $layout_policy, public readonly ?Native_Record_Layout $native_layout,
        public readonly int $constructor_body, public readonly int $destructor_body,
        public readonly int $copy_body, public readonly int $assignment_body) {
        if (($layout_policy!==\type_model\RECORD_LAYOUT_TARGET) && ($layout_policy!==\type_model\RECORD_LAYOUT_NATIVE_VERIFIED)) { throw new \InvalidArgumentException('Unknown record layout policy'); }
        if (($constructor_body < 0) || ($destructor_body < 0) || ($copy_body < 0) || ($assignment_body < 0)) { throw new \InvalidArgumentException('Invalid source lifecycle body identity'); }
        foreach ($fields as $field) { $this->fields[]=$field; }
    }
    public function field_count(): int { return q_count($this->fields); }
    public function field_at(int $index): Field_Declaration {
        if (($index < 0) || ($index >= q_count($this->fields))) { throw new \InvalidArgumentException('Invalid record declaration field index'); }
        return $this->fields[$index];
    }
}
