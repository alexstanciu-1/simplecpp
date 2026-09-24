<?php
declare(strict_types=1);
require_once __DIR__ . '/../../generators/php/bin/bootstrap.php';

use Scpp\S2S\Analysis\FrontEndSymbolExtractor;

// Public source-to-summary path, including negative cases that must retain missing_return.
$cases = [
    'throw_only' => [true, 'throw new Exception("bad");'],
    'return_or_throw' => [true, 'if ($x > 0) { return 1; } throw new Exception("bad");'],
    'exhaustive_if' => [true, 'if ($x > 0) { return 1; } else { throw new Exception("bad"); }'],
    'partial_if' => [false, 'if ($x > 0) { return 1; }'],
    'exhaustive_switch' => [true, 'switch ($x) { case 0: return 1; default: throw new Exception("bad"); }'],
    'grouped_cases' => [true, 'switch ($x) { case 0: case 1: return 2; default: throw new Exception("bad"); }'],
    'fallthrough_default' => [true, 'switch ($x) { default: $x = 1; case 0: return 2; }'],
    'missing_default' => [false, 'switch ($x) { case 0: return 1; }'],
    'break_path' => [false, 'switch ($x) { case 0: break; default: return 1; }'],
    'unreachable_return' => [false, 'switch ($x) { case 0: break; return 2; default: return 1; }'],
    'conditional_break' => [false, 'switch ($x) { case 0: if ($x > 1) { break; } return 2; default: return 1; }'],
    'return_after_switch' => [true, 'switch ($x) { case 0: break; default: return 1; } return 2;'],
    'loop_may_not_run' => [false, 'while ($x > 0) { return 1; }'],
    'loop_then_throw' => [true, 'while ($x > 0) { break; } throw new Exception("bad");'],
    'nested_switch_break' => [false, 'switch ($x) { case 0: switch ($x) { default: break 2; } return 1; default: return 2; }'],
    'try_break' => [false, 'switch ($x) { case 0: try { break; } finally { $x = 2; } return 1; default: return 2; }'],
];
$extractor = new FrontEndSymbolExtractor();
foreach ($cases as $name => [$expected, $body]) {
    $source = 'function probe(int $x): int { ' . $body . ' }';
    $file = $extractor->extract('/tmp/scpp-return-path-' . $name . '.phs', $source);
    $summary = $extractor->summarize($file, $source);
    $actual = $summary['root_functions'][0]['returns_on_all_paths'];
    if ($actual !== $expected) { throw new RuntimeException($name . ': unexpected completion analysis'); }
}
echo count($cases), " return-path cases passed\n";
