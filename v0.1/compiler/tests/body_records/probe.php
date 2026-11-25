<?php
declare(strict_types=1);
namespace body_records_test;
final class Probe {
    public static function value(int $kind, string $hex): bool {
        $none /** vector<\check_bodies\Place_Projection> */ = [];
        $place = new \check_bodies\Place(7, $none);
        $conversion = new \check_bodies\Conversion_Value(1, \check_bodies\CONVERSION_INTEGER_WIDEN);
        $operands /** vector<int> */ = [2, 2];
        $contract = new \type_model\Operation_Contract('addition', $operands, 2,
            new \type_model\Implementation_Binding(1, 'compiler.integer', 'add_wrap'));
        $operation = new \check_bodies\Operation_Value(1, 2, $contract);
        $bytes = ''; for ($i = 0; $i < 256; $i++) { $bytes .= string_byte_from_int($i); }
        $literal = new \check_bodies\Byte_Literal($bytes);
        $value = new \check_bodies\Typed_Value(11, 2, \check_bodies\VALUE_RECORD_DEFAULT);
        if ($kind === \check_bodies\VALUE_INTEGER_LITERAL) { $value = new \check_bodies\Typed_Value(11, 2, $kind, '18446744073709551615'); }
        elseif ($kind === \check_bodies\VALUE_BYTE_LITERAL) { $value = new \check_bodies\Typed_Value(11, 2, $kind, '', 0, null, null, null, $literal); }
        elseif ($kind === \check_bodies\VALUE_CALL_RESULT) { $value = new \check_bodies\Typed_Value(11, 2, $kind, '', 4); }
        elseif (($kind === \check_bodies\VALUE_LOCAL_READ) || ($kind === \check_bodies\VALUE_LOCAL_BORROW)) { $value = new \check_bodies\Typed_Value(11, 2, $kind, '', 0, $place); }
        elseif ($kind === \check_bodies\VALUE_CONVERSION) { $value = new \check_bodies\Typed_Value(11, 2, $kind, '', 0, null, $conversion); }
        elseif ($kind === \check_bodies\VALUE_OPERATION) { $value = new \check_bodies\Typed_Value(11, 2, $kind, '', 0, null, null, $operation); }
        else { $value = new \check_bodies\Typed_Value(11, 2, $kind); }
        $valid = ($value->source_node_id === 11) && ($value->type_id === 2) && ($value->kind === $kind);
        if ($kind === \check_bodies\VALUE_INTEGER_LITERAL) { if ($value->integer_text() !== '18446744073709551615') { $valid = false; } }
        if ($kind === \check_bodies\VALUE_BYTE_LITERAL) {
            if ($value->byte_literal() !== $literal) { $valid = false; }
            if ($literal->to_json() !== '{"hex":"' . $hex . '"}') { $valid = false; }
        }
        if ($kind === \check_bodies\VALUE_CALL_RESULT) { if ($value->call_id() !== 4) { $valid = false; } }
        if (($kind === \check_bodies\VALUE_LOCAL_READ) || ($kind === \check_bodies\VALUE_LOCAL_BORROW)) { if ($value->place() !== $place) { $valid = false; } }
        if ($kind === \check_bodies\VALUE_CONVERSION) { if ($value->conversion() !== $conversion) { $valid = false; } }
        if ($kind === \check_bodies\VALUE_OPERATION) { if ($value->operation() !== $operation) { $valid = false; } }
        $failed = false;
        try {
            if ($kind === \check_bodies\VALUE_INTEGER_LITERAL) { $unused = $value->call_id(); }
            else { $unused_text = $value->integer_text(); }
        } catch (\LogicException $error) { $failed = true; }
        if (!$failed) { $valid = false; }
        // A present foreign payload is rejected even when this kind also has a valid payload.
        $failed = false;
        try { $bad = new \check_bodies\Typed_Value(11, 2, \check_bodies\VALUE_INTEGER_LITERAL, '1', 0, $place); }
        catch (\LogicException $error) { $failed = true; }
        if (!$failed) { $valid = false; }
        return $valid;
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        $none /** vector<\check_bodies\Place_Projection> */ = [];
        $place = new \check_bodies\Place(7, $none);
        for ($i = 0; $i < $cases->size(); $i++) {
            $row = $cases->at($i); $valid = true;
            if ($row->member('mode')->text() === 'value') { $valid = \body_records_test\Probe::value($row->member('kind')->integer(), $row->member('hex')->text()); }
            else {
                $failed = false;
                try {
                    $statement = new \check_bodies\Typed_Statement(23, \check_bodies\STATEMENT_EXPRESSION, 0, 3, 4, 1);
                    if ($row->member('target')->boolean()) {
                        $statement = new \check_bodies\Typed_Statement(23, $row->member('kind')->integer(), $row->member('value')->integer(), 3, 4, $row->member('scope')->integer(), $place, $row->member('write')->integer(), $row->member('ret')->integer());
                        if ($statement->target !== $place) { $valid = false; }
                    } else {
                        $statement = new \check_bodies\Typed_Statement(23, $row->member('kind')->integer(), $row->member('value')->integer(), 3, 4, $row->member('scope')->integer(), null, $row->member('write')->integer(), $row->member('ret')->integer());
                    }
                    if (($statement->call_start !== 3) || ($statement->call_count !== 4) || ($statement->source_node_id !== 23)) { $valid = false; }
                } catch (\LogicException $error) { $failed = true; }
                if ($failed === $row->member('valid')->boolean()) { $valid = false; }
            }
            echo $valid ? "true\n" : "false\n";
        }
        $call = new \check_bodies\Typed_Call(1, 2, 0, 3, 4);
        if (($call->result_value_id !== 0) || ($call->argument_start !== 3) || ($call->argument_count !== 4)) { throw new \LogicException('Void call changed'); }
        for ($passing = 0; $passing < 4; $passing++) {
            $argument = new \check_bodies\Typed_Argument(1, 2, $passing);
            if ($argument->passing !== $passing) { throw new \LogicException('Passing changed'); }
        }
        $failed = false;
        try { $bad_argument = new \check_bodies\Typed_Argument(0, 2); } catch (\LogicException $error) { $failed = true; }
        if (!$failed) { throw new \LogicException('Void argument accepted'); }
        $scope = new \check_bodies\Typed_Scope(2, 0);
        if ($scope->statement_count !== 0) { throw new \LogicException('Empty scope changed'); }
    }
}
