<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{entry_target, Incremental_Project};

function check(bool $condition, string $message): void
{
	if (!$condition) throw new RuntimeException($message);
}

// The Python driver gives each flavor its own fixture, with one caller for each sibling.
$caller_path = $argv[3];
$main_path = $argv[1] . '/main.' . pathinfo($caller_path, PATHINFO_EXTENSION);
$main_source = file_get_contents($main_path);
$caller_source = file_get_contents($caller_path);
check($main_source !== false && $caller_source !== false, 'Missing lifecycle fixture sources');
$project = new Incremental_Project($argv[1], $argv[2], new entry_target(31));

unlink($caller_path);
file_put_contents($main_path, 'return 8;');
$project->run();
$watches = $project->session()->dependency_watches;
check($watches->called_methods() === null, 'Static methods with no callers allocated a demand graph');

file_put_contents($main_path, $main_source);
$project->run();
$cache = $watches->called_methods();
check($cache !== null && $cache->count() === 1, 'First static call did not create the demand graph');
check($project->session()->dependency_watches === $watches, 'First call replaced the dependency coordinator');
check($project->run()['preparation']['prepared'] === 0, 'Unchanged bodies were prepared');
check($watches->called_methods() === $cache, 'No-op preparation replaced the demand graph');

$origin = $project->session()->scope->resolve('method:Base::read');
$first = $origin->root()->static_implementations($origin);
$right_key = array_key_first($first);
$right = $first[$right_key];
check($right->instance->called->declaration()->short_name === 'Right'
	&& $right_key === spl_object_id($right->instance->called), 'Expected Right first under its called-type key');
unset($first);

file_put_contents($caller_path, $caller_source);
$project->run();
check($watches->called_methods() === $cache && $cache->count() === 2, 'Second caller did not reuse the demand graph');
$instances = $origin->root()->static_implementations($origin);
check(count($instances) === 2, 'Expected two sibling demands');
$left = reset($instances);
check($left->instance->called->declaration()->short_name === 'Left', 'Expected Left before the earlier Right demand');
$left_key = spl_object_id($left->instance->called);
check($instances === [$left_key => $left, $right_key => $right], 'Sorting changed called-type keys or published order');
check($left->body() !== $right->body() && $left->body()->statements[0] !== $right->body()->statements[0], 'Sibling facts share executable syntax');
check(!$origin->body()->is_prepared(), 'Authored body acquired private facts');
check($left->root()->tokens === $origin->root()->tokens, 'Private body copied tokens');

$edited_caller = str_replace('read();', 'read() + 0;', $caller_source);
check($edited_caller !== $caller_source, 'Fixture did not edit the caller body');
file_put_contents($caller_path, $edited_caller);
$project->run();
check($project->session()->dependency_watches === $watches && $watches->called_methods() === $cache,
	'An incremental caller edit replaced the demand graph');
check(array_values($origin->root()->static_implementations($origin)) === [$left, $right],
	'An edit with the same demands replaced an implementation');

$left_entry = WeakReference::create($left->instance);
$left_body = WeakReference::create($left->body());
unset($left, $instances);
unlink($caller_path);
$project->run();
gc_collect_cycles();
check($left_entry->get() === null && $left_body->get() === null, 'Retired left demand or body still held by the session');
check($watches->called_methods() === $cache && $cache->count() === 1, 'Retirement replaced the cache or discarded the surviving demand');
check($origin->root()->static_implementations($origin) === [$right_key => $right], 'Retiring Left failed to preserve Right under its original key');

$right_entry = WeakReference::create($right->instance);
$right_body = WeakReference::create($right->body());
unset($right);
file_put_contents($main_path, 'return 8;');
$project->run();
gc_collect_cycles();
check($right_entry->get() === null && $right_body->get() === null, 'Removing the last caller retained its demand or body');
check($watches->called_methods() === $cache && $cache->count() === 0, 'Removing the last caller did not leave an empty demand graph');
check($origin->root()->static_implementations($origin) === [], 'Retired static implementations stayed published');

file_put_contents($main_path, $main_source);
$project->run();
check($watches->called_methods() === $cache && $cache->count() === 1, 'New demand after retirement did not reuse the cache');
