<?php
declare(strict_types=1);
namespace representation_test;
final class Probe {
    private static function check(bool $value): void { echo $value ? "true\n" : "false\n"; }
    private static function invalid(int $test): bool {
        $none /** vector<int> */ = [];
        try {
            if ($test === 0) { \type_model\Representation::integer(0); }
            if ($test === 1) { \type_model\Representation::floating('float'); }
            if ($test === 2) { \type_model\Representation::pointer(0, 0); }
            if ($test === 3) { \type_model\Representation::pointer(1, -1); }
            if ($test === 4) { \type_model\Representation::fixed_array(1, -1); }
            if ($test === 5) { \type_model\Representation::fixed_array(0, 1); }
            if ($test === 6) { \type_model\Representation::structure(-1, 1); }
            if ($test === 7) { \type_model\Representation::structure(0, -1); }
            if ($test === 8) { \type_model\Representation::signature(0, 0, 0, $none, 1); }
            if ($test === 9) { \type_model\Representation::signature(1, 0, -1, $none, 1); }
            if ($test === 10) { \type_model\Representation::signature(1, 0, 0, $none, 9); }
            if ($test === 11) { $modes /** vector<int> */ = [0]; \type_model\Representation::signature(1, 0, 2, $modes, 1); }
            if ($test === 12) { $modes /** vector<int> */ = [4]; \type_model\Representation::signature(1, 0, 1, $modes, 1); }
            if ($test === 13) { \type_model\Representation::opaque(4, 0); }
            if ($test === 14) { \type_model\Representation::opaque(0, 4); }
            if ($test === 15) { \type_model\Representation::opaque(6, 3); }
            if ($test === 16) { \type_model\Representation::opaque(6, 4); }
            if ($test === 17) { \type_model\Semantic_Modes::passing('reference'); }
            if ($test === 18) { \type_model\Semantic_Modes::result_name(-1); }
            if ($test === 19) { $context = new \type_model\Type_Context('', 'p', 't'); }
            if ($test === 20) { $context = new \type_model\Type_Context('c', '', 't'); }
            if ($test === 21) { $context = new \type_model\Type_Context('c', 'p', ''); }
            if ($test === 22) { $member = new \type_model\Type_Member(0, '', true); }
            if ($test === 23) { \type_model\Representation::signature(1, 0, 0, $none, 1)->parameter_passing(0); }
            if ($test === 24) { \type_model\Representation::signature(1, 0, 1, $none, 1)->parameter_passing(-1); }
        } catch (\InvalidArgumentException $error) { return true; }
        return false;
    }
    public static function run(): void {
        $void = \type_model\Representation::void_type();
        Probe::check($void->kind() === 0);
        Probe::check(\type_model\Representation::byte_span()->kind() === 8);
        $integer = \type_model\Representation::integer(37);
        Probe::check($integer->kind() === 1);
        Probe::check($integer->bit_width() === 37);
        Probe::check(\type_model\Representation::integer(4294967296)->bit_width() === 4294967296);
        $formats /** vector<string> */ = ['ieee_binary16','bfloat16','ieee_binary32','ieee_binary64','ieee_binary128'];
        $widths /** vector<int> */ = [16,16,32,64,128];
        for ($i /** int */ = 0; $i < 5; ++$i) {
            $float_shape = \type_model\Representation::floating($formats[$i]);
            Probe::check($float_shape->kind() === 2);
            Probe::check($float_shape->floating_format() === $formats[$i]);
            Probe::check($float_shape->bit_width() === $widths[$i]);
        }
        $pointer = \type_model\Representation::pointer(7, 3);
        Probe::check($pointer->kind() === 3);
        Probe::check($pointer->element() === 7);
        Probe::check($pointer->pointer_address_space() === 3);
        $array_shape = \type_model\Representation::fixed_array(8, 0);
        Probe::check($array_shape->kind() === 4);
        Probe::check($array_shape->element() === 8);
        Probe::check($array_shape->member_count() === 0);
        $record = \type_model\Representation::structure(12, 3);
        Probe::check($record->kind() === 5);
        Probe::check($record->member_first() === 12);
        Probe::check($record->member_count() === 3);
        $none /** vector<int> */ = [];
        $signature = \type_model\Representation::signature(9, 2, 2, $none, \type_model\RESULT_VALUE);
        Probe::check($signature->kind() === 6);
        Probe::check($signature->signature_return() === 9);
        Probe::check($signature->member_first() === 2);
        Probe::check($signature->member_count() === 2);
        Probe::check($signature->parameter_passing(0) === \type_model\PASS_VALUE);
        Probe::check($signature->parameter_passing(1) === \type_model\PASS_VALUE);
        Probe::check($signature->result_production() === \type_model\RESULT_VALUE);
        $passing /** vector<int> */ = [\type_model\PASS_VALUE,\type_model\PASS_BORROW_CONST,\type_model\PASS_BORROW_MUTABLE,\type_model\PASS_BYTE_SPAN];
        $explicit = \type_model\Representation::signature(1, 0, 4, $passing, \type_model\RESULT_OWNED);
        $passing[1] = \type_model\PASS_VALUE;
        Probe::check($explicit->parameter_passing(1) === \type_model\PASS_BORROW_CONST);
        Probe::check($explicit->parameter_passing(2) === \type_model\PASS_BORROW_MUTABLE);
        Probe::check($explicit->parameter_passing(3) === \type_model\PASS_BYTE_SPAN);
        Probe::check($explicit->result_production() === \type_model\RESULT_OWNED);
        $opaque = \type_model\Representation::opaque(32, 16);
        Probe::check($opaque->kind() === 7);
        Probe::check($opaque->opaque_size() === 32);
        Probe::check($opaque->opaque_alignment() === 16);
        $names /** vector<string> */ = ['value','borrow_const','borrow_mutable','byte_span'];
        $results /** vector<string> */ = ['none','value','owned','dependent_value'];
        for ($i /** int */ = 0; $i < 4; ++$i) {
            Probe::check(\type_model\Semantic_Modes::passing($names[$i]) === $i);
            Probe::check(\type_model\Semantic_Modes::passing_name($i) === $names[$i]);
            Probe::check(\type_model\Semantic_Modes::result($results[$i]) === $i);
            Probe::check(\type_model\Semantic_Modes::result_name($i) === $results[$i]);
        }
        Probe::check(!\type_model\Semantic_Modes::is_borrow(\type_model\PASS_VALUE));
        Probe::check(\type_model\Semantic_Modes::is_borrow(\type_model\PASS_BORROW_CONST));
        Probe::check(\type_model\Semantic_Modes::is_borrow(\type_model\PASS_BORROW_MUTABLE));
        Probe::check(!\type_model\Semantic_Modes::is_borrow(\type_model\PASS_BYTE_SPAN));
        $context = new \type_model\Type_Context('config:1','provider:2','target:3');
        Probe::check($context->configuration_key === 'config:1');
        Probe::check($context->provider_key === 'provider:2');
        Probe::check($context->target_key === 'target:3');
        $first = new \type_model\Type_Lineage(); $second = new \type_model\Type_Lineage(); $shared = $first;
        Probe::check($first !== $second);
        Probe::check($first === $shared);
        $member = new \type_model\Type_Member(4, 'field', false);
        Probe::check($member->type_id === 4);
        Probe::check($member->name === 'field');
        Probe::check(!$member->writable);
        for ($i /** int */ = 0; $i < 25; ++$i) { Probe::check(Probe::invalid($i)); }
        $wrong = false;
        try { $void->bit_width(); } catch (\LogicException $error) { $wrong = true; }
        Probe::check($wrong);
        $wrong = false;
        try { $integer->signature_return(); } catch (\LogicException $error) { $wrong = true; }
        Probe::check($wrong);
    }
}
