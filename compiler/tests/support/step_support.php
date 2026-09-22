<?php
declare(strict_types=1);

// Test-only access to selection algorithms: worker/join proofs use the real tasks
// without adding a second public scheduling API to production owners.
class Step_Test
{
    public static function select(string $class, mixed ...$inputs): array
    {
        return (new ReflectionMethod($class, 'select'))->invoke(null, ...$inputs);
    }

    // Exercise the common contract wherever a fixture executes a complete phase.
    public static function run(\compile\Step $step): \compile\Step_Result
    {
        self::state($step, \compile\step_status::created, ['run', 'finalize', 'result', 'store']);
        $step->init();
        self::state($step, \compile\step_status::ready, ['init', 'finalize', 'result', 'store']);
        $step->run();
        self::state($step, \compile\step_status::processed, ['init', 'run', 'result', 'store']);
        $step->finalize();
        self::state($step, \compile\step_status::finished, ['init', 'run', 'finalize']);
        $result = $step->result();
        self::check($result === $step->result(), 'Stable concrete result identity');
        if ($step instanceof \compile\Store_Providing_Step) {
            self::check($step->store() === $step->store(), 'Stable concrete store identity');
        }
        return $result;
    }

    public static function state(\compile\Step $step, \compile\step_status $expected, array $invalid): void
    {
        self::check($step->status() === $expected, get_class($step) . ': expected ' . $expected->name);
        self::check($step->supports_run() === ($step instanceof \compile\Runnable_Step), 'Capability matches interface');
        foreach ($invalid as $operation)
        {
            if (($operation === 'store') && (!($step instanceof \compile\Store_Providing_Step))) {
                continue;
            }
            $rejected = false;
            try {
                $step->$operation();
            }
            catch (LogicException $error) {
                $rejected = str_contains($error->getMessage(), $operation)
                    && str_contains($error->getMessage(), $expected->name);
            }
            self::check(($rejected) && ($step->status() === $expected), 'Reject ' . $operation . ' without changing ' . $expected->name);
        }
    }

    public static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }
}
