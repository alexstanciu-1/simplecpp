<?php
// Host-only broad boundary matrix generated with Python arbitrary-precision integers.
$cases=json_decode(file_get_contents('inputs/ranges.json'),true,512,JSON_THROW_ON_ERROR);
foreach ($cases as $case) {
    $type=\context_test\Probe::integer($case['bits'],$case['signed']);
    $actual=null;
    try { $actual=\check_bodies\Integer_Literals::resolve($case['digits'],$type); }
    catch (RangeException $e) { if ($case['expected']!==null) { throw $e; } }
    if ($actual!==$case['expected']) { throw new RuntimeException('Exact integer range disagreement'); }
}
echo json_encode(['exact_range_cases'=>count($cases)]),"\n";
