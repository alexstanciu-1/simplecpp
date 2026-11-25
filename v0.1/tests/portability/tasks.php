<?php
require_once __DIR__ . '/../../tools/php_portability/runtime/bootstrap.php';
require_once __DIR__ . '/../../tools/php_portability/src/converter.php';
$events = [];
$count = task_run_publish_unordered([1, 2, 3], 2,
    function (int $item) use (&$events): int { $events[] = 'work:' . $item; return $item * 2; },
    function (int $value) use (&$events): void { $events[] = 'publish:' . $value; });
if ($count !== 3 || $events !== ['work:1', 'publish:2', 'work:2', 'publish:4', 'work:3', 'publish:6']) {
    throw new RuntimeException('Sequential task facade changed callback ordering');
}
foreach (['work', 'publish'] as $stage) {
    $failure = new RuntimeException($stage);
    try {
        task_run_publish_unordered([1], 2,
            function (int $item) use ($stage, $failure): int { if ($stage === 'work') { throw $failure; } return $item; },
            function (int $item) use ($stage, $failure): void { if ($stage === 'publish') { throw $failure; } });
        throw new LogicException('Missing task failure');
    } catch (RuntimeException $error) { if ($error !== $failure) { throw $error; } }
}
try { task_run_publish_unordered([], 0, fn($x) => $x, fn($x) => $x); throw new LogicException('Invalid workers accepted'); }
catch (RuntimeException $expected) {}
$converter = new scpp\portability\Converter(require __DIR__ . '/../../tools/php_portability/function_map.php');
$out = $converter->convert('<?php $items /** vector<int> */ = [1]; $count = task_run_publish_unordered($items, 2, function (int $item): int { return $item; }, function (int $item): bool { return true; });', 'tasks.php');
if (!str_contains($out, 'task_run_publish_unordered(')) { throw new RuntimeException('Task binding missing'); }
echo "Tasks PHP facade: ordering, error identity, bounds and conversion passed\n";
