<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

// Exercise the public command with a private shared cache; never remove or
// rebuild the developer's installed runtime artifacts to manufacture a miss.
if (PHP_OS_FAMILY !== 'Linux' || detect_default_compiler() === null || !resolve_runtime_curl_build_spec()['enabled']) {
	echo "SKIP: Linux compiler and libcurl development files required\n";
	exit(0);
}
$root = sys_get_temp_dir() . '/scpp_runtime_bundle_' . getmypid() . '_' . bin2hex(random_bytes(4));
$repo = resolve_repo_root();
$cacheRepo = $root . '/repo';
$project = $root . '/project';
$launcher = $root . '/cli/scpp.php';
foreach ([$cacheRepo, $project, dirname($launcher)] as $directory) {
	mkdir($directory, 0777, true);
}
function bundle_assert(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}
function bundle_remove_tree(string $path): void
{
	if (is_link($path) || !is_dir($path)) {
		if (file_exists($path) || is_link($path)) { unlink($path); }
		return;
	}
	foreach (scandir($path) ?: [] as $item) {
		if ($item !== '.' && $item !== '..') { bundle_remove_tree($path . '/' . $item); }
	}
	rmdir($path);
}
try {
	foreach (['runtime', 'generators', 'specs', 'bin'] as $directory) {
		bundle_assert(symlink($repo . '/' . $directory, $cacheRepo . '/' . $directory), 'Cannot link test repository sources');
	}
	file_put_contents(dirname($launcher) . '/scpp.json', json_encode(['repo_root' => $cacheRepo]));
	file_put_contents($launcher, "<?php\nrequire " . var_export($repo . '/bin/scpp.php', true) . ";\n");
	$config = ['entrypoint' => 'main.phs', 'runtime' => ['languages' => ['php' => ['profile' => 'strict']], 'modules' => ['json', 'filesystem', 'datetime']]];
	$writeConfig = static function () use ($project, &$config): void {
		file_put_contents($project . '/prism.json', json_encode($config));
	};
	$run = static function (array $args) use ($project, $launcher): array {
		$result = scpp_run_command_capture($project, [PHP_BINARY, $launcher, ...$args], ['SCPP_CXX' => '', 'SCPP_CXX_LAUNCHER' => ' '], 240.0);
		bundle_assert($result['exit_code'] === 0, implode(' ', $args) . " failed:\n" . $result['stdout'] . $result['stderr']);
		return $result;
	};
	$writeConfig();
	$run(['runtime-build']);
	$config['runtime']['modules'][] = 'curl';
	$writeConfig();
	$compiler = detect_default_compiler();
	$bundle = resolve_shared_runtime_bundle_specs($cacheRepo, $project, $compiler, 'debug', resolve_runtime_build_config($config));
	$base = normalize_path($project . '/' . $bundle['base']['artifact_path']);
	$module = normalize_path($project . '/' . $bundle['modules'][0]['artifact_path']);
	bundle_assert(is_file($base) && !is_file($module), 'Fixture must start with a reusable base and no curl artifact');
	$baseHash = hash_file('sha256', $base);
	$run(['runtime-build']);
	bundle_assert(is_file($module), 'runtime-build must build the missing curl artifact while reusing the base');
	bundle_assert(hash_file('sha256', $base) === $baseHash, 'Adding a module must not rewrite the base library');
	$run(['runtime-build']);
	file_put_contents($project . '/main.phs', <<<'PHS'
$handle curl_handle;
$err error;
if (take($handle, $err, curl_init("file:///missing"))) {
    echo "curl-ready\n";
}
PHS
	);
	$result = $run(['run', '--no-stan']);
	bundle_assert(str_contains($result['stdout'], "curl-ready\n"), 'Project must link and load the newly built curl artifact');
	bundle_assert(!is_dir($project . '/.prism/runtime/project'), 'Public rebuild followed by run should reuse shared artifacts');
	echo "PASS: scpp runtime-build shared bundle\n";
} finally {
	bundle_remove_tree($root);
}
