<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/bootstrap.php';
use type_model as model;

/** Check the semantic/ABI boundary independently of a particular runtime implementation. */
final class Semantic_Calls_Test
{
    public static function check(bool $valid): void
    {
        if (!$valid) {
            throw new \RuntimeException('Semantic/ABI boundary assertion failed');
        }
    }

    /** Semantic/physical disagreements must fail before they enter type checking or lowering. */
    public static function rejects(callable $operation): void
    {
        try {
            $operation();
        }
        catch (\InvalidArgumentException) {
            return;
        }
        throw new \RuntimeException('Expected semantic/ABI rejection');
    }
}

$type = new model\named_type_reference('example', '');
$owned = new model\semantic_result($type, model\result_production::owned);
$signature = new model\semantic_signature([
    new model\semantic_parameter($type, model\argument_passing::byte_span),
    new model\semantic_parameter($type, model\argument_passing::borrow_const)], $owned);
$abi = new model\runtime_callable_abi('test_symbol', 'ccc', null,
    [new model\runtime_byte_span_abi(new model\runtime_integer_abi(64, model\abi_extension::none)),
        new model\runtime_borrow_abi()], model\result_passing::caller_storage);
$abi->validate($signature);
Semantic_Calls_Test::check($abi->parameter_indices === [[1, 2], [3]]);
Semantic_Calls_Test::rejects(fn() => $abi->validate(new model\semantic_signature($signature->parameters,
    new model\semantic_result($type, model\result_production::none))));
Semantic_Calls_Test::rejects(fn() => $abi->validate(new model\semantic_signature([$signature->parameters[0]], $owned)));
Semantic_Calls_Test::rejects(fn() => $abi->validate(new model\semantic_signature([
    $signature->parameters[0], new model\semantic_parameter($type, model\argument_passing::borrow_mutable)], $owned)));
$direct = new model\runtime_callable_abi('test_direct', 'ccc', null, []);
Semantic_Calls_Test::rejects(fn() => $direct->validate(new model\semantic_signature([],
    new model\semantic_result($type, model\result_production::dependent_value))));
$direct->validate(new model\semantic_signature([], new model\semantic_result($type, model\result_production::none)));
fwrite(STDOUT, "semantic call contracts: physical mappings and incompatible passing/results checked\n");
