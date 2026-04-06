<?php
declare(strict_types=1);

const PHP_AST_WINDOWS_RELEASE = '1.1.3';
const PHP_AST_WINDOWS_BASE_URL = 'https://downloads.php.net/~windows/pecl/releases/ast/' . PHP_AST_WINDOWS_RELEASE;

main();

function main(): void
{
	echo "Simple C++ installer\n\n";

	ensure_php_version();

	if (PHP_OS_FAMILY === 'Windows') {
		ensure_windows_php_ast();
	}
	else {
		ensure_non_windows_php_ast();
	}

	$projectDir = __DIR__;
	$binDir = get_user_bin_dir();

	ensure_dir($binDir);
	write_launcher($projectDir, $binDir);
	write_config($projectDir, $binDir);

	echo "Done.\n";
}

function ensure_php_version(): void
{
	if (version_compare(PHP_VERSION, '8.4.0', '<')) {
		throw new RuntimeException('Simple C++ requires PHP 8.4 or newer.');
	}

	echo "PHP version OK: " . PHP_VERSION . "\n";
}

function ensure_windows_php_ast(): void
{
	if (extension_loaded('ast')) {
		echo "php-ast already enabled\n";
		return;
	}

	$phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
	if (!in_array($phpVersion, ['8.4', '8.5'], true)) {
		throw new RuntimeException("Automatic Windows php-ast install currently supports PHP 8.4 and 8.5. Detected: {$phpVersion}");
	}

	$threadSafety = detect_windows_php_thread_safety() ? 'ts' : 'nts';
	$zipName = sprintf(
		'php_ast-%s-%s-%s-vs17-x64.zip',
		PHP_AST_WINDOWS_RELEASE,
		$phpVersion,
		$threadSafety
	);

	$url = PHP_AST_WINDOWS_BASE_URL . '/' . $zipName;
	echo "Installing php-ast from: {$url}\n";

	$tempDir = rtrim(sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'simple_cpp_php_ast';
	ensure_dir($tempDir);

	$zipFile = $tempDir . DIRECTORY_SEPARATOR . $zipName;
	file_put_contents_or_throw($zipFile, download_file($url));

	$extractDir = $tempDir . DIRECTORY_SEPARATOR . 'extract';
	if (is_dir($extractDir)) {
		delete_tree($extractDir);
	}
	ensure_dir($extractDir);

	$zip = new ZipArchive();
	$res = $zip->open($zipFile);
	if ($res !== true) {
		throw new RuntimeException("Unable to open php-ast zip: {$zipFile}");
	}
	$zip->extractTo($extractDir);
	$zip->close();

	$dllSource = find_file_recursive($extractDir, 'php_ast.dll');
	if ($dllSource === null) {
		throw new RuntimeException('php_ast.dll was not found in the downloaded package.');
	}

	$extDir = (string) ini_get('extension_dir');
	if ($extDir === '') {
		$extDir = dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'ext';
	}
	ensure_dir($extDir);

	$dllTarget = rtrim($extDir, "\\/") . DIRECTORY_SEPARATOR . 'php_ast.dll';
	if (!@copy($dllSource, $dllTarget) && !is_file($dllTarget)) {
		throw new RuntimeException("Failed to copy php_ast.dll to: {$dllTarget}");
	}

	$phpIni = locate_php_ini_windows();
	ensure_windows_php_ini_has_ast($phpIni);

	echo "php-ast installed to: {$dllTarget}\n";
	echo "php.ini updated: {$phpIni}\n";
	echo "Open a new terminal after installation so PHP reloads the updated php.ini.\n";
}

function ensure_non_windows_php_ast(): void
{
	if (!extension_loaded('ast')) {
		throw new RuntimeException('php-ast is required. Install and enable it before running install.php.');
	}

	echo "php-ast already enabled\n";
}

function get_user_bin_dir(): string
{
	if (PHP_OS_FAMILY === 'Windows') {
		$home = (string) (getenv('USERPROFILE') ?: '');
	}
	else {
		$home = (string) (getenv('HOME') ?: '');
	}

	if ($home === '') {
		throw new RuntimeException('Unable to determine the user home directory.');
	}

	return $home . DIRECTORY_SEPARATOR . '.d-app';
}

function write_launcher(string $projectDir, string $binDir): void
{
	$runPhp = $projectDir . DIRECTORY_SEPARATOR . 'php_generator' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'run.php';

	if (PHP_OS_FAMILY === 'Windows') {
		$launcherPath = $binDir . DIRECTORY_SEPARATOR . 's++.cmd';
		$content =
			"@echo off\r\n" .
			"php " . escapeshellarg($runPhp) . " %*\r\n";
	}
	else {
		$launcherPath = $binDir . DIRECTORY_SEPARATOR . 's++';
		$content =
			"#!/usr/bin/env bash\n" .
			"php " . escapeshellarg($runPhp) . " \"$@\"\n";
	}

	file_put_contents_or_throw($launcherPath, $content);

	if (PHP_OS_FAMILY !== 'Windows') {
		@chmod($launcherPath, 0755);
	}

	echo "Launcher installed: {$launcherPath}\n";
}

function write_config(string $projectDir, string $binDir): void
{
	$configPath = $binDir . DIRECTORY_SEPARATOR . 'simple-cpp.json';
	$config = [
		'src_dir' => $projectDir,
	];

	$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	if (!is_string($json)) {
		throw new RuntimeException('Failed to encode installer config.');
	}

	file_put_contents_or_throw($configPath, $json . PHP_EOL);
	echo "Config written: {$configPath}\n";
}

function detect_windows_php_thread_safety(): bool
{
	ob_start();
	phpinfo(INFO_GENERAL);
	$phpinfo = (string) ob_get_clean();

	if (preg_match('/Thread Safety\s*<\/td>\s*<td[^>]*>\s*(enabled|disabled)/i', $phpinfo, $matches)) {
		return strtolower($matches[1]) === 'enabled';
	}

	$plain = trim(strip_tags($phpinfo));
	if (preg_match('/Thread Safety\s*=>\s*(enabled|disabled)/i', $plain, $matches)) {
		return strtolower($matches[1]) === 'enabled';
	}

	throw new RuntimeException('Unable to determine PHP thread safety on Windows.');
}

function locate_php_ini_windows(): string
{
	$loaded = php_ini_loaded_file();
	if (is_string($loaded) && $loaded !== '') {
		return $loaded;
	}

	$phpDir = dirname(PHP_BINARY);
	$candidate = $phpDir . DIRECTORY_SEPARATOR . 'php.ini';
	if (!is_file($candidate)) {
		$production = $phpDir . DIRECTORY_SEPARATOR . 'php.ini-production';
		if (is_file($production)) {
			if (!@copy($production, $candidate) && !is_file($candidate)) {
				throw new RuntimeException("Failed to create php.ini from php.ini-production");
			}
		}
		else {
			file_put_contents_or_throw($candidate, '');
		}
	}

	return $candidate;
}

function ensure_windows_php_ini_has_ast(string $phpIni): void
{
	$content = file_get_contents($phpIni);
	if ($content === false) {
		throw new RuntimeException("Failed to read php.ini: {$phpIni}");
	}

	$pattern = '/(^|\R)\s*;?\s*extension\s*=\s*php_ast\.dll\s*(?=$|\R)/i';
	if (preg_match($pattern, $content)) {
		$content = preg_replace($pattern, '$1extension=php_ast.dll', $content);
	}
	else {
		if ($content !== '' && !str_ends_with($content, "\n") && !str_ends_with($content, "\r")) {
			$content .= PHP_EOL;
		}
		$content .= 'extension=php_ast.dll' . PHP_EOL;
	}

	file_put_contents_or_throw($phpIni, $content);
}

function download_file(string $url): string
{
	if (!function_exists('curl_init')) {
		throw new RuntimeException('The PHP cURL extension is required for automatic Windows php-ast installation.');
	}

	$ch = curl_init($url);
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_SSL_VERIFYHOST => 2,
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_TIMEOUT => 120,
	]);

	$data = curl_exec($ch);
	if ($data === false) {
		$error = curl_error($ch);
		curl_close($ch);
		throw new RuntimeException("Download failed: {$error}");
	}

	$status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
	curl_close($ch);

	if ($status < 200 || $status >= 300) {
		throw new RuntimeException("Download failed with HTTP {$status}: {$url}");
	}

	return $data;
}

function find_file_recursive(string $baseDir, string $fileName): ?string
{
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS)
	);

	foreach ($iterator as $fileInfo) {
		if ($fileInfo->isFile() && strcasecmp($fileInfo->getFilename(), $fileName) === 0) {
			return $fileInfo->getPathname();
		}
	}

	return null;
}

function ensure_dir(string $dir): void
{
	if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
		throw new RuntimeException("Failed to create directory: {$dir}");
	}
}

function file_put_contents_or_throw(string $path, string $content): void
{
	if (file_put_contents($path, $content) === false) {
		throw new RuntimeException("Failed to write file: {$path}");
	}
}

function delete_tree(string $dir): void
{
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ($iterator as $fileInfo) {
		if ($fileInfo->isDir()) {
			@rmdir($fileInfo->getPathname());
		}
		else {
			@unlink($fileInfo->getPathname());
		}
	}

	@rmdir($dir);
}
