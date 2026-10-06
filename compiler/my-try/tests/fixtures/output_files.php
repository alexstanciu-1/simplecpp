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
foreach ([0, 1, 3, 55, 56, 63, 64, 65, 127, 128, 10000] as $length) {
	$input = '';
	for ($i = 0; $i < $length; $i++) { $input .= chr($i % 256); }
	echo content_sha256($input), "\n";
}
