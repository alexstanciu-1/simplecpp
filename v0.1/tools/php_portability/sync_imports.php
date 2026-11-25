<?php
declare(strict_types=1);

require_once __DIR__ . '/src/import_policy.php';
try {
	if ($argc < 2 || $argc > 3 || ($argc === 3 && $argv[2] !== '--check')) {
		throw new RuntimeException('Usage: php sync_imports.php SOURCE_DIRECTORY [--check]');
	}
	$root = realpath($argv[1]);
	if ($root === false || !is_dir($root)) { throw new RuntimeException('Source directory does not exist'); }
	$policy = new scpp\portability\Import_Policy(require __DIR__ . '/function_map.php');
	$updates = [];
	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
		if (!$file->isFile() || $file->getExtension() !== 'php') { continue; }
		if ($file->isLink()) { throw new RuntimeException('Symlink sources are unsupported'); }
		$source = file_get_contents($file->getPathname());
		$next = $policy->synchronize($source, $file->getPathname());
		if ($source !== $next) { $updates[$file->getPathname()] = $next; }
	}
	if ($argc === 3 && $updates !== []) {
		throw new RuntimeException('Missing or stale managed imports: ' . implode(', ', array_keys($updates)));
	}
	foreach ($updates as $path => $bytes) {
		$temp = tempnam(dirname($path), '.imports-');
		try {
			chmod($temp, fileperms($path) & 0777);
			if (file_put_contents($temp, $bytes) !== strlen($bytes) || !rename($temp, $path)) {
				throw new RuntimeException('Cannot update ' . $path);
			}
		} finally { if (is_file($temp)) { unlink($temp); } }
	}
	echo 'Updated imports: ', count($updates), "\n";
} catch (Throwable $error) {
	fwrite(STDERR, $error->getMessage() . "\n");
	exit(1);
}
