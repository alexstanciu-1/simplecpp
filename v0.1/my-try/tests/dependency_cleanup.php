<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function cleanup_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

$directory = sys_get_temp_dir() . '/scpp_cleanup_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try
{
	file_put_contents($path, 'function a(): int { return 1; } function b(): int { return a(); } return b();');
	$compiler = new Compiler();
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->sync([]);
	$compiler->prepare();
	$a = Model::$global_scope->functions_named('a')[0]->preparation;
	$b = Model::$global_scope->functions_named('b')[0]->preparation;
	$b_body = object_cast($b->declaration(), collected_function::class)->syntax()->body->work();
	$file_body = Model::collected_files()[0]->root->single_body()->body->work();
	$old_lookups = [];
	foreach ($b_body->lookups as $candidate) {
		$old_lookups[] = $candidate;
	}
	cleanup_check(count($old_lookups) > 0, 'Call body had no lookup observations');

	// Body replacement removes outgoing edges and orphaned name observations on preparation.
	file_put_contents($path, 'function a(): int { return 1; } function b(): int { return 2; } return b();');
	$compiler->sync([$path]);
	$compiler->prepare();
	cleanup_check(!isset($a->dependents[$b_body]), 'Replaced body retained its old call dependency');
	$shared_count = 0;
	$orphan_count = 0;
	foreach ($old_lookups as $lookup) {
		cleanup_check(!isset($lookup->dependents[$b_body]) && !isset($b_body->lookups[$lookup]), 'Replaced body retained lookup registration');
		$registered = in_array($lookup, $lookup->lookup_scope->preparation_lookups_named($lookup->name), true);
		if (count($lookup->dependents) === 0) {
			$orphan_count++;
			cleanup_check(!$registered, 'Orphan lookup remained in its scope');
		}
		else {
			$shared_count++;
			cleanup_check($registered && isset($lookup->dependents[$a]) && isset($a->lookups[$lookup]), 'Shared lookup lost the declaring signature subscriber');
		}
	}
	cleanup_check($shared_count > 0 && $orphan_count > 0, 'Cleanup proof did not exercise shared and orphaned lookups');

	// Exercise a reverse-link cycle in the deletion batch and a surviving downstream consumer.
	$a->dependencies[$b] = 0;
	$b->dependents[$a] = true;
	$b->dependencies[$a] = 0;
	$a->dependents[$b] = true;
	file_put_contents($path, 'return 3;');
	$compiler->sync([$path]);
	cleanup_check($a->change_status === change_state::deleted && $b->change_status === change_state::deleted, 'Notification revived deleted owners');
	cleanup_check($file_body->change_status === change_state::changed, 'Deletion failed to invalidate consumer');
	foreach ([$a, $b, $b_body] as $owner) {
		cleanup_check(count($owner->dependencies) === 0 && count($owner->dependents) === 0 && count($owner->lookups) === 0, 'Retired owner retained dependency links');
	}
	cleanup_check(!isset($file_body->dependencies[$b]), 'Consumer retained retired declaration');
	$compiler->prepare();

	// Full reset severs links even when callers retain handles to the retired graph.
	file_put_contents($path, 'function a(): int { return 1; } return a();');
	$compiler->sync([$path]);
	$compiler->prepare();
	$a = Model::$global_scope->functions_named('a')[0]->preparation;
	$file_body = Model::collected_files()[0]->root->single_body()->body->work();
	Compiler_Lifecycle::reset();
	cleanup_check(count($a->dependents) === 0 && count($file_body->dependencies) === 0 && count($file_body->lookups) === 0, 'Full reset retained dependency graph');
}
finally {
	unlink($path);
	rmdir($directory);
}
echo "Dependency cleanup: body replacement, batch deletion cycles, consumer invalidation and full reset passed\n";
