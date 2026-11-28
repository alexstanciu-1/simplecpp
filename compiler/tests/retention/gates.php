<?php

declare(strict_types=1);
namespace scpp\compiler;
require_once __DIR__ . '/../../boot.php';

function gate_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \RuntimeException($message);
	}
}

/** Compare the manifest and every owned output byte at a publication boundary. @return string[] */
function gate_bundle(string $directory): array /** array<string,string> */
{
	$text = file_get_contents($directory . '/manifest.json');
	$manifest = json_decode($text, true, flags: \JSON_THROW_ON_ERROR);
	$files = ['manifest.json' => $text];
	foreach ([...$manifest['translation_units'], ...$manifest['headers']] as $path) {
		$files[$path] = file_get_contents($directory . '/' . $path);
	}
	ksort($files);
	return $files;
}

/** Two actual source units share a published index across edits but own their own syntax. */
function publication_gate_case(string $work, string $flavor): void
{
	mkdir($work);
	$app = $work . '/app';
	mkdir($app);
	file_put_contents($app . '/module.json', '{}');
	$main_name = 'main.' . \scpp\compiler\flavor::from($flavor)->source_extension();
	$library_name = 'library.' . \scpp\compiler\flavor::from($flavor)->source_extension();
	$library_path = $app . '/' . $library_name;
	file_put_contents($app . '/' . $main_name, 'value(); return 8;');
	$header = $flavor === 'cpp' ? 'void value()' : 'function value(): void';
	$original = $header . ' {}';
	$replacement = $header . ' { echo 1; }';
	$stamp = time();
	$write = static function (string $text) use ($library_path, &$stamp): void {
		file_put_contents($library_path, $text);
		touch($library_path, ++$stamp);
	};
	$write($original);

	$output = $work . '/out';
	$target = new entry_target(31);
	$project = new Incremental_Project($app, $output, $target);
	$project->run();
	$reader = $project->session();
	$old_session = \WeakReference::create($reader);
	$files = $reader->files();
	$key = $reader->entry_key();
	$handle = new prepared_program($key, $files, new prepared_entry($files[$key], $target), $reader);
	unset($files);
	$handle->require_prepared();
	$entry = $reader->scope->entry('func:value');
	$unchanged = $reader->entry->sources[$main_name]->ast;
	$old_root = \WeakReference::create($reader->entry->sources[$library_name]->ast);
	$old_declaration = \WeakReference::create($entry->declaration());
	gate_check($old_declaration->get() === $old_root->get()->functions()[0], 'Published entry lost source ownership');

	$write($replacement);
	$persisted = $project->run();
	$current = $project->session();
	gate_check($current->scope->entry('func:value') === $entry && $entry->status === change_status::changed,
		'Replacement rebuilt the symbol entry');
	gate_check($entry->declaration() === $current->entry->sources[$library_name]->ast->functions()[0]
		&& $entry->declaration() !== $old_declaration->get(), 'Replacement left a stale declaration');
	gate_check($current->entry->sources[$main_name]->ast === $unchanged
		&& $persisted['preparation']['prepared'] === 1, 'Body edit touched the unaffected source');
	gate_check($current->previous === null && !$reader->is_current, 'Successful generation retained history');
	gate_check($reader->entry->sources[$library_name]->ast === $old_root->get()
		&& $reader->entry->sources[$library_name]->ast->functions()[0] === $old_declaration->get()
		&& $old_declaration->get()->source_text() === $original, 'Reader lost its old source declaration');
	try {
		Generation_Cleanup::close($reader);
		throw new \RuntimeException('Stale session was accepted');
	}
	catch (\LogicException) {
	}
	unset($reader);
	gc_collect_cycles();
	gate_check($old_session->get() === $handle->session && $old_root->get() !== null,
		'Prepared reader lost its source generation');
	try {
		Cpp_Program::emit($handle);
		throw new \RuntimeException('Stale prepared reader emitted code');
	}
	catch (\LogicException $error) {
		gate_check($error->getMessage() === 'Stale module compilation', 'Wrong stale-reader failure');
	}
	unset($handle, $error);
	gc_collect_cycles();
	gate_check($old_session->get() === null, 'Released prepared reader retained its session');
	gate_check($old_root->get() === null && $old_declaration->get() === null,
		'Published index kept the replaced declaration or source alive');

	$baseline = gate_bundle($output);
	$current_declaration = \WeakReference::create($entry->declaration());
	$current_root = \WeakReference::create($entry->declaration()->root());
	$write($header . ' {');
	try {
		$project->run();
		throw new \LogicException('Unfinished source was published');
	}
	catch (diagnostic $error) {
		gate_check(str_contains($error->getMessage(), 'Expected'), 'Wrong parse diagnostic');
		unset($error);
	}
	gate_check($project->session() === $current && $current->is_current, 'Parse failure replaced the published generation');
	gate_check($current->scope->entry('func:value') === $entry && $entry->status === change_status::changed
		&& $entry->declaration() === $current_declaration->get(), 'Parse failure changed the published entry');
	gate_check($current->entry->sources[$main_name]->ast === $unchanged && gate_bundle($output) === $baseline,
		'Parse failure altered syntax or the last good bundle');

	unlink($library_path);
	try {
		$project->run();
		throw new \LogicException('Missing provider was accepted');
	}
	catch (diagnostic_group $error) {
		gate_check(str_contains($error->getMessage(), 'Unknown function: value'), 'Wrong missing-provider diagnostic');
		unset($error);
	}
	$missing = $project->session();
	gate_check(!$current->is_current && $missing->previous === null && $missing->removed_sources === [],
		'Failed preparation kept obsolete history');
	gate_check($missing->scope->entry('func:value') === $entry && $entry->status === change_status::removed
		&& $entry->declaration() === null && !isset($missing->scope->heads()['func:value']), 'Removal did not leave a tombstone');
	gate_check($missing->entry->sources[$main_name]->ast === $unchanged && gate_bundle($output) === $baseline,
		'Removal failure altered the unaffected AST or last good bundle');
	try {
		Generation_Cleanup::close($missing);
		throw new \RuntimeException('Pending work was accepted');
	}
	catch (\LogicException) {
	}
	unset($current);
	gc_collect_cycles();
	gate_check($current_declaration->get() === null && $current_root->get() === null, 'Tombstone kept a removed declaration alive');

	$write($replacement);
	$project->run();
	$restored = $project->session();
	gate_check($restored->scope->entry('func:value') === $entry && $entry->status === change_status::added
		&& $entry->declaration() === $restored->entry->sources[$library_name]->ast->functions()[0],
		'Re-addition did not reuse the tombstone');
	gate_check($restored->entry->sources[$main_name]->ast === $unchanged && $restored->previous === null
		&& $restored->removed_sources === [], 'Recovery replaced reused syntax or kept obsolete history');
	Generation_Cleanup::close($restored);

	$fresh_output = $work . '/fresh';
	(new Incremental_Project($app, $fresh_output, $target))->run();
	gate_check(gate_bundle($output) === gate_bundle($fresh_output), 'Recovered output differs from fresh output');
	$noop = $project->run();
	gate_check($noop['preparation']['prepared'] === 0 && $noop['emitted_sources'] === []
		&& $entry->status === change_status::none && $project->session()->entry->sources[$main_name]->ast === $unchanged,
		'Final no-op rebuilt published work');
}

$root = sys_get_temp_dir() . '/scpp-close-gates-' . bin2hex(random_bytes(8));
mkdir($root);
try {
	foreach (['cpp', 'php', 'js'] as $flavor) {
		publication_gate_case($root . '/' . $flavor, $flavor);
	}
	echo "Publication gates: stable entries, retained readers, tombstones, parse failure and fresh recovery passed in all flavors\n";
}
finally {
	$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
		\RecursiveIteratorIterator::CHILD_FIRST);
	foreach ($files as $file) {
		$file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
	}
	rmdir($root);
}
