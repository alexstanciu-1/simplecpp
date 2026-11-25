<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/body_support.php';

use Body_Test_Stages as Check;
use analyze_lifetimes\Resource_Locations as Locations;
use analyze_lifetimes\Resource_States as States;
use analyze_lifetimes\resource_location;

/** Independent finite-relation oracle; never calls the production composition helpers. */
final class Resource_Contract_Test
{
    private const EDGES = [[0, 0], [0, 1], [1, 0], [1, 1]];

    /** Decode the four-bit format into a set of input/output edges. */
    public static function relation(int $bits): array
    {
        $edges = [];
        foreach (self::EDGES as $index => $edge) {
            if (($bits & (1 << $index)) !== 0) {
                $edges[] = $edge;
            }
        }
        return $edges;
    }

    /** Relational composition follows matching middle states, including nondeterministic and absent outputs. */
    public static function composed(array $first, array $second): int
    {
        $edges = [];
        foreach ($first as [$input, $middle]) {
            foreach ($second as [$next, $output]) {
                if ($middle === $next) {
                    $edges[] = [$input, $output];
                }
            }
        }
        $bits = 0;
        foreach (self::EDGES as $index => $edge) {
            if (in_array($edge, $edges, true)) {
                $bits |= 1 << $index;
            }
        }
        return $bits;
    }

    /** Accept an input only when it has outputs and every output belongs to the allowed set. */
    public static function compatible(array $edges, int $required, bool $deterministic = false): int
    {
        $accepted = 0;
        foreach ([0, 1] as $input)
        {
            $outputs = [];
            foreach ($edges as [$from, $to]) {
                if ($from === $input) {
                    $outputs[] = $to;
                }
            }
            $allowed = array_filter($outputs, static fn(int $output): bool => ($required & (1 << $output)) !== 0);
            if (($outputs !== []) && ($allowed === $outputs) && ((!$deterministic) || (count($outputs) === 1))) {
                $accepted |= 1 << $input;
            }
        }
        return $accepted;
    }
}

// Every relation, including unreachable and branch-merged lanes, has an independent interpretation.
for ($left = 0; $left < 16; ++$left)
{
    $relation = Resource_Contract_Test::relation($left);
    for ($right = 0; $right < 16; ++$right) {
        Check::check(States::compose($left, $right) === Resource_Contract_Test::composed($relation, Resource_Contract_Test::relation($right)),
            'Compact composition agrees with finite relation composition');
    }
    foreach ([States::EMPTY, States::OWNED, States::EITHER] as $required) {
        Check::check(States::compatible($left, $required) === Resource_Contract_Test::compatible($relation, $required),
            'Required-state checking accepts all and only compatible input states');
    }
    Check::check(States::deterministic($left) === Resource_Contract_Test::compatible($relation, States::EITHER, true),
        'Determinism rejects missing or multiple outcomes independently for each input');
    Check::check((States::compose($left, States::IDENTITY) === $left) && (States::compose(States::IDENTITY, $left) === $left),
        'Identity preserves all relations on both sides');
}
Check::check(Resource_Contract_Test::relation(States::EMPTY_VALUE) === [[0, 0], [1, 0]], 'Empty result ignores the incoming state');
Check::check(Resource_Contract_Test::relation(States::OWNED_VALUE) === [[0, 1], [1, 1]], 'Owned result ignores the incoming state');

// Exact scoped keys distinguish roots, fields and parameter positions without hashes or ambiguous components.
foreach ([[], [0], [1, 23], [12, 3]] as $path)
{
    $key = Locations::path_key($path);
    $endpoint = Locations::parameter_key(2, $key);
    Check::check(Locations::parameter_parts($endpoint) === [2, $key], 'Canonical endpoint round trip');
    $base = new resource_location(4, [3]);
    $projected = Locations::project($base, $key);
    Check::check($projected->path === [3, ...$path], 'Project a summary leaf under caller storage');
    Check::check(Locations::path_key($projected->path) === Locations::prefix('3', $key), 'Lifecycle and call projection use the same path contract');
    Check::check($base->path === [3], 'Projection leaves its fixed input unchanged');
}
Check::check(Locations::parameter_key(1, '23') !== Locations::parameter_key(12, '3'), 'Parameter boundaries remain unambiguous');
Check::check(Locations::path_key([1, 23]) !== Locations::path_key([12, 3]), 'Field boundaries remain unambiguous');
Check::check(Locations::overlaps(new resource_location(1, [2]), new resource_location(1, [2, 3])), 'An ancestor overlaps its nested leaf');
Check::check(!Locations::overlaps(new resource_location(1, [2]), new resource_location(1, [3])), 'Sibling fields are distinct');
Check::check(!Locations::overlaps(new resource_location(1, [2]), new resource_location(2, [2])), 'Different roots remain distinct locations');
$pair = Locations::distinct_pair('1:0', '0:1');
Check::check($pair === Locations::distinct_pair('0:1', '1:0'), 'Alias restrictions are unordered pairs');
Check::check(Locations::distinct_key($pair) === '0:1|1:0', 'Canonical pair keys preserve both complete endpoints');
foreach (['-1:0', '01:0', '0:01', '0:.1', '0:1.', '0:1..2', '0:missing', '0:1|1:0', '0:0:1', '999999999999999999999999999999:0'] as $invalid) {
    Check::rejects(static fn() => Locations::parameter_parts($invalid), 'ownership alias endpoint');
}

echo "Resource contracts: relation algebra, canonical paths, projection and alias endpoints passed\n";
