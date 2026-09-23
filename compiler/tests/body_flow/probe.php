<?php
declare(strict_types=1);
namespace body_flow_test;
final class Probe {
    public static function builder(): void {
        $builder = new \check_bodies\Flow_Builder();
        $first = $builder->reserve(); $second = $builder->reserve();
        $builder->begin($first, 0, 1);
        $builder->ensure(999, 9);
        $builder->terminate(2, \check_bodies\FLOW_JUMP, $second, 0);
        $builder->terminate(90, \check_bodies\FLOW_RETURN, 0, 0);
        $builder->begin($second, 2, 3);
        $rows = $builder->complete(5);
        if (q_count($rows) !== 2) { throw new \LogicException('Bad builder count'); }
        if (($rows[0]->statement_start !== 0) || ($rows[0]->statement_count !== 2) || ($rows[0]->scope_id !== 1)) { throw new \LogicException('Bad first range'); }
        if (($rows[1]->statement_count !== 3) || ($rows[1]->scope_id !== 3) || ($rows[1]->end !== \check_bodies\FLOW_FALLTHROUGH)) { throw new \LogicException('Bad completed range'); }
        $failed = false;
        try { $builder->begin(1, 0, 1); } catch (\LogicException $error) { $failed = true; }
        if (!$failed) { throw new \LogicException('Reopened completed block'); }
        $builder->ensure(5, 1);
        $new_rows = $builder->complete(5);
        if ((q_count($rows) !== 2) || (q_count($new_rows) !== 3)) { throw new \LogicException('Published membership changed'); }
        $incomplete = new \check_bodies\Flow_Builder(); $incomplete->reserve();
        $failed = false;
        try { $unused = $incomplete->complete(0); } catch (\LogicException $error) { $failed = true; }
        if (!$failed) { throw new \LogicException('Accepted incomplete flow'); }
        $failed = false;
        try { $incomplete->begin(0, 0, 1); } catch (\LogicException $error) { $failed = true; }
        if (!$failed) { throw new \LogicException('Accepted missing reservation'); }
    }
    public static function run(string $text): void {
        \body_flow_test\Probe::builder();
        $cases = json_read($text);
        for ($i = 0; $i < $cases->size(); $i++) {
            $case_data = $cases->at($i); $rows = $case_data->member('rows');
            $blocks /** vector<\check_bodies\Typed_Block> */ = [];
            for ($j = 0; $j < $rows->size(); $j++) {
                $row = $rows->at($j);
                $blocks[] = new \check_bodies\Typed_Block($row->at(0)->integer(), $row->at(1)->integer(), $row->at(2)->integer(), $row->at(3)->integer(), $row->at(4)->integer(), $row->at(5)->integer());
            }
            $failed = false; $ids /** vector<int> */ = [];
            try { $ids = \check_bodies\Flow_Graph::reachable($blocks); }
            catch (\LogicException $error) { $failed = true; }
            $expected = $case_data->member('expected');
            $valid = $failed === $case_data->member('error')->boolean();
            if (q_count($ids) !== $expected->size()) { $valid = false; }
            else { for ($j = 0; $j < q_count($ids); $j++) { if ($ids[$j] !== $expected->at($j)->integer()) { $valid = false; } } }
            echo $valid ? "true\n" : "false\n";
        }
    }
}
