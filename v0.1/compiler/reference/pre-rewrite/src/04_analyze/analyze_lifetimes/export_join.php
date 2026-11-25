<?php
declare(strict_types=1);

/*
 * Role: Accept selected export checks and retain unchanged verification evidence.
 * Call map: prepare_backend\Export_Verification -> Export_Join::join()
 * Output: complete current evidence indexed by exact operation link name.
 */
namespace analyze_lifetimes;

final class Export_Join implements \compile\Join
{
    /** Current inputs are captured before selection; previous evidence is shared read-only. */
    public function __construct(private readonly array $current, private readonly array $selected,
        private readonly array $previous)
    {
    }

    /** Accept arbitrary completion order; reject missing, duplicate and stale private outputs. */
    public function join(array $results): array
    {
        foreach ($this->selected as $key => $task) {
            if (($this->current[$key] ?? null) !== $task) {
                throw new \LogicException('Stale selected source export check');
            }
        }

        $accepted = [];
        foreach ($results as $result) {
            $key = $result->task->operation->link_name;
            if ((($this->selected[$key] ?? null) !== $result->task) || isset($accepted[$key])) {
                throw new \LogicException('Unexpected, duplicate or stale source export verification');
            }
            $accepted[$key] = $result;
        }
        if (count($accepted) !== count($this->selected)) {
            throw new \LogicException('Incomplete source export verification');
        }

        // Unselected work must still be current; removed declarations are not retained.
        foreach ($this->current as $key => $task) {
            $result = $accepted[$key] ?? $this->previous[$key] ?? null;
            if (!self::current($result, $task)) {
                throw new \LogicException('Missing or stale source export verification');
            }
            $accepted[$key] = $result;
        }
        ksort($accepted);
        return $accepted;
    }

    /** Compare exact analyzed inputs; equal signatures never authorize reuse after a body change. */
    public static function current(?export_verification $result, export_task $task): bool
    {
        return ($result !== null) && ($result->task->operation === $task->operation)
            && ($result->task->definition === $task->definition) && ($result->task->bodies === $task->bodies)
            && ($result->task->ownership === $task->ownership);
    }
}
