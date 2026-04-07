<?php
declare(strict_types=1);

const PHP_AST_WINDOWS_RELEASE = '1.1.3';
const PHP_AST_WINDOWS_BASE_URL = 'https://downloads.php.net/~windows/pecl/releases/ast/' . PHP_AST_WINDOWS_RELEASE;
const SCPP_MIN_PHP_VERSION = '8.4.0';
const SCPP_VERSION = '0.1.0-dev';
const SCPP_PROFILE_BEGIN = '# >>> scpp user-local bin >>>';
const SCPP_PROFILE_END = '# <<< scpp user-local bin <<<';

main();

function main(): void
{
	echo "Prism++ installer\n\n";

	ensure_php_version();

	if (PHP_OS_FAMILY === 'Windows') {
		ensure_windows_php_ast();
	}
	else {
		ensure_non_windows_php_ast();
	}

	$repoRoot = dirname(__DIR__);
	$binDir = get_user_bin_dir();

	ensure_dir($binDir);
	write_php_shim($binDir);
	write_launcher($binDir);
	write_config($repoRoot, $binDir);
	ensure_user_path_entry($binDir);
	verify_install($binDir);
	print_success_notes($repoRoot, $binDir);
}

function ensure_php_version(): void
{
	if (version_compare(PHP_VERSION, SCPP_MIN_PHP_VERSION, '<')) {
		throw new RuntimeException('Prism++ requires PHP 8.4 or newer.');
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

	$tempDir = rtrim(sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'prismpp_php_ast';
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
		$localAppData = (string) (getenv('LOCALAPPDATA') ?: '');
		if ($localAppData === '') {
			throw new RuntimeException('Unable to determine %LOCALAPPDATA% for the current user.');
		}

		return $localAppData . DIRECTORY_SEPARATOR . 'Programs' . DIRECTORY_SEPARATOR . 'scpp' . DIRECTORY_SEPARATOR . 'bin';
	}

	$home = (string) (getenv('HOME') ?: '');
	if ($home === '') {
		throw new RuntimeException('Unable to determine the user home directory.');
	}

	return $home . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR . 'bin';
}

function write_php_shim(string $binDir): void
{
	$shimPath = $binDir . DIRECTORY_SEPARATOR . 'scpp.php';
	$content = <<<'PHP'
<?php
declare(strict_types=1);

// Repo-based installed shim. Reads sibling config and forwards to the repo entrypoint.
$configPath = __DIR__ . DIRECTORY_SEPARATOR . 'scpp.json';
if (!is_file($configPath)) {
	fwrite(STDERR, "Missing scpp.json next to launcher\n");
	exit(2);
}

$configJson = file_get_contents($configPath);
if ($configJson === false) {
	fwrite(STDERR, "Failed to read scpp.json\n");
	exit(2);
}

$config = json_decode($configJson, true);
if (!is_array($config) || !isset($config['repo_root']) || !is_string($config['repo_root']) || $config['repo_root'] === '') {
	fwrite(STDERR, "Invalid scpp.json: missing repo_root\n");
	exit(2);
}

$entry = rtrim($config['repo_root'], "\\/") . DIRECTORY_SEPARATOR . 'php_generator' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'scpp.php';
if (!is_file($entry)) {
	fwrite(STDERR, "Entrypoint not found: {$entry}\n");
	fwrite(STDERR, "This is a repo-based install. If the repo moved, run the installer again.\n");
	exit(2);
}

require $entry;
PHP;
	file_put_contents_or_throw($shimPath, $content . PHP_EOL);
}

function write_launcher(string $binDir): void
{
	if (PHP_OS_FAMILY === 'Windows') {
		$launcherPath = $binDir . DIRECTORY_SEPARATOR . 'scpp.cmd';
		$content = "@echo off\r\n" .
			"php \"%~dp0scpp.php\" %*\r\n";
	}
	else {
		$launcherPath = $binDir . DIRECTORY_SEPARATOR . 'scpp';
		$content = "#!/usr/bin/env sh\n" .
			"php \"\$(CDPATH= cd -- \"\$(dirname -- \"\$0\")\" && pwd)/scpp.php\" \"\$@\"\n";
	}

	file_put_contents_or_throw($launcherPath, $content);

	if (PHP_OS_FAMILY !== 'Windows') {
		@chmod($launcherPath, 0755);
	}

	echo "Launcher installed: {$launcherPath}\n";
}

function write_config(string $repoRoot, string $binDir): void
{
	$configPath = $binDir . DIRECTORY_SEPARATOR . 'scpp.json';
	$config = [
		'repo_root' => $repoRoot,
		'installed_at' => date(DATE_ATOM),
		'install_mode' => 'repo-based-user-local',
		'version' => SCPP_VERSION,
	];

	$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	if (!is_string($json)) {
		throw new RuntimeException('Failed to encode installer config.');
	}

	file_put_contents_or_throw($configPath, $json . PHP_EOL);
	echo "Config written: {$configPath}\n";
}

function ensure_user_path_entry(string $binDir): void
{
	if (PHP_OS_FAMILY === 'Windows') {
		ensure_windows_user_path_entry($binDir);
		return;
	}

	ensure_posix_profile_path_entry($binDir);
}

function ensure_windows_user_path_entry(string $binDir): void
{
	$current = (string) getenv('PATH');
	if (path_contains_dir($current, $binDir)) {
		echo "PATH already contains launcher directory in current process\n";
	}

	$psDir = windows_path_for_powershell($binDir);
	$script = '$target = ' . single_quote_ps($psDir) . '; ' .
		'$existing = [Environment]::GetEnvironmentVariable("Path", "User"); ' .
		'if ([string]::IsNullOrWhiteSpace($existing)) { ' .
			'[Environment]::SetEnvironmentVariable("Path", $target, "User") ' .
		'} else { ' .
			'$parts = $existing -split ";" | ForEach-Object { $_.Trim() } | Where-Object { $_ -ne "" }; ' .
			'$normalizedTarget = $target.TrimEnd("\\"); ' .
			'$exists = $false; ' .
			'foreach ($part in $parts) { if ($part.TrimEnd("\\") -ieq $normalizedTarget) { $exists = $true; break } } ' .
			'if (-not $exists) { [Environment]::SetEnvironmentVariable("Path", ($existing.TrimEnd(";") + ";" + $target), "User") } ' .
		'}';

	$command = 'powershell -NoProfile -ExecutionPolicy Bypass -Command ' . escapeshellarg($script);
	exec($command, $output, $code);
	if ($code !== 0) {
		throw new RuntimeException('Failed to update the user PATH on Windows.');
	}

	echo "User PATH ensured: {$binDir}\n";
}

function ensure_posix_profile_path_entry(string $binDir): void
{
	$profiles = [
		'.profile',
		'.bash_profile',
		'.zprofile',
	];

	$home = dirname($binDir, 2);
	$snippet = SCPP_PROFILE_BEGIN . "\n" .
		'if [ -d "$HOME/.local/bin" ] && ! printf %s ":$PATH:" | grep -Fq ":$HOME/.local/bin:"; then' . "\n" .
		"\t" . 'export PATH="$HOME/.local/bin:$PATH"' . "\n" .
		'fi' . "\n" .
		SCPP_PROFILE_END . "\n";

	foreach ($profiles as $profileName) {
		$profilePath = $home . DIRECTORY_SEPARATOR . $profileName;
		$existing = is_file($profilePath) ? file_get_contents($profilePath) : '';
		if ($existing === false) {
			throw new RuntimeException("Failed to read profile: {$profilePath}");
		}

		if (str_contains($existing, SCPP_PROFILE_BEGIN) && str_contains($existing, SCPP_PROFILE_END)) {
			echo "Profile already configured: {$profilePath}\n";
			continue;
		}

		if ($existing !== '' && !str_ends_with($existing, "\n")) {
			$existing .= "\n";
		}

		file_put_contents_or_throw($profilePath, $existing . $snippet);
		echo "Profile updated: {$profilePath}\n";
	}

	$sessionPath = (string) getenv('PATH');
	if (!path_contains_dir($sessionPath, $binDir)) {
		$separator = $sessionPath === '' ? '' : PATH_SEPARATOR;
		putenv('PATH=' . $binDir . $separator . $sessionPath);
	}
}

function verify_install(string $binDir): void
{
	$commandPath = $binDir . DIRECTORY_SEPARATOR . (PHP_OS_FAMILY === 'Windows' ? 'scpp.cmd' : 'scpp');
	$command = PHP_OS_FAMILY === 'Windows'
		? 'cmd /c ' . escapeshellarg($commandPath) . ' --version'
		: escapeshellarg($commandPath) . ' --version';

	exec($command . ' 2>&1', $output, $code);
	if ($code !== 0) {
		throw new RuntimeException("Post-install verification failed:\n" . implode(PHP_EOL, $output));
	}

	echo "Verification OK: " . implode(' ', $output) . "\n";
}

function print_success_notes(string $repoRoot, string $binDir): void
{
	echo "\nDone.\n";
	echo "Repo root: {$repoRoot}\n";
	echo "User-local bin: {$binDir}\n";
	echo "This is a repo-based install for the current user only.\n";
	echo "If you move or delete the repo, run the installer again.\n";

	if (PHP_OS_FAMILY === 'Windows') {
		echo "Open a new terminal so updated PATH entries are picked up.\n";
	}
	else {
		echo "Open a new shell, or run: source ~/.profile\n";
	}
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
				throw new RuntimeException('Failed to create php.ini from php.ini-production');
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

	if (!is_string($content)) {
		throw new RuntimeException("Failed to update php.ini: {$phpIni}");
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

function path_contains_dir(string $pathValue, string $dir): bool
{
	$needle = normalize_path_for_compare($dir);
	if ($needle === '') {
		return false;
	}

	foreach (explode(PATH_SEPARATOR, $pathValue) as $part) {
		if (normalize_path_for_compare($part) === $needle) {
			return true;
		}
	}

	return false;
}

function normalize_path_for_compare(string $path): string
{
	$trimmed = trim($path);
	if ($trimmed === '') {
		return '';
	}

	$trimmed = rtrim($trimmed, "\\/");
	if (PHP_OS_FAMILY === 'Windows') {
		$trimmed = strtolower($trimmed);
	}

	return $trimmed;
}

function windows_path_for_powershell(string $path): string
{
	return str_replace('/', '\\', $path);
}

function single_quote_ps(string $value): string
{
	return "'" . str_replace("'", "''", $value) . "'";
}
