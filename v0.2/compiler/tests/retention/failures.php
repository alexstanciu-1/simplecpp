<?php

declare(strict_types=1);
namespace scpp\compiler;
require_once __DIR__ . '/../../boot.php';
require_once __DIR__ . '/inspect.php';

function failure_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \RuntimeException($message);
	}
}

/** Repeated failures retain only current analysis and retry work; the last published bundle remains reusable. @return mixed[] */
function failure_case(string $app, string $flavor): array /** list<array<string,mixed>> */
{
	mkdir($app);
	file_put_contents($app . '/module.json', '{}');
	$function = static fn(string $name, string $type, string $body): string => ($flavor === 'cpp'
		? "$type $name()" : "function $name(): $type") . '{' . $body . '}';
	$stamp = time();
	$write = static function (string $name, string $text) use ($app, $flavor, &$stamp): void {
		file_put_contents($app . '/' . $name . '.' . $flavor, $text);
		touch($app . '/' . $name . '.' . $flavor, ++$stamp);
	};
	$write('main', 'echo value(); return 8;');
	$write('quiet', $function('quiet', 'void', ''));
	$write('provider', $function('value', 'int', 'return 8;'));
	$project = new Incremental_Project($app, $app . '/out', new entry_target(31));
	$write('provider', "\xff");
	try {
		$project->run();
		throw new \LogicException('Initial invalid source accepted');
	}
	catch (diagnostic) {
	}
	failure_check($project->session() === null, 'Initial failure adopted incomplete candidate');
	$write('provider', $function('value', 'int', 'return 8;'));
	$probe = new Retention_Inspection();
	$rows = [];
	$quiet = null;
	$slot = null;
	/** Observe after dropping exceptions, which are external owners of source data and stack arguments in PHP. */
	$run = static function (string $phase, ?string $expected, ?int $prepared = null) use ($project, $probe, &$rows, &$quiet, &$slot): void
	{
		$message = null;
		$result = null;
		$start = hrtime(true);
		try {
			$result = $project->run();
		}
		catch (\Throwable $error) {
			$message = $error->getMessage();
			unset($error);
		}
		$php_ms = (hrtime(true) - $start) / 1e6;
		failure_check($expected === null ? $message === null : (($message !== null) && str_contains($message, $expected)),
			$phase . ': unexpected result ' . ($message ?? 'success'));
		$session = $project->session();
		$slot ??= $session->scope->entries()['func:value'];
		failure_check($session->scope->entries()['func:value'] === $slot, 'Lost stable entry');
		$body = $session->scope->resolve('func:quiet')->body();
		$quiet ??= \WeakReference::create($body);
		failure_check(($quiet->get() === $body) && $body->is_prepared(), 'Quiet cache changed');
		$start = hrtime(true);
		gc_collect_cycles();
		$gc_ms = (hrtime(true) - $start) / 1e6;
		$snapshot = $probe->snapshot($project);
		failure_check(($snapshot['session_depth'] === 1) && ($snapshot['retired_ast_roots'] === 0), $phase . ': retained history');
		failure_check(($snapshot['stale_watchers'] === 0) && ($snapshot['pending_notifications'] === 0), 'Stale watchers');
		$families = [compilation_session::class, ast_file_node::class, ast_function_body::class, token_list::class, source_file::class];
		failure_check($probe->survivors() === array_sum(array_intersect_key($snapshot['class_counts'], array_flip($families))),
			$phase . ': observed retired object survives');
		if ($expected === null) {
			failure_check($result['preparation']['prepared'] === $prepared, $phase . ': wrong prepared count');
			failure_check(($snapshot['pending_bodies'] === 0) && ($snapshot['pending_emission_sources'] === 0), 'Successful work pending');
		}
		$rows[] = ['phase' => $phase, 'error' => $message, 'php_ms' => $php_ms, 'gc_ms' => $gc_ms] + $snapshot;
	};
	$run('initial', null, 3);
	$before = \WeakReference::create($project->session());
	for ($i = 0; $i < 10; $i++) {
		$write('provider', "\xff" . str_repeat(' ', $i));
		$run('tokenize_failure', 'Invalid UTF-8');
		failure_check($before->get() === $project->session(), 'Token failure replaced published session');
	}
	for ($i = 0; $i < 10; $i++) {
		$write('provider', ($flavor === 'cpp' ? 'int value(){' : 'function value(): int {') . str_repeat(' ', $i));
		$run('parse_failure', 'Expected');
		failure_check($before->get() === $project->session(), 'Parse failure replaced published session');
	}
	$write('provider', $function('value', 'int', 'return 8;'));
	$run('parse_recovery', null, 1);
	for ($i = 0; $i < 10; $i++) {
		if (($i % 2) === 0) {
			$write('provider', $function('value', 'UnknownType', 'return 8;') . str_repeat(' ', $i));
		}
		$run('declaration_failure', 'Unknown or unsupported return type');
		failure_check($project->session()->preparation_work->pending_count() === 2, 'Declaration retry lost pending bodies');
	}
	$write('provider', $function('value', 'int', 'return missing();'));
	for ($i = 0; $i < 10; $i++) {
		if (($i % 2) === 0) {
			$write('provider', $function('value', 'int', 'return missing();') . str_repeat(' ', $i));
		}
		$run('body_failure', 'Unknown function: missing');
		$session = $project->session();
		failure_check($session->preparation_work->pending_count() === 1, 'Failed body not pending');
		failure_check($session->dependency_watches->observer_count($session->scope->entries()['func:missing']) === 1,
			'Necessary missing-name watch lost/duplicated');
		unset($session);
	}
	$write('missing', $function('missing', 'int', 'return 8;'));
	$run('missing_recovery', null, 2);
	for ($i = 0; $i < 10; $i++) {
		if (($i % 2) === 0) {
			$write('duplicate', $function('value', 'int', 'return 9;') . str_repeat(' ', $i));
		}
		$run('duplicate_failure', 'Duplicate function');
		failure_check($project->session()->dependency_watches->observer_count($slot) === 1, 'Ambiguity lost consumer watch');
	}
	unlink($app . '/duplicate.' . $flavor);
	$run('duplicate_recovery', null, 1);
	// Block an existing output path after preparation; repairing it requires no source edit.
	$data = json_decode(file_get_contents($app . '/out/manifest.json'), true, flags: \JSON_THROW_ON_ERROR);
	$provider_path = null;
	foreach ($data['sources'] as $key => $path) {
		if (str_ends_with($key, 'provider.' . $flavor)) {
			$provider_path = $app . '/out/' . $path;
		}
	}
	failure_check($provider_path !== null, 'Provider output missing');
	$saved = file_get_contents($provider_path);
	unlink($provider_path);
	mkdir($provider_path);
	$write('provider', $function('value', 'int', 'return 9;'));
	$baseline = file_get_contents($app . '/out/manifest.json');
	for ($i = 0; $i < 10; $i++) {
		$run('publication_failure', 'Cannot');
		failure_check($project->session()->preparation_work->pending_count() === 0, 'Prepared body unnecessarily pending');
		failure_check(count($project->session()->preparation_work->emission_sources()) === 1, 'Emission retry work lost');
		failure_check(file_get_contents($app . '/out/manifest.json') === $baseline, 'Failed publication committed manifest');
	}
	rmdir($provider_path);
	file_put_contents($provider_path, $saved);
	$run('publication_retry_without_edit', null, 0);
	$run('final_noop', null, 0);
	unset($run, $project, $slot);
	gc_collect_cycles();
	failure_check($probe->survivors() === 0, 'Project release left survivors');
	return $rows;
}

$root = sys_get_temp_dir() . '/scpp-failure-cleanup-' . bin2hex(random_bytes(8));
mkdir($root);
try {
	$report = [];
	foreach (['cpp', 'php', 'js'] as $flavor) {
		$report[$flavor] = failure_case($root . '/' . $flavor, $flavor);
	}
	echo json_encode($report, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR) . "\n";
}
finally {
	$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
		\RecursiveIteratorIterator::CHILD_FIRST);
	foreach ($files as $file) {
		$file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
	}
	rmdir($root);
}
