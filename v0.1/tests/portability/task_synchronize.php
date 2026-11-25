<?php
require_once __DIR__ . '/../../tools/php_portability/runtime/bootstrap.php';

/** Missing context and nested synchronization must reject instead of silently running unlocked. */
function synchronization_rejects(callable $call): void
{
    try {
        $call();
    } catch (LogicException $expected) {
        return;
    }
    throw new RuntimeException('Missing task synchronization boundary check');
}

synchronization_rejects(function (): void { task_synchronize(function (): void {}); });
$events = new ArrayObject();
task_run_publish_unordered([1, 2], 2,
    function (int $item) use ($events): int {
        task_synchronize(function () use ($events, $item): void {
            $events[] = $item;
            synchronization_rejects(function (): void { task_synchronize(function (): void {}); });
        });
        try {
            task_synchronize(function (): void { throw new RuntimeException('callback'); });
        } catch (RuntimeException $expected) {}
        // Throwing callbacks release the lock/context and a later synchronization can proceed.
        task_synchronize(function () use ($events): void { $events[] = 'recovered'; });
        return $item;
    },
    function (int $item): void {
        synchronization_rejects(function (): void { task_synchronize(function (): void {}); });
    });
if ($events->getArrayCopy() !== [1, 'recovered', 2, 'recovered']) {
    throw new RuntimeException('Synchronized callback sequencing changed');
}
synchronization_rejects(function (): void { task_synchronize(function (): void {}); });
echo "Task synchronization PHP: callback execution, context boundaries and exception cleanup passed\n";
