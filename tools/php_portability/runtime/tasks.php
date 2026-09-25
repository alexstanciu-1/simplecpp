<?php
namespace scpp;

/** Sequential PHP carrier; native executes work concurrently and serializes publication. */
function task_run_publish_unordered(array $items, int $workers, callable $work, callable $publish): int {
    if ($workers < 1) { throw new \RuntimeException('task_run_publish_unordered(): workers must be positive'); }
    if (!array_is_list($items)) { throw new \LogicException('Tasks require a packed vector'); }
    $published = 0;
    foreach ($items as $item) {
        $result = $work($item);
        $publish($result);
        ++$published;
    }
    return $published;
}
