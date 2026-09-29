<?php

namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function file_scan_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

$root = sys_get_temp_dir() . '/scpp_files_' . bin2hex(random_bytes(6));
mkdir($root);
mkdir($root . '/one');
mkdir($root . '/two');
mkdir($root . '/one/nested');
$first = $root . '/one/main.phs';
$second = $root . '/two/main.phs';
$nested = $root . '/one/nested/item.phs';
file_put_contents($first, 'function first(): int { return 1; }');
file_put_contents($second, 'return first();');
file_put_contents($nested, 'function nested(): int { return 3; }');
try
{
	$compiler = new Compiler();
	$compiler->init([$root . '/one', $root . '/two']);
	$one = Model::$modules[$root . '/one'];
	$two = Model::$modules[$root . '/two'];
	$a = $one->sources['main.phs'];
	$b = $two->sources['main.phs'];
	$c = $one->sources['nested/item.phs'];
	file_scan_check(($a !== $b) && ($a->path === $b->path), 'Module-relative paths did not retain separate identities');
	file_scan_check(($c->path === 'nested/item.phs') && ($c->file->path === $c->path), 'Nested source was not indexed directly at module level');
	file_scan_check(($a->file->content === '') && ($a->parsed === null), 'Discovery read source bytes');
	$members = $one->sources;
	$compiler->sync([]);
	$syntax_a = $a->parsed;
	$syntax_b = $b->parsed;
	$syntax_c = $c->parsed;
	$published = $a->file;
	$compiler->sync([]);
	file_scan_check(($one->sources === $members) && ($a->file === $published) && ($a->parsed === $syntax_a) && ($b->parsed === $syntax_b), 'Unchanged scan replaced membership or published snapshots');

	// Size changes detect modifications even within the same timestamp tick.
	file_put_contents($first, 'function first(): int { return 12; }');
	$compiler->sync([]);
	file_scan_check(($one->sources['main.phs'] === $a) && ($a->parsed === $syntax_a) && ($b->parsed === $syntax_b) && ($c->parsed === $syntax_c), 'Metadata scan rebuilt the wrong files or replaced source identity');
	$syntax_a = $a->parsed;
	$mtime = $a->file->mtime;
	file_put_contents($first, 'function first(): int { return 13; }');
	touch($first, $mtime);
	$compiler->sync([]);
	file_scan_check($a->parsed === $syntax_a, 'Equal metadata did not follow the accepted initial change detector');
	$compiler->sync([$first]);
	file_scan_check(($a->parsed === $syntax_a) && str_contains($a->file->content, '13'), 'Explicit notification did not force a reread');

	// A failed forced read/parse remains pending even with matching filesystem metadata.
	$good = $a->file->content;
	$syntax_a = $a->parsed;
	file_put_contents($first, str_pad('$', strlen($good), ' '));
	touch($first, $a->file->mtime);
	$failed = false;
	try {
		$compiler->sync([$first]);
	}
	catch (\RuntimeException $expected) {
		$failed = true;
	}
	file_scan_check($failed && ($a->parsed === $syntax_a) && ($a->file->content === $good), 'Failed candidate damaged published input');
	file_put_contents($first, str_replace('13', '14', $good));
	touch($first, $a->file->mtime);
	$compiler->sync([]);
	file_scan_check(($a->parsed === $syntax_a) && str_contains($a->file->content, '14'), 'Pending work disappeared behind equal metadata');

	// Add and remove nested files without allocating a folder owner or replacing the index.
	$new_path = $root . '/one/nested/new.phs';
	file_put_contents($new_path, 'function extra(): int { return 8; }');
	$compiler->sync([]);
	$added = $one->sources['nested/new.phs'];
	file_scan_check(($one->sources === $members) && ($added->parsed !== null), 'New nested file did not update module membership in place');
	unlink($nested);
	$compiler->sync([]);
	file_scan_check(($one->sources['nested/item.phs'] === $c) && ($c->changes === change_state::deleted) && ($c->file->changes === SYNC_DELETED), 'Deleted file lost its tombstone');
	file_scan_check(count(Model::$global_scope->functions_named('nested')) === 0, 'Deleted file remained visible in scope');
	file_put_contents($nested, 'function nested(): int { return 4; }');
	$compiler->sync([]);
	file_scan_check(($one->sources['nested/item.phs'] === $c) && ($c->changes === change_state::unchanged), 'Reappearance lost retained identity');

	// An incomplete module scan must not treat unvisited entries as deleted.
	chmod($root . '/one/nested', 0000);
	if (!is_readable($root . '/one/nested'))
	{
		$failed = false;
		try {
			$compiler->sync([]);
		}
		catch (\RuntimeException $expected) {
			$failed = true;
		}
		file_scan_check($failed && ($c->changes !== change_state::deleted) && ($added->changes !== change_state::deleted), 'Failed traversal deleted unvisited files');
		chmod($root . '/one/nested', 0700);
		$compiler->sync([]);
	}
	chmod($root . '/one/nested', 0700);
	Model::$revision = 4294967295;
	$c->revision = 1;
	$compiler->sync([]);
	file_scan_check(($c->changes === change_state::unchanged) && ($c->revision !== 0), 'Revision rollover lost a scanned file');
	file_scan_check(Source_Registry::find($first) === $a, 'External path lookup did not select module-local identity');
}
finally
{
	chmod($root . '/one/nested', 0700);
	foreach (glob($root . '/one/nested/*') as $path) {
		unlink($path);
	}
	rmdir($root . '/one/nested');
	foreach (['one', 'two'] as $name) {
		foreach (glob($root . '/' . $name . '/*') as $path) {
			unlink($path);
		}
		rmdir($root . '/' . $name);
	}
	rmdir($root);
}
echo "File scan: module-relative indexes, metadata changes, reuse, notifications, deletion and retry passed\n";
