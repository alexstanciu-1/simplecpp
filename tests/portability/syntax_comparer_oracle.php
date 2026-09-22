<?php
declare(strict_types=1);
require __DIR__ . '/../../compiler/bootstrap.php';
require __DIR__ . '/oracles/syntax_comparer_original.php';
function observation(string $class, parse\File_Frontend $left, int $a, parse\File_Frontend $right, int $b): array {
    try { return ['value', $class::equal($left, $a, $right, $b)]; }
    catch (LogicException $error) { return ['error', $error->getMessage()]; }
}
mt_srand(20260922);
$kinds = parse\syntax_kind::cases();
for ($trial = 0; $trial < 1500; ++$trial) {
    $tree = new parse\Syntax_Tree();
    $copy = new parse\Syntax_Tree();
    $size = mt_rand(1, 25);
    for ($i = 1; $i <= $size; ++$i) {
        $node = new parse\syntax_node(); $node->kind = $kinds[mt_rand(0, count($kinds) - 1)];
        $node->start = mt_rand(0, 8); $node->length = mt_rand(0, 3);
        $node->first_child_id = $i < $size && mt_rand(0, 2) === 0 ? mt_rand($i + 1, $size) : 0;
        $node->next_sibling_id = $i < $size && mt_rand(0, 2) === 0 ? mt_rand($i + 1, $size) : 0;
        $tree->nodes[] = $node; $copy->nodes[] = clone $node;
    }
    if ($trial % 2 === 0) { $copy->nodes[mt_rand(0, $size - 1)]->kind = $kinds[mt_rand(0, count($kinds) - 1)]; }
    $left = new parse\File_Frontend(new tokenize\Token_Buffer(new read_sources\Source_Buffer(1, 'left', 0, 'abc def ghi')), $tree);
    $right = new parse\File_Frontend(new tokenize\Token_Buffer(new read_sources\Source_Buffer(2, 'right', 0, 'abc def ghi')), $copy);
    $a = mt_rand(0, $size + 1); $b = $trial % 3 === 0 ? mt_rand(0, $size + 1) : $a;
    $before = serialize([$left, $right]);
    $expected = observation(parse\Baseline_Syntax_Comparer::class, $left, $a, $right, $b);
    $actual = observation(parse\Syntax_Comparer::class, $left, $a, $right, $b);
    if ($actual !== $expected || serialize([$left, $right]) !== $before) { throw new RuntimeException('Comparison mismatch/mutation: ' . $trial); }
}
echo "1500 syntax comparisons match frozen oracle without input mutation\n";
