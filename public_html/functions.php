<?php

/**
 * Create a ZIP archive from included folders, excluding specific folders.
 *
 * @param array<string> $includeDirs Absolute or relative paths to include
 * @param array<string> $excludeDirs Absolute or relative paths to exclude
 * @param string $zipPath Output zip file path
 */
function createZip(array $includeDirs, array $excludeDirs, string $zipPath): void
{
	$zip = new ZipArchive();

	if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
		throw new RuntimeException("Cannot open zip: $zipPath");
	}

	// Normalize exclude paths
	$excludeDirs = array_map('realpath', $excludeDirs);

	foreach ($includeDirs as $dir) {
		$dirPath = realpath($dir);
		if ($dirPath === false) {
			continue;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {
			$filePath = $file->getRealPath();

			// Skip excluded folders
			foreach ($excludeDirs as $exclude) {
				if ($exclude !== false && str_starts_with($filePath, $exclude)) {
					continue 2;
				}
			}

			// Local path inside zip
			$localPath = basename($dirPath).DIRECTORY_SEPARATOR.substr($filePath, strlen($dirPath) + 1);
			
			if ($file->isDir()) {
				$zip->addEmptyDir($localPath);
			} else {
				$zip->addFile($filePath, $localPath);
			}
		}
	}

	$zip->close();
}
