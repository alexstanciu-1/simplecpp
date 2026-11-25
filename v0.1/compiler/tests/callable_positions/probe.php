<?php
declare(strict_types=1);
namespace callable_positions_test;
final class Probe {
    private static function abi_text(\type_model\Runtime_Abi_Position $abi): string {
        $text = 'unknown';
        if ($abi->kind === 1) { $text = 'integer:' . $abi->bits . ':' . $abi->extension; }
        elseif ($abi->kind === 2) { $text = $abi->mutable ? 'mutable' : 'const'; }
        else {
            $length = $abi->length;
            if ($length !== null) { $text = 'span:' . $length->bits; }
        }
        return $text;
    }
    public static function run(string $text): void {
        $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        $policy = new \type_model\Lifetime_Policy();
        $life = new \type_model\Lifetime_Contract($policy, $operations);
        $word = new \type_model\Named_Definition('Word', '', \type_model\Representation::integer(32), $life, true, '', false, false, true);
        $object = new \type_model\Named_Definition('Object', '', \type_model\Representation::opaque(8, 8), $life, null, '', false, false, false);
        $span = new \type_model\Named_Definition('Span', '', \type_model\Representation::byte_span(), $life, null, '', false, false, false);
        $void_type = new \type_model\Named_Definition('Void', '', \type_model\Representation::void_type(), null, null, '', false, false, false);
        $fields /** vector<\type_model\Field_Declaration> */ = [];
        $record = new \type_model\Record_Declaration('Row', '', $fields, true, 0, null, 0, 0, 0, 0);
        $types /** hash<\load_runtime\Runtime_Type> */ = [];
        $types['word'] = new \load_runtime\Runtime_Type('word', new \load_runtime\Runtime_Storage(0,4,4),32,true,$word);
        $types['object'] = new \load_runtime\Runtime_Type('object', new \load_runtime\Runtime_Storage(4,8,8),null,null,$object);
        $types['span'] = new \load_runtime\Runtime_Type('span', new \load_runtime\Runtime_Storage(2,16,8),null,null,$span);
        $types['void'] = new \load_runtime\Runtime_Type('void', new \load_runtime\Runtime_Storage(3,0,1),null,null,$void_type);
        $types['record'] = new \load_runtime\Runtime_Type('record', new \load_runtime\Runtime_Storage(5,8,8),null,null,null,$record);
        $types['hidden'] = new \load_runtime\Runtime_Type('hidden', new \load_runtime\Runtime_Storage(4,8,8),null,null,null);
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index); $actual = 'error';
            try {
                $positions = \load_runtime\Package_Syntax::rows($fixture->member('positions'), 'ABI position');
                if ($fixture->member('mode')->integer() === 0) {
                    $result = \load_runtime\Callable_Abi_Import::call_result($fixture->member('row'), $types, $positions);
                    $abi = $result->abi; $physical = 'none';
                    if ($abi !== null) { $physical = Probe::abi_text($abi); }
                    $actual = $result->semantic->type->name() . ':' . $result->passing . ':' . $result->semantic->production . ':' . $physical . ':' . $result->next_position;
                    if ($result->definition->name !== $result->semantic->type->name()) { $actual = 'wrong definition'; }
                } else {
                    $parameter = \load_runtime\Callable_Abi_Import::call_parameter($fixture->member('row'), $types, $positions, $fixture->member('offset')->integer());
                    $actual = $parameter->semantic->type->name() . ':' . $parameter->semantic->passing . ':' . Probe::abi_text($parameter->abi) . ':' . $parameter->next_position;
                }
            } catch (\RuntimeException $error) { $actual = 'error'; }
            echo $actual === $fixture->member('want')->text() ? "true\n" : "false\n";
        }
    }
}
