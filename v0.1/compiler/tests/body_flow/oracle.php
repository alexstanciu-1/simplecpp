<?php
$reference = dirname(__DIR__, 2) . '/reference/pre-rewrite/src/04_analyze/check_bodies';
require $reference . '/data/structures.php';
require $reference . '/flow.php';
require $reference . '/utilities/flow_graph.php';
$cases = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
$kinds = [1 => \check_bodies\flow_end::jump, 2 => \check_bodies\flow_end::branch,
    3 => \check_bodies\flow_end::return_exit, 4 => \check_bodies\flow_end::fallthrough];
foreach ($cases as $case) {
    $blocks = [];
    foreach ($case['rows'] as $row) {
        $blocks[] = new \check_bodies\typed_block($row[0], $row[1], $row[2], $kinds[$row[3]], $row[4], $row[5]);
    }
    $failed = false; $ids = [];
    try { $ids = \check_bodies\Flow_Graph::reachable($blocks); }
    catch (\LogicException $error) { $failed = true; }
    if ($failed !== $case['error'] || $ids !== $case['expected']) { throw new \LogicException('Prototype graph differs'); }
}
$builder = new \check_bodies\Flow_Builder();
$a = $builder->reserve(); $b = $builder->reserve();
$builder->begin($a, 0, 1); $builder->ensure(999, 9);
$builder->terminate(2, \check_bodies\flow_end::jump, $b);
$builder->terminate(90, \check_bodies\flow_end::return_exit);
$builder->begin($b, 2, 3); $rows = $builder->complete(5);
if (count($rows) !== 2 || $rows[0]->statement_count !== 2 || $rows[1]->statement_count !== 3 || $rows[1]->scope_id !== 3) {
    throw new \LogicException('Prototype builder differs');
}
echo count($cases), " retained graph cases and builder trace passed\n";
