<?php
declare(strict_types=1);
namespace body_operations_test;
final class Probe {
    public static function run(string $text): void {
        $catalog = \load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
        $types = \type_model\Type_Store::fresh(new \type_model\Type_Context('config','providers','target'));
        for ($di = 0; $di < $catalog->size(); $di++) { \resolve_types\Type_Cache::materialize($types, $catalog->definition_at($di)); }
        $types_before = $types->type_count(); $shapes_before = $types->representation_count();
        $cases = json_read($text);
        for ($i = 0; $i < $cases->size(); $i++) {
            $row = $cases->at($i);
            $left = $types->find_type($row->member('left')->text(), '');
            $right = $types->find_type($row->member('right')->text(), '');
            $boolean = 0;
            if ($row->member('boolean')->boolean()) { $boolean = $types->find_type('bool', ''); }
            $operation = $row->member('operation')->text();
            $contract = \check_bodies\Operation_Resolver::binary($types, $operation, $left, $right, $boolean);
            $entry = $row->member('entry')->text(); $valid = true;
            if ($entry === '') { $valid = $contract === null; }
            else {
                if ($contract === null) { $valid = false; }
                else {
                    $result = $left;
                    if ($operation === 'less_than') { $result = $boolean; }
                    if (($contract->operation !== $operation) || ($contract->result_type !== $result) || ($contract->size() !== 2)) { $valid = false; }
                    if (($contract->operand_at(0) !== $left) || ($contract->operand_at(1) !== $right)) { $valid = false; }
                    if (($contract->implementation->provider !== 'compiler.integer') || ($contract->implementation->entry !== $entry) || ($contract->implementation->kind !== \type_model\IMPLEMENTATION_NATIVE_OPERATION)) { $valid = false; }
                }
            }
            if (($types->type_count() !== $types_before) || ($types->representation_count() !== $shapes_before)) { $valid = false; }
            echo $valid ? "true\n" : "false\n";
        }
        $binding = new \type_model\Implementation_Binding(\type_model\IMPLEMENTATION_CALLABLE, 'provider', 'call');
        $operands /** vector<int> */ = [1, 2];
        $contract = new \type_model\Operation_Contract('fixture', $operands, 3, $binding);
        $operands[0] = 99; $operands[] = 4;
        if (($contract->size() !== 2) || ($contract->operand_at(0) !== 1) || ($contract->implementation !== $binding)) { throw new \LogicException('Operation membership changed'); }
        $failed = false;
        try { $unused = $contract->operand_at(2); } catch (\OutOfBoundsException $error) { $failed = true; }
        if (!$failed) { throw new \LogicException('Missing operand accepted'); }
        $failed = false;
        $invalid /** vector<int> */ = [0];
        try { $bad = new \type_model\Operation_Contract('fixture', $invalid, 1, $binding); } catch (\InvalidArgumentException $error) { $failed = true; }
        if (!$failed) { throw new \LogicException('Invalid operand accepted'); }
    }
}
