<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{entry_target, Incremental_Project};

function check(bool $condition, string $message): void
{
	if (!$condition) throw new RuntimeException($message);
}

// The Python driver owns the fixture. Its final revision has one caller for each sibling.
$project = new Incremental_Project($argv[1], $argv[2], new entry_target(31));
$project->run();
$origin = $project->session()->scope->resolve('method:Base::read');
$instances = $origin->root()->static_implementations($origin);
check(count($instances) === 2, 'Expected two sibling demands');
[$left, $right] = array_values($instances);
check($left->instance->called->declaration()->short_name === 'Left', 'Expected stable called-type order');
check($left->body() !== $right->body() && $left->body()->statements[0] !== $right->body()->statements[0], 'Sibling facts share executable syntax');
check(!$origin->body()->is_prepared(), 'Authored body acquired private facts');
check($left->root()->tokens === $origin->root()->tokens, 'Private body copied tokens');
$weak = WeakReference::create($left->body());
unset($left, $instances);
check($project->run()['preparation']['prepared'] === 0, 'Unchanged bodies were prepared');
unlink($argv[3]);
$project->run();
gc_collect_cycles();
check($weak->get() === null, 'Retired body still held by the session');
check(array_values($origin->root()->static_implementations($origin)) === [$right], 'Surviving sibling was replaced');
