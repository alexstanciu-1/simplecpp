<?php
declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{Program_Frontend, entry_target, source_file, flavor, passing_mode, callable_signature_index, owned_callable_type};

/** Load parameter declarations through frontend preparation.
 * @param string[] $declarations
 * @return owned_callable_type[]
 */
function declared_types(string $name, array $declarations): array
{
	$params = [];
	foreach ($declarations as $i => $decl) $params[] = 'function<' . $decl . '> $p' . $i;
	$source = "class $name {} function sample(" . implode(', ', $params) . "):void {} return 8;";
	$program = (new Program_Frontend())->prepare_sources('main', ['main' => new source_file('main', $source, flavor::php)], new entry_target(31));
	foreach ($program->files['main']->functions() as $fn) {
		if ($fn->name() === 'sample') return array_map(static fn($p) => $p->type_def(), $fn->parameters);
	}
	throw new RuntimeException('Missing prepared sample');
}

/** Keep only observers at the end of this scope.
 * @return WeakReference[]
 */
function release_leaf(): array
{
	[$owned] = declared_types('Transient', ['int(Transient)']);
	$canonical = callable_signature_index::element($owned->signature);
	return [WeakReference::create($canonical), WeakReference::create($owned->signature->inputs[0])];
}

[$old_leaf, $old_input] = release_leaf();
gc_collect_cycles();
if ($old_leaf->get() !== null || $old_input->get() !== null) throw new RuntimeException('Production cache retained a retired declaration');

// Retaining the prepared signature alone must allow the canonical owner to expire.
[$declared] = declared_types('Holder', ['int(int)']);
$signature = $declared->signature;
$observer = WeakReference::create(callable_signature_index::element($signature));
if ($observer->get() !== null) throw new RuntimeException('Live components pinned a canonical owner');
$replacement = callable_signature_index::element($signature);
if ($replacement !== callable_signature_index::element($signature) || $observer->get() !== null) {
	throw new RuntimeException('Lookup after expiry did not publish a fresh live owner');
}

$rows = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$components = new SplObjectStorage();
$elements = new SplObjectStorage();
$cases = [];
foreach ($rows as $row) {
	foreach (declared_types('Box', $row) as $owned) {
		$sig = $owned->signature;
		$owned = callable_signature_index::element($sig);
		$ids = [];
		foreach ([$sig->result, ...$sig->inputs] as $type) {
			if (!$components->offsetExists($type)) $components[$type] = count($components);
			$ids[] = $components[$type];
		}
		$modes = [];
		foreach ($sig->inputs as $i => $input) {
			$modes[] = $sig->input_mode($i) === passing_mode::reference;
		}
		if (!$elements->offsetExists($owned)) $elements[$owned] = count($elements);
		$cases[] = [$ids, $modes, $sig->reference_origin !== null, $sig->variadic_slot !== null, $elements[$owned]];
	}
}
echo json_encode($cases, JSON_THROW_ON_ERROR);
