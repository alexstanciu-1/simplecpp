<?php
require_once dirname(__DIR__, 4) . '/tools/php_portability/runtime/bootstrap.php';

function output_check(bool $value): void
{
	if (!$value) { throw new RuntimeException('Output adapter assertion failed'); }
}
$root = fs_output_root($argv[1]);
output_check(fs_output_read($root, 'absent/file') === null);
output_check(fs_output_write($root, 'empty.cpp', ''));
output_check(fs_output_read($root, 'empty.cpp') === '');
output_check(!fs_output_write($root, 'empty.cpp', ''));
output_check(fs_output_remove($root, 'empty.cpp', ''));
$stage = new \scpp\Output_Stage($root);
$alias = $stage;
$stage_path = $stage->path();
file_put_contents($stage_path, "#!/bin/sh\nexit 7\n");
$inode = stat($stage_path)['ino'];
output_check($stage->promote('program', '', true));
clearstatcache();
output_check(stat($root . '/program')['ino'] === $inode && (fileperms($root . '/program') & 07777) === 0700);
$failed = false;
try { $alias->path(); } catch (RuntimeException $error) { $failed = true; }
output_check($failed);
$stage->close();
$stage = new \scpp\Output_Stage($root);
file_put_contents($stage->path(), "#!/bin/sh\nexit 7\n");
output_check(!$stage->promote('program', fs_output_digest($root, 'program'), true));
clearstatcache();
output_check(stat($root . '/program')['ino'] === $inode);
$stage = new \scpp\Output_Stage($root);
$aborted_path = $stage->path();
file_put_contents($aborted_path, 'replacement');
$failed = false;
try { $stage->promote('program', '', true); } catch (RuntimeException $error) { $failed = true; }
output_check($failed && is_file($aborted_path));
$stage->close();
$stage->close();
output_check(!file_exists($aborted_path));
output_check(fs_output_digest($root, 'program') === hash('sha256', "#!/bin/sh\nexit 7\n"));
$path = 'units/data.cpp';
$bytes = "abc\0def";
output_check(fs_output_write($root, $path, $bytes));
$before = stat($root . '/' . $path);
output_check(!fs_output_write($root, $path, $bytes));
clearstatcache(true);
$after = stat($root . '/' . $path);
output_check($before['ino'] === $after['ino'] && $before['mtime'] === $after['mtime']);
output_check(fs_output_read($root, $path) === $bytes);
$failed = false;
try { fs_output_remove($root, $path, 'wrong'); }
catch (RuntimeException $error) { $failed = true; }
output_check($failed);
foreach (['../escape', '/absolute', 'a//b', 'a/./b', 'a/../b', 'link', 'pipe', 'sub/link/file'] as $bad) {
	foreach (['read', 'write', 'remove'] as $operation) {
		$failed = false;
		try {
			if ($operation === 'read') { fs_output_read($root, $bad); }
			elseif ($operation === 'write') { fs_output_write($root, $bad, $bytes); }
			else { fs_output_remove($root, $bad, $bytes); }
		}
		catch (RuntimeException $error) { $failed = true; }
		output_check($failed);
	}
}
output_check(fs_output_remove($root, $path, $bytes));
output_check(!fs_output_remove($root, $path, $bytes));
foreach ([0, 1, 3, 55, 56, 63, 64, 65, 127, 128, 10000, 131071, 131072, 131073] as $length) {
	$input = '';
	for ($i = 0; $i < $length; $i++) { $input .= chr($i % 256); }
	fs_output_write($root, 'chunks.cpp', $input);
	output_check(fs_output_digest($root, 'chunks.cpp') === content_sha256($input));
	output_check(fs_file_digest($root . '/chunks.cpp') === content_sha256($input));
	echo content_sha256($input), "\n";
}
