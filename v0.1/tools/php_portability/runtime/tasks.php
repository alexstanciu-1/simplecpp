<?php
namespace scpp;

/** PHP has sequential workers; track the same callback boundary as the native batch. */
final class Task_Publication_Context
{
    public static bool $active = false;
}

/** Serialize a synchronous worker-side publication without exposing a lock handle. */
function task_synchronize(callable $callback): void
{
    if (!Task_Publication_Context::$active) {
        throw new \LogicException('task_synchronize requires an unordered work callback; nesting is unsupported');
    }
    Task_Publication_Context::$active = false;
    try {
        $callback();
    } finally {
        Task_Publication_Context::$active = true;
    }
}

/** Sequential PHP carrier; native executes work concurrently and serializes publication. */
function task_run_publish_unordered(array $items, int $workers, callable $work, callable $publish): int
{
    if ($workers < 1) { throw new \RuntimeException('task_run_publish_unordered(): workers must be positive'); }
    if (!array_is_list($items)) { throw new \LogicException('Tasks require a packed vector'); }
    $published = 0;
    $previous = Task_Publication_Context::$active;
    try {
        foreach ($items as $item) {
            Task_Publication_Context::$active = true;
            $result = $work($item);
            Task_Publication_Context::$active = false;
            $publish($result);
            ++$published;
        }
    } finally {
        Task_Publication_Context::$active = $previous;
    }
    return $published;
}
