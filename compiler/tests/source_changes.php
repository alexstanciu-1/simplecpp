<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Program_Loader, Project_Changes, change_status, flavor};

function verify(bool $ok, string $message): void
{
	if (!$ok) {
		throw new RuntimeException($message);
	}
}

$root = sys_get_temp_dir() . '/scpp-source-changes-' . bin2hex(random_bytes(8));
mkdir($root);
try
{
	file_put_contents($root . '/main.cpp', 'return 8;');
	file_put_contents($root . '/other.cpp', '// secondary');
	file_put_contents($root . '/facts.json', '{}');
	$session = Program_Loader::inventory($root, 'main.cpp', ['main.cpp' => flavor::cpp, 'other.cpp' => flavor::cpp]);
	$baseline = Project_Changes::capture($session, $root . '/facts.json', $root . '/out');
	verify(Project_Changes::mark($session, $baseline, null) === [], 'Initial removal');
	verify($session->entry->entry->status === change_status::added, 'Initial added status');
	verify($session->entry->entry->size === 9, 'Source size');
	Project_Changes::mark($session, $baseline, $baseline);
	verify($session->entry->entry->status === change_status::none, 'Unchanged status');
	// Mutate twice in one PHP process: capture must clear the stat cache.
	file_put_contents($root . '/main.cpp', 'return 80;');
	unlink($root . '/other.cpp');
	$session = Program_Loader::inventory($root, 'main.cpp', ['main.cpp' => flavor::cpp]);
	$current = Project_Changes::capture($session, $root . '/facts.json', $root . '/out');
	$removed = Project_Changes::mark($session, $current, $baseline);
	verify($session->entry->entry->status === change_status::changed, 'Changed status');
	verify((count($removed) === 1) && ($removed[0]->status === change_status::removed), 'Removed unit retained');
	verify(($removed[0]->path === 'other.cpp') && ($removed[0]->module === $session->entry),
		'Removed source identity');
	verify(count($session->units()) === 1, 'Removed source entered active compilation');
	verify(count(Project_Changes::mark($session, $current, $baseline)) === 1, 'Failure retry lost removal');
	verify(Project_Changes::mark($session, $current, $current) === [], 'Accepted baseline retained removal');
	verify($session->entry->entry->status === change_status::none, 'Accepted baseline retained changed status');
	verify(!Project_Changes::valid('old fingerprint'), 'Legacy hash baseline accepted');
}
finally {
	foreach (glob($root . '/*') as $path) {
		unlink($path);
	}
	rmdir($root);
}
echo "Source-unit status, mtime/size, removed records and baseline acceptance passed\n";
