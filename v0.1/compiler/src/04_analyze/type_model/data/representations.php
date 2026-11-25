<?php
declare(strict_types=1);
namespace type_model;

const REPRESENTATION_VOID = 0;
const REPRESENTATION_INTEGER = 1;
const REPRESENTATION_FLOATING = 2;
const REPRESENTATION_POINTER = 3;
const REPRESENTATION_ARRAY = 4;
const REPRESENTATION_STRUCTURE = 5;
const REPRESENTATION_SIGNATURE = 6;
const REPRESENTATION_OPAQUE = 7;
const REPRESENTATION_BYTE_SPAN = 8;

/** Value shape only: no signedness, lifetime permission, layout inference or backend availability. */
final class Representation {
    private int $tag = 0;
    private int $width = 0;
    private string $format = '';
    private int $element_type = 0;
    private int $address_space = 0;
    private int $first = 0;
    private int $count = 0;
    private int $return_type = 0;
    private int $result = 0;
    private int $size_bytes = 0;
    private int $alignment_bytes = 0;
    private array $passing /** vector<int> */ = [];

    // Default construction is the payload-free void shape; factories are the only writers.
    public function kind(): int { return $this->tag; }
    private function require_kind(int $kind): void {
        if ($this->tag !== $kind) { throw new \LogicException('Wrong representation accessor'); }
    }
    public static function void_type(): Representation { return new Representation(); }
    public static function byte_span(): Representation {
        $out = new Representation(); $out->tag = \type_model\REPRESENTATION_BYTE_SPAN; return $out;
    }
    public static function integer(int $width): Representation {
        if ($width < 1) { throw new \InvalidArgumentException('Integer width must be positive'); }
        $out = new Representation(); $out->tag = \type_model\REPRESENTATION_INTEGER; $out->width = $width; return $out;
    }
    public static function floating(string $format): Representation {
        $width = 0;
        if (($format === 'ieee_binary16') || ($format === 'bfloat16')) { $width = 16; }
        else if ($format === 'ieee_binary32') { $width = 32; }
        else if ($format === 'ieee_binary64') { $width = 64; }
        else if ($format === 'ieee_binary128') { $width = 128; }
        else { throw new \InvalidArgumentException('Unknown floating format'); }
        $out = new Representation(); $out->tag = \type_model\REPRESENTATION_FLOATING;
        $out->format = $format; $out->width = $width; return $out;
    }
    public function bit_width(): int {
        if (($this->tag !== \type_model\REPRESENTATION_INTEGER) && ($this->tag !== \type_model\REPRESENTATION_FLOATING)) { throw new \LogicException('Representation has no scalar bit width'); }
        return $this->width;
    }
    public function floating_format(): string { $this->require_kind(\type_model\REPRESENTATION_FLOATING); return $this->format; }
    public static function pointer(int $element_type, int $address_space): Representation {
        if (($element_type < 1) || ($address_space < 0)) { throw new \InvalidArgumentException('Invalid pointer type or address space'); }
        $out = new Representation(); $out->tag = \type_model\REPRESENTATION_POINTER;
        $out->element_type = $element_type; $out->address_space = $address_space; return $out;
    }
    public static function fixed_array(int $element_type, int $count): Representation {
        if (($element_type < 1) || ($count < 0)) { throw new \InvalidArgumentException('Invalid array type or element count'); }
        $out = new Representation(); $out->tag = \type_model\REPRESENTATION_ARRAY;
        $out->element_type = $element_type; $out->count = $count; return $out;
    }
    public function element(): int {
        if (($this->tag !== \type_model\REPRESENTATION_POINTER) && ($this->tag !== \type_model\REPRESENTATION_ARRAY)) { throw new \LogicException('Representation has no element type'); }
        return $this->element_type;
    }
    public function pointer_address_space(): int { $this->require_kind(\type_model\REPRESENTATION_POINTER); return $this->address_space; }
    private static function require_range(int $first, int $count): void {
        if (($first < 0) || ($count < 0)) { throw new \InvalidArgumentException('Invalid member range'); }
        // The owning store checks membership; do not add these potentially large integers here.
    }
    public static function structure(int $first, int $count): Representation {
        Representation::require_range($first, $count);
        $out = new Representation(); $out->tag = \type_model\REPRESENTATION_STRUCTURE;
        $out->first = $first; $out->count = $count; return $out;
    }
    public static function signature(int $return_type, int $first, int $count, array $passing /** vector<int> */, int $result): Representation {
        if ($return_type < 1) { throw new \InvalidArgumentException('Invalid return type ID'); }
        Representation::require_range($first, $count);
        Semantic_Modes::result_name($result);
        $out = new Representation(); $out->tag = \type_model\REPRESENTATION_SIGNATURE;
        $out->return_type = $return_type; $out->first = $first; $out->count = $count; $out->result = $result;
        if (q_count($passing) === 0) {
            for ($i /** int */ = 0; $i < $count; ++$i) { $out->passing[] = \type_model\PASS_VALUE; }
        } else {
            if (q_count($passing) !== $count) { throw new \InvalidArgumentException('Signature passing count differs from parameter count'); }
            foreach ($passing as $mode) { Semantic_Modes::passing_name($mode); $out->passing[] = $mode; }
        }
        return $out;
    }
    public function member_first(): int {
        if (($this->tag !== \type_model\REPRESENTATION_STRUCTURE) && ($this->tag !== \type_model\REPRESENTATION_SIGNATURE)) { throw new \LogicException('Representation has no member range'); }
        return $this->first;
    }
    public function member_count(): int {
        if (($this->tag !== \type_model\REPRESENTATION_STRUCTURE) && ($this->tag !== \type_model\REPRESENTATION_SIGNATURE)
            && ($this->tag !== \type_model\REPRESENTATION_ARRAY)) { throw new \LogicException('Representation has no element count'); }
        return $this->count;
    }
    public function signature_return(): int { $this->require_kind(\type_model\REPRESENTATION_SIGNATURE); return $this->return_type; }
    public function result_production(): int { $this->require_kind(\type_model\REPRESENTATION_SIGNATURE); return $this->result; }
    public function parameter_passing(int $index): int {
        $this->require_kind(\type_model\REPRESENTATION_SIGNATURE);
        if (($index < 0) || ($index >= q_count($this->passing))) { throw new \InvalidArgumentException('Invalid parameter index'); }
        return $this->passing[$index];
    }
    public static function opaque(int $size, int $alignment): Representation {
        if (($size < 1) || ($alignment < 1)) { throw new \InvalidArgumentException('Invalid opaque storage layout'); }
        // Bounded integer-only validation; stop before doubling could overflow.
        $power = 1;
        while ($power < $alignment) {
            if ($power > $alignment - $power) { throw new \InvalidArgumentException('Opaque alignment must be a power of two'); }
            $power = $power * 2;
        }
        if (($size % $alignment) !== 0) { throw new \InvalidArgumentException('Opaque size must be aligned'); }
        $out = new Representation(); $out->tag = \type_model\REPRESENTATION_OPAQUE;
        $out->size_bytes = $size; $out->alignment_bytes = $alignment; return $out;
    }
    /** Exact value-shape equality; IDs/ranges are interpreted in their owning store. */
    public function same(Representation $other): bool {
        if ($this->tag !== $other->tag) { return false; }
        if ($this->width !== $other->width) { return false; }
        if ($this->format !== $other->format) { return false; }
        if ($this->element_type !== $other->element_type) { return false; }
        if ($this->address_space !== $other->address_space) { return false; }
        if ($this->first !== $other->first) { return false; }
        if ($this->count !== $other->count) { return false; }
        if ($this->return_type !== $other->return_type) { return false; }
        if ($this->result !== $other->result) { return false; }
        if ($this->size_bytes !== $other->size_bytes) { return false; }
        if ($this->alignment_bytes !== $other->alignment_bytes) { return false; }
        if (q_count($this->passing) !== q_count($other->passing)) { return false; }
        foreach ($this->passing as $index => $mode) { if ($mode !== $other->passing[$index]) { return false; } }
        return true;
    }
    public function opaque_size(): int { $this->require_kind(\type_model\REPRESENTATION_OPAQUE); return $this->size_bytes; }
    public function opaque_alignment(): int { $this->require_kind(\type_model\REPRESENTATION_OPAQUE); return $this->alignment_bytes; }
}
