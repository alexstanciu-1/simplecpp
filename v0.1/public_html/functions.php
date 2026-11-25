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
		throw new RuntimeException("Cannot open zip: {$zipPath}");
	}

	// Normalize $includeDirs
	$includeDirs = array_map('realpath', $includeDirs);
	// Normalize exclude paths
	$excludeDirs = array_map('realpath', $excludeDirs);
	
	$max_common_dir = null;
	foreach ($includeDirs as $dir) {
		$d = is_file($dir) ? dirname($dir) : realpath($dir);
		if ($d === false) {
			continue;
		}
		$d = explode(DIRECTORY_SEPARATOR, trim($d, DIRECTORY_SEPARATOR));
		if ($max_common_dir === null) {
			$max_common_dir = $d;
		}
		else {
			$new_mcd = [];
			foreach ($max_common_dir as $pos => $md) {
				if ($md !== $d[$pos]) {
					break;
				}
				else {
					$new_mcd[] = $md;
				}
			}
			$max_common_dir = $new_mcd;
		}
	}
	$max_common_dir = !empty($max_common_dir) ? DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $max_common_dir) . DIRECTORY_SEPARATOR : "/";
	
	foreach ($includeDirs as $dir) {
		if (!is_string($dir)) {
			continue;
		}
		$dirPath = realpath($dir);
		if ($dirPath === false) {
			continue;
		}
		
		$debug = false;
		if (is_file($dirPath)) {
			$iterator = [ new SplFileInfo($dirPath) ];
			$dirPath = dirname($dirPath);
			$debug = true;
		}
		else {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS)
			);
		}

		foreach ($iterator as $file) {
			$filePath = $file->getRealPath();
			
			// Skip excluded folders
			foreach ($excludeDirs as $exclude) {
				if ($exclude !== false && str_starts_with($filePath, $exclude)) {
					continue 2;
				}
			}

			// Local path inside zip
			$localPath = substr($filePath, strlen($max_common_dir));
			
			if ($file->isDir()) {
				$zip->addEmptyDir($localPath);
			} else {
				$zip->addFile($filePath, $localPath);
			}
		}
	}

	$zip->close();
}
