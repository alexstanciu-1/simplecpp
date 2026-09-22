<?php
declare(strict_types=1);
namespace package_syntax_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    public static function run(): void {
        $storage_names /** vector<string> */ = ['integer', 'address', 'byte_span', 'void', 'opaque_inline', 'record'];
        for ($kind = 0; $kind < 6; $kind++) {
            Probe::check((\load_runtime\Runtime_Modes::storage_name($kind) === $storage_names[$kind]) && (\load_runtime\Runtime_Modes::storage($storage_names[$kind]) === $kind));
        }
        $module_names /** vector<string> */ = ['ordinary', 'full_lto', 'thin_lto'];
        for ($kind = 0; $kind < 3; $kind++) { Probe::check((\load_runtime\Runtime_Modes::module_name($kind) === $module_names[$kind]) && (\load_runtime\Runtime_Modes::module_kind($module_names[$kind]) === $kind)); }
        $storage = new \load_runtime\Runtime_Storage(0, 4, 4);
        $integer = new \load_runtime\Runtime_Type('signed32', $storage, 32, true, null);
        $unsigned = new \load_runtime\Runtime_Type('unsigned32', $storage, 32, false, null);
        Probe::check(($integer->id === 'signed32') && ($integer->storage === $storage));
        Probe::check(($storage->size_bytes === 4) && ($storage->alignment_bytes === 4));
        Probe::check($unsigned->signed === false);
        $void_definition = new \type_model\Named_Definition('void', '', \type_model\Representation::void_type(), null, null, '', false, false, false);
        $void_row = new \load_runtime\Runtime_Type('nothing', new \load_runtime\Runtime_Storage(3, 0, 1), null, null, $void_definition);
        Probe::check($void_row->language_type === $void_definition);
        $policy = new \type_model\Lifetime_Policy(); $policy->copy = 1;
        $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        $word = new \type_model\Named_Definition('word', '', \type_model\Representation::integer(32), new \type_model\Lifetime_Contract($policy, $operations), true, '', false, false, true);
        $fields /** vector<\type_model\Field_Declaration> */ = [new \type_model\Field_Declaration('value', \type_model\Field_Type::named($word), true)];
        $record = new \type_model\Record_Declaration('Row', '', $fields, true, 0, null, 0, 0, 0, 0);
        $record_row = new \load_runtime\Runtime_Type('row', new \load_runtime\Runtime_Storage(5, 4, 4), null, null, null, $record);
        Probe::check($record_row->record === $record);
        Probe::check($record_row->language_type === null);
        $attributes /** vector<string> */ = ['', 'noundef', 'signext', 'zeroext', 'noundef signext', 'zeroext noundef', ' noundef  noundef ', "\t noundef\nzeroext\r", 'signext signext', 'signext zeroext', 'byval', 'noundef noalias'];
        $extensions /** vector<int> */ = [0,0,1,2,1,2,0,2,-1,-1,-1,-1];
        $attributes[] = string_byte_from_int(0) . 'signext' . string_byte_from_int(0); $extensions[] = 1;
        $attributes[] = 'noundef' . string_byte_from_int(12) . 'zeroext'; $extensions[] = 2;
        $attributes[] = 'signext' . string_byte_from_int(0) . 'noundef'; $extensions[] = -1;
        for ($index = 0; $index < q_count($attributes); $index++) {
            $actual = -1;
            try { $abi = \load_runtime\Package_Syntax::integer_abi(json_read('"i32"'), json_read(json_quote($attributes[$index])), $integer); $actual = $abi->extension; }
            catch (\RuntimeException $error) { $actual = -1; }
            Probe::check($actual === $extensions[$index]);
        }
        $address_rows /** vector<string> */ = ['{"type":"ptr","attributes":""}', '{"type":"ptr","attributes":" noundef "}', '{"type":"ptr","attributes":"signext"}', '{"type":"ptr","attributes":"sret"}', '{"type":"ptr addrspace(1)","attributes":""}', '{"type":"ptr","attributes":false}', '{}', '[]'];
        for ($index = 0; $index < q_count($address_rows); $index++) {
            $accepted = true;
            try { \load_runtime\Package_Syntax::address_abi(json_read($address_rows[$index])); }
            catch (\RuntimeException $error) { $accepted = false; }
            Probe::check($accepted === ($index < 2));
        }
        $name = \load_runtime\Package_Syntax::language_name(json_read('{"name":"word_32","namespace":""}'));
        Probe::check(($name->name() === 'word_32') && ($name->namespace_name() === ''));
        $names /** vector<string> */ = ['{"name":"9word","namespace":""}', '{"name":"word","namespace":"ns"}', '{"name":"é","namespace":""}', '{"name":"","namespace":""}', '{"name":"word"}', '{"name":false,"namespace":""}'];
        foreach ($names as $row) {
            $rejected = false;
            try { $bad_name = \load_runtime\Package_Syntax::language_name(json_read($row)); } catch (\RuntimeException $error) { $rejected = true; }
            Probe::check($rejected);
        }
        Probe::check(\load_runtime\Package_Syntax::identifier(json_read('"exact/id"')) === 'exact/id');
        Probe::check(\load_runtime\Package_Syntax::positive(json_read('32'), 'width') === 32);
        $invalid_numbers /** vector<string> */ = ['0', '-1', '1.0', '1e0', 'true', '"32"', 'null', '9223372036854775808'];
        foreach ($invalid_numbers as $row) {
            $rejected = false;
            try { \load_runtime\Package_Syntax::positive(json_read($row), 'width'); } catch (\RuntimeException $error) { $rejected = true; }
            Probe::check($rejected);
        }
        $rows = \load_runtime\Package_Syntax::rows(json_read('[{"id":"a"},{"id":"b"}]'), 'type');
        Probe::check(q_count($rows) === 2); Probe::check($rows[1]->member('id')->text() === 'b');
        $empty = \load_runtime\Package_Syntax::rows(json_read('[]'), 'type'); Probe::check(q_count($empty) === 0);
        $bad_rows /** vector<string> */ = ['{}', '[[]]', '[1]', '{"0":{"id":"a"}}'];
        foreach ($bad_rows as $row) {
            $rejected = false;
            try { $bad = \load_runtime\Package_Syntax::rows(json_read($row), 'type'); } catch (\RuntimeException $error) { $rejected = true; }
            Probe::check($rejected);
        }
        for ($case = 0; $case < 4; $case++) {
            $rejected = false;
            try {
                if ($case === 0) { $bad_abi = \load_runtime\Package_Syntax::integer_abi(json_read('"i64"'), json_read('""'), $integer); }
                elseif ($case === 1) { $bad_abi = \load_runtime\Package_Syntax::integer_abi(json_read('"i32"'), json_read('false'), $integer); }
                elseif ($case === 2) { $no_bits = new \load_runtime\Runtime_Type('pointer', new \load_runtime\Runtime_Storage(1, 8, 8), null, null, null); $bad_abi = \load_runtime\Package_Syntax::integer_abi(json_read('"i32"'), json_read('""'), $no_bits); }
                else { \load_runtime\Package_Syntax::identifier(json_read('""')); }
            } catch (\RuntimeException $error) { $rejected = true; }
            Probe::check($rejected);
        }
    }
}
