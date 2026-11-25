<?php
declare(strict_types=1);

/*
 * Role: Accept exactly one private result per fixed ownership task.
 * Used by: Ownership_Preparation after each ready batch
 * Call map: join() -> validate_summary(); [action] retain equal summary meaning
 */
namespace analyze_lifetimes;

final class Ownership_Join implements \compile\Join
{
    public function __construct(private readonly array $tasks, private readonly array $previous = [])
    {
    }

    /** Accept arbitrary completion order; stable meaning can retain identity despite a producer edit. */
    public function join(array $results): array
    {
        $selected = [];
        foreach ($this->tasks as $task) {
            if (isset($selected[$task->key])) {
                throw new \LogicException('Duplicate ownership task');
            }
            $selected[$task->key] = $task;
        }
        $accepted = [];
        foreach ($results as $result)
        {
            $key = $result->task->key;
            if ((($selected[$key] ?? null) !== $result->task) || isset($accepted[$key])) {
                throw new \LogicException('Unexpected, duplicate or stale ownership result');
            }
            $body = $result->task->subject instanceof \check_bodies\Checked_Body ? $result->task->subject : null;
            if (($result->allocations !== null) && ($result->allocations->body !== $body)) {
                throw new \LogicException('Ownership allocation provenance mismatch');
            }
            $summary = $result->summary;
            $expected = $body === null ? [0 => $result->task->subject->definition->resource_paths]
                : Resource_Locations::parameters($body);
            if (($body === null) && $result->task->subject->kind->has_source()) {
                $expected[1] = $expected[0];
            }
            self::validate_summary($summary, $expected, $body,
                ($body === null) && ($result->task->subject->kind === \type_model\lifecycle_operation_kind::move_construct));

            // Results carry fixed owned field states, independently of borrowed parameter transitions.
            $paths = $body === null ? [] : $body->definition_for($body->signature_for($body->callable_id)->return_type)->resource_paths;
            $names = array_map(Resource_Locations::path_key(...), $paths);
            $actual = array_map('strval', array_keys($summary->result));
            sort($names);
            sort($actual);
            if ($names !== $actual) {
                throw new \LogicException('Incomplete owned result resource summary');
            }
            foreach ($summary->result as $state) {
                if (!in_array($state, [Resource_States::EMPTY_VALUE, Resource_States::OWNED_VALUE], true)) {
                    throw new \LogicException('Invalid owned result resource state');
                }
            }

            // Equal meaning retains identity even after a producer body replacement.
            $old = $this->previous[$key] ?? null;
            if (($old !== null) && ($old->summary == $summary)) {
                $summary = $old->summary;
            }
            $accepted[$key] = $summary === $result->summary ? $result : new ownership_result($result->task, $summary, $result->allocations);
        }
        if (count($accepted) !== count($selected)) {
            throw new \LogicException('Incomplete ownership batch');
        }
        return $accepted;
    }

    /** Check complete parameter/path coverage and preserve const source identity at the acceptance boundary. */
    private static function validate_summary(ownership_summary $summary, array $expected, ?\check_bodies\Checked_Body $body, bool $moving): void
    {
        $positions = array_keys($summary->parameters);
        sort($positions);
        if ($positions !== array_keys($expected)) {
            throw new \LogicException('Incomplete ownership summary parameters');
        }
        foreach ($summary->distinct as $key => $pair)
        {
            if (!array_is_list($pair) || (count($pair) !== 2) || !is_string($pair[0]) || !is_string($pair[1])
                || (strcmp($pair[0], $pair[1]) >= 0) || ($key !== Resource_Locations::distinct_key($pair))) {
                throw new \LogicException('Invalid ownership alias restriction');
            }
            foreach ($pair as $endpoint) {
                [$position, $path] = Resource_Locations::parameter_parts($endpoint);
                if (!isset($summary->parameters[$position][$path])) {
                    throw new \LogicException('Unknown ownership alias endpoint');
                }
            }
        }
        foreach ($expected as $position => $paths)
        {
            $fields = $summary->parameters[$position];
            $names = array_map(Resource_Locations::path_key(...), $paths);
            $actual = array_map('strval', array_keys($fields));
            sort($names);
            sort($actual);
            if ($names !== $actual) {
                throw new \LogicException('Incomplete ownership summary fields');
            }
            $const = $body === null ? (($position === 1) && (!$moving)) : $body->local_passing($position + 1) === \type_model\argument_passing::borrow_const;
            foreach ($fields as $transition) {
                if (!$transition instanceof resource_transition
                    || ((!$transition->accessed) && (($transition->mutates) || ($transition->required !== Resource_States::EITHER) || ($transition->result !== Resource_States::IDENTITY)))
                    || (($const) && (($transition->mutates) || ($transition->result !== Resource_States::IDENTITY)))) {
                    throw new \LogicException('Invalid ownership field transition');
                }
            }
        }
    }
}
