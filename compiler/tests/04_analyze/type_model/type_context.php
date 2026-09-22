<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

use compile\Update_Context;
use type_model\type_context;
use resolve_types\Type_Resolver;
use type_model\Type_Store;

class Type_Context_Test
{
    public static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public static function rejects(callable $operation, string $expected): void
    {
        try {
            $operation();
        }
        catch (Throwable $error) {
            self::check($error instanceof $expected, 'Unexpected error: ' . $error);
            return;
        }
        throw new Exception('Expected rejection: ' . $expected);
    }
}

$context = new type_context('configuration-a', 'provider-a', 'target-a');
$update = new Update_Context();
$update->full_rebuild = \resolve_types\Type_Cache::requires_rebuild(null, $context);
$types = \resolve_types\Type_Cache::prepare(null, $context, $update->full_rebuild);
Type_Context_Test::check(($update->full_rebuild) && ($types->context === $context),
    'Coordinator selects full work from the cache requirement, then prepares an explicit context');

// A reference reserves identity without claiming a declaration. The first
// authoritative declaration completes that identity; subsequent ones fail.
$pending = $types->reference_type('Word', 'fixture');
$pending_row = $types->type_by_id($pending);
$representation = $types->intern_integer(32);
Type_Context_Test::check((!$types->is_declared($pending)) && $types->needs_representation($pending),
    'A pending name has neither a declaration nor a representation');
$before = $types->to_json();
Type_Context_Test::rejects(static fn() => $types->set_representation($pending, $representation), LogicException::class);
Type_Context_Test::check($types->to_json() === $before, 'An undeclared name cannot be completed through the representation setter');

$candidate = clone $types;
Type_Context_Test::check(($candidate->declare_type('Word', 'fixture') === $pending)
    && $candidate->is_declared($pending) && $candidate->needs_representation($pending)
    && ($types->type_by_id($pending) === $pending_row) && (!$pending_row->declared),
    'Declaration after reference preserves ID and previous snapshot');
$before = $candidate->to_json();
Type_Context_Test::rejects(static fn() => $candidate->declare_type('Word', 'fixture'), InvalidArgumentException::class);
Type_Context_Test::check($candidate->to_json() === $before, 'Duplicate declaration must fail even before representation completion');
$candidate->set_representation($pending, $representation);
Type_Context_Test::check((!$candidate->needs_representation($pending)) && $candidate->is_declared($pending),
    'Representation completion does not subsume declaration state');
$accepted = $candidate;

// A different context object with equal input keys permits reuse. The store
// remains a separate writable candidate and unchanged immutable rows are shared.
$same = new type_context('configuration-a', 'provider-a', 'target-a');
$update = new Update_Context();
Type_Context_Test::check(!\resolve_types\Type_Cache::requires_rebuild($accepted, $same), 'Equal keys require no cache rebuild');
$candidate = \resolve_types\Type_Cache::prepare($accepted, $same, $update->full_rebuild);
Type_Context_Test::check((!$update->full_rebuild) && ($candidate !== $accepted)
    && ($candidate->type_by_id($pending) === $accepted->type_by_id($pending))
    && ($candidate->representation_for_type($pending) === $accepted->representation_for_type($pending)),
    'Equal context keys preserve warm rows without sharing the writable owner');
$candidate->invalidate_definition($pending);
Type_Context_Test::check($candidate->is_declared($pending) && $candidate->needs_representation($pending)
    && (!$accepted->needs_representation($pending)), 'Invalidation affects representation only in the candidate');
Type_Context_Test::rejects(static fn() => $candidate->declare_type('Word', 'fixture'), InvalidArgumentException::class);

// Each context component independently invalidates all cached definitions.
// New caches have a new ID lineage and no old declaration or representation rows.
$before = $accepted->to_json();
foreach ([
        new type_context('configuration-b', 'provider-a', 'target-a'),
        new type_context('configuration-a', 'provider-b', 'target-a'),
        new type_context('configuration-a', 'provider-a', 'target-b'),
    ] as $changed)
{
    $update = new Update_Context();
    Type_Context_Test::rejects(static fn() => \resolve_types\Type_Cache::prepare($accepted, $changed, false), LogicException::class);
    $update->full_rebuild = \resolve_types\Type_Cache::requires_rebuild($accepted, $changed);
    $candidate = \resolve_types\Type_Cache::prepare($accepted, $changed, $update->full_rebuild);
    $export = json_decode($candidate->to_json(), true, 512, JSON_THROW_ON_ERROR);
    Type_Context_Test::check(($update->full_rebuild) && ($candidate->context === $changed)
        && ($candidate->find_type('Word', 'fixture') === 0)
        && ($export['types'] === []) && ($export['representations'] === []) && ($export['members'] === [])
        && ($accepted->to_json() === $before), 'Changed inputs must not retain facts or mutate the accepted cache');

    // The same declaration/representation APIs populate every candidate.
    $id = $candidate->declare_type('Word', 'fixture');
    $candidate->set_representation($id, $candidate->intern_integer(64));
    Type_Context_Test::check(($candidate->representation_for_type($id)->payload->bit_width === 64)
        && ($accepted->representation_for_type($pending)->payload->bit_width === 32),
        'Recomputation uses the common path and preserves previous readers');
}

$update = new Update_Context();
$update->full_rebuild = true;
Type_Context_Test::check(!\resolve_types\Type_Cache::requires_rebuild($accepted, $same), 'Equal keys require no cache rebuild');
$candidate = \resolve_types\Type_Cache::prepare($accepted, $same, $update->full_rebuild);
Type_Context_Test::check(($update->full_rebuild) && ($candidate->find_type('Word', 'fixture') === 0),
    'A preexisting full-rebuild decision must stay set and disallow warm facts');

// A missing provider type remains absent, even if its spelling existed before.
Type_Context_Test::rejects(static fn() => $candidate->type_by_id($pending), OutOfBoundsException::class);
Type_Context_Test::check($accepted->to_json() === $before, 'Discarding an unfinished candidate preserves accepted facts');

foreach ([['', 'p', 't'], ['c', '', 't'], ['c', 'p', '']] as $keys) {
    Type_Context_Test::rejects(static fn() => new type_context(...$keys), InvalidArgumentException::class);
}
Type_Context_Test::rejects(static function () use ($context): void {
        $context->provider_key = 'changed';
    }, Error::class);
Type_Context_Test::rejects(static function () use ($accepted, $same): void {
        $accepted->context = $same;
    }, Error::class);
Type_Context_Test::check(json_decode($accepted->to_json(), true, 512, JSON_THROW_ON_ERROR)['context'] === [
        'configuration_key' => 'configuration-a', 'provider_key' => 'provider-a', 'target_key' => 'target-a',
    ], 'Debug export identifies the cache input context');

echo "type context ok: pending/declaration/representation separation, fixed cache inputs, conservative full selection, warm sharing and candidate isolation; signature integration is tested separately\n";
