<?php
declare(strict_types=1);
namespace check_bodies;
const VALUE_INTEGER_LITERAL = 1;
const VALUE_RECORD_DEFAULT = 2;
const VALUE_DEFAULT_CONSTRUCT = 3;
const VALUE_BYTE_LITERAL = 4;
const VALUE_CALL_RESULT = 5;
const VALUE_LOCAL_READ = 6;
const VALUE_LOCAL_BORROW = 7;
const VALUE_CONVERSION = 8;
const VALUE_OPERATION = 9;
const CONVERSION_INTEGER_WIDEN = 1;
final class Conversion_Value {
    public function __construct(public readonly int $input_value_id, public readonly int $operation) {
        if ($input_value_id < 1) { throw new \LogicException('A conversion node requires an input value'); }
        if ($operation !== \check_bodies\CONVERSION_INTEGER_WIDEN) { throw new \InvalidArgumentException('Invalid primitive conversion'); }
    }
}
final class Operation_Value {
    public function __construct(public readonly int $left, public readonly int $right,
        public readonly \type_model\Operation_Contract $contract) {}
}
/** Binary source contents, shared by checking and lowering. */
final class Byte_Literal {
    public function __construct(public readonly string $bytes) {}
    public function to_json(): string {
        $digits = '0123456789abcdef'; $hex = '';
        for ($i = 0; $i < string_byte_len($this->bytes); $i++) {
            $byte = string_byte_at($this->bytes, $i);
            $high = (int)($byte / 16); $low = $byte % 16;
            $hex .= string_byte_slice($digits, $high, 1) . string_byte_slice($digits, $low, 1);
        }
        return '{"hex":"' . $hex . '"}';
    }
}
/** Tagged payload with checked access; containing body owns ID/type/readiness validation. */
final class Typed_Value {
    public function __construct(public readonly int $source_node_id, public readonly int $type_id,
        public readonly int $kind, private readonly string $literal_text = '', private readonly int $producing_call = 0,
        private readonly ?Place $location = null, private readonly ?Conversion_Value $conversion_payload = null,
        private readonly ?Operation_Value $operation_payload = null, private readonly ?Byte_Literal $byte_payload = null) {
        if (($kind < \check_bodies\VALUE_INTEGER_LITERAL) || ($kind > \check_bodies\VALUE_OPERATION)) { throw new \InvalidArgumentException('Invalid checked value kind'); }
        if (($kind !== \check_bodies\VALUE_INTEGER_LITERAL) && ($literal_text !== '')) { throw new \LogicException('Unexpected literal payload'); }
        if (($kind === \check_bodies\VALUE_CALL_RESULT) !== ($producing_call > 0)) { throw new \LogicException('Invalid producing call payload'); }
        if ($producing_call < 0) { throw new \LogicException('Invalid producing call payload'); }
        $place_kind = ($kind === \check_bodies\VALUE_LOCAL_READ) || ($kind === \check_bodies\VALUE_LOCAL_BORROW);
        if ($place_kind !== ($location !== null)) { throw new \LogicException('Invalid location payload'); }
        if (($kind === \check_bodies\VALUE_CONVERSION) !== ($conversion_payload !== null)) { throw new \LogicException('Invalid conversion payload'); }
        if (($kind === \check_bodies\VALUE_OPERATION) !== ($operation_payload !== null)) { throw new \LogicException('Invalid operation payload'); }
        if (($kind === \check_bodies\VALUE_BYTE_LITERAL) !== ($byte_payload !== null)) { throw new \LogicException('Invalid byte payload'); }
    }
    public function integer_text(): string {
        if ($this->kind !== \check_bodies\VALUE_INTEGER_LITERAL) { throw new \LogicException('Not an integer literal'); }
        return $this->literal_text;
    }
    public function call_id(): int {
        if ($this->kind !== \check_bodies\VALUE_CALL_RESULT) { throw new \LogicException('Not a call result'); }
        return $this->producing_call;
    }
    public function place(): Place {
        $payload = $this->location;
        if ($payload === null) { throw new \LogicException('Wrong checked value payload'); }
        return $payload;
    }
    public function conversion(): Conversion_Value {
        $payload = $this->conversion_payload;
        if ($payload === null) { throw new \LogicException('Wrong checked value payload'); }
        return $payload;
    }
    public function operation(): Operation_Value {
        $payload = $this->operation_payload;
        if ($payload === null) { throw new \LogicException('Wrong checked value payload'); }
        return $payload;
    }
    public function byte_literal(): Byte_Literal {
        $payload = $this->byte_payload;
        if ($payload === null) { throw new \LogicException('Wrong checked value payload'); }
        return $payload;
    }
}
