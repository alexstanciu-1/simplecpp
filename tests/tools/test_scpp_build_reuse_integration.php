<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppBuildReuseIntegrationTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_build_reuse_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		if (find_command_path(['ninja']) === null) {
			echo "SKIP: ninja not found\n";
			return 0;
		}
		if (resolve_compiler(['build' => []]) === null) {
			echo "SKIP: compiler not found\n";
			return 0;
		}

		try {
			$app = $this->root . '/app';
			$lib = $this->root . '/lib';
			$this->writeProject($lib, [], "function helper_value(): int { return 7; }\n");
			$this->writeProject($app, ['../lib'], "echo \"app\\n\";\n");

			$full = scpp_run_build_service($app, $app . '/prism.json', [
				'compile_runtime' => true,
				'compile_dependencies' => true,
			]);
			$this->assertSame(true, $full['ok'], 'initial full build should succeed');
			$this->assertContains('Runtime compilation: enabled', $full['output'], 'full build should report runtime compilation');
			$this->assertContains('Dependency compilation: enabled', $full['output'], 'full build should report dependency compilation');
			$this->assertBuildNinjaReusesPrebuiltRuntime($app);
			$this->assertConcurrentRuntimeBuildsAreSerialized();
			$this->assertDirectNinjaNoWork($app);
			$this->assertModeSeparatedBuildRoots();
			$this->assertSameProjectStrictUnitsComposeWithoutSourceIncludes();
			$this->assertSameProjectStrictNamespacedUnitsComposeBeforeIncludeOrder();
			$this->assertStrictProjectDependencyHeadersComposeBeforeLocalUnits();

			$depObject = $this->findDependencyObject($lib);
			$runtimeArtifact = $this->resolveRuntimeArtifactPath($app);
			$runtimeCompositionSource = $this->resolveRuntimeCompositionSourcePath($app);
			$this->assertFileExists($depObject, 'dependency object should exist after full build');
			$this->assertFileExists($runtimeArtifact, 'runtime artifact should exist after full build');
			$this->assertFileExists($runtimeCompositionSource, 'runtime composition source should exist after full build');
			$depBeforeReuse = $this->mtime($depObject);
			$runtimeBeforeReuse = $this->mtime($runtimeArtifact);
			$runtimeCompositionBeforeReuse = $this->mtime($runtimeCompositionSource);

			$this->sleepForTimestamp();
			$this->write($app . '/main.phs', "echo \"app reuse\\n\";\n");
			$reuse = scpp_run_build_service($app, $app . '/prism.json');
			$this->assertSame(true, $reuse['ok'], 'warm service build should succeed');
			$this->assertContains('Runtime compilation: reuse existing artifact only', $reuse['output'], 'service build should reuse runtime by default');
			$this->assertContains('Dependency compilation: reuse existing artifacts only', $reuse['output'], 'service build should reuse dependencies by default');
			$this->assertSame($depBeforeReuse, $this->mtime($depObject), 'dependency object should not rebuild in default reuse mode');
			$this->assertSame($runtimeBeforeReuse, $this->mtime($runtimeArtifact), 'runtime artifact should not rebuild in default reuse mode');
			$this->assertSame($runtimeCompositionBeforeReuse, $this->mtime($runtimeCompositionSource), 'runtime reuse should not rewrite shared runtime composition source');

			$this->sleepForTimestamp();
			$this->write($lib . '/main.phs', "function helper_value(): int { return 8; }\n");
			$depReuse = scpp_run_build_service($app, $app . '/prism.json');
			$this->assertSame(false, $depReuse['ok'], 'service build with changed dependency source should fail clearly in reuse mode');
			$this->assertContains('Dependency compilation is in reuse-only mode', $depReuse['error'] ?? '', 'reuse-mode failure should explain why dependency artifacts cannot be reused');
			$this->assertContains('Next: Re-run with --build-dependencies', $depReuse['error'] ?? '', 'reuse-mode failure should point users at the dependency rebuild flag');
			$this->assertSame($depBeforeReuse, $this->mtime($depObject), 'dependency object should remain untouched until dependency compilation is requested');

			$this->sleepForTimestamp();
			$depFull = scpp_run_build_service($app, $app . '/prism.json', parse_build_command_arguments(['--build-dependencies']));
			$this->assertSame(true, $depFull['ok'], 'full build should rebuild dependency artifacts');
			$this->assertContains('Dependency compilation: enabled', $depFull['output'], 'full build should re-enable dependency compilation');
			$depAfterFull = $this->mtime($depObject);
			$this->assertTrue($depAfterFull > $depBeforeReuse, 'dependency object should rebuild when full dependency compilation is requested');

			$this->sleepForTimestamp();
			$this->write($depObject, "not-an-object\n");
			$invalidDepReuse = scpp_run_build_service($app, $app . '/prism.json');
			$this->assertSame(false, $invalidDepReuse['ok'], 'reuse-mode build should fail clearly when a dependency object is invalid');
			$this->assertContains('reusable dependency artifacts are missing or stale', $invalidDepReuse['error'] ?? '', 'invalid dependency reuse should still be classified as a dependency artifact preflight problem');
			$this->assertContains('Invalid reusable dependency object', $invalidDepReuse['error'] ?? '', 'reuse-mode failure should identify the invalid dependency object directly');
			$this->assertContains('Next: Re-run with --build-dependencies', $invalidDepReuse['error'] ?? '', 'invalid dependency reuse should still point to the dependency rebuild flag');

			$this->sleepForTimestamp();
			$depRecovered = scpp_run_build_service($app, $app . '/prism.json', parse_build_command_arguments(['--build-dependencies']));
			$this->assertSame(true, $depRecovered['ok'], 'dependency rebuild should recover from an invalid cached dependency object');
			$this->assertContains('Dependency compilation: enabled', $depRecovered['output'], 'recovery build should report dependency compilation');
			$this->assertTrue($this->mtime($depObject) > $depAfterFull, 'recovery build should replace the invalid dependency object with a fresh native object');
			$this->assertTrue($this->read($depObject) !== "not-an-object\n", 'recovery build should not leave the invalid dependency payload in place');

			$this->sleepForTimestamp();
			unlink($runtimeArtifact);
			$runtimeReuse = scpp_run_build_service($app, $app . '/prism.json');
			$this->assertSame(false, $runtimeReuse['ok'], 'reuse-mode build should fail before Ninja when the runtime artifact is missing');
			$this->assertContains('Required runtime artifact is missing.', $runtimeReuse['error'] ?? '', 'reuse-mode failure should report the runtime cache problem first');
			$this->assertContains('reusing runtime artifacts by default', $runtimeReuse['error'] ?? '', 'reuse-mode failure should explain that build is reusing runtime artifacts');
			$this->assertContains('scpp runtime-build', $runtimeReuse['error'] ?? '', 'reuse-mode failure should suggest rebuilding the reusable runtime');
			$this->assertContains('scpp build --build-runtime', $runtimeReuse['error'] ?? '', 'reuse-mode failure should suggest the inline rebuild flag');
			$this->assertTrue(!str_contains($runtimeReuse['error'] ?? '', 'missing and no known rule to make it'), 'reuse-mode failure should not surface the raw Ninja missing-edge error first');
			$this->assertMissingRuntimeSummaryAvailable($app);

			$runtimeFull = scpp_run_build_service($app, $app . '/prism.json', [
				'compile_runtime' => true,
				'compile_dependencies' => false,
			]);
			$this->assertSame(true, $runtimeFull['ok'], 'runtime-only rebuild should succeed');
			$this->assertFileExists($runtimeArtifact, 'runtime artifact should be recreated when runtime compilation is requested');
			$this->assertTrue($this->mtime($runtimeArtifact) > $runtimeBeforeReuse, 'runtime artifact should rebuild when explicitly requested');

			echo "PASS: scpp build reuse integration\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function resolveRuntimeArtifactPath(string $projectRoot): string
	{
		$config = load_project_config($projectRoot . '/prism.json');
		$repoRoot = resolve_repo_root();
		$compiler = resolve_compiler($config);
		if ($compiler === null) {
			throw new RuntimeException('Compiler not available');
		}
		$runtimeConfig = is_array($config['runtime'] ?? null) ? $config['runtime'] : resolve_runtime_build_config($config);
		$runtimeBuild = build_runtime_artifact_spec($repoRoot, $projectRoot, $compiler, resolve_build_mode($config), $runtimeConfig);
		return normalize_path($projectRoot . '/' . normalize_config_path($runtimeBuild['artifact_path']));
	}

	private function resolveRuntimeCompositionSourcePath(string $projectRoot): string
	{
		$config = load_project_config($projectRoot . '/prism.json');
		$repoRoot = resolve_repo_root();
		$compiler = resolve_compiler($config);
		if ($compiler === null) {
			throw new RuntimeException('Compiler not available');
		}
		$runtimeConfig = is_array($config['runtime'] ?? null) ? $config['runtime'] : resolve_runtime_build_config($config);
		$runtimeBuild = build_runtime_artifact_spec($repoRoot, $projectRoot, $compiler, resolve_build_mode($config), $runtimeConfig);
		return normalize_path($projectRoot . '/' . normalize_config_path($runtimeBuild['source_path']));
	}

	private function assertMissingRuntimeSummaryAvailable(string $projectRoot): void
	{
		$command = [PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'];
		$descriptor = [
			0 => ['file', '/dev/null', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = proc_open($command, $descriptor, $pipes, $projectRoot, scpp_build_process_environment([
			'SCPP_CXX_LAUNCHER' => ' ',
		]));
		if (!is_resource($process)) {
			throw new RuntimeException('Failed to start scpp error for missing runtime artifact summary');
		}
		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$exitCode = proc_close($process);
		$stdout = is_string($stdout) ? $stdout : '';
		$stderr = is_string($stderr) ? $stderr : '';
		$this->assertSame(0, $exitCode, "scpp error should succeed for saved missing-runtime diagnostics:\n" . $stderr);
		$this->assertContains('Category: runtime_cache / missing_runtime_artifact', $stdout, 'saved summary should preserve the preflight runtime-cache category');
		$this->assertContains('Required runtime artifact is missing.', $stdout, 'saved summary should preserve the short missing-runtime message');
	}

	private function assertDirectNinjaNoWork(string $projectRoot): void
	{
		$buildDir = $projectRoot . '/.prism/build';
		$buildFile = $buildDir . '/build.ninja';
		$command = [
			'ninja',
			'-C',
			$buildDir,
			'-f',
			basename($buildFile),
			'-d',
			'explain',
			'main',
		];
		$descriptor = [
			0 => ['file', 'php://stdin', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = proc_open($command, $descriptor, $pipes, $projectRoot);
		if (!is_resource($process)) {
			throw new RuntimeException('Failed to start direct ninja check');
		}
		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$status = proc_close($process);
		$output = (is_string($stdout) ? $stdout : '') . (is_string($stderr) ? $stderr : '');
		if ($status !== 0) {
			throw new RuntimeException("Direct ninja check failed:\n" . $output);
		}
		if (!str_contains($output, 'no work to do')) {
			throw new RuntimeException("Direct ninja check was expected to be warm and idle, got:\n" . $output);
		}
		if (str_contains($output, 'missing and no known rule to make it')) {
			throw new RuntimeException("Direct ninja check still reports a missing dependency edge:\n" . $output);
		}
	}

	private function assertModeSeparatedBuildRoots(): void
	{
		$project = $this->root . '/mode_roots';
		$this->writeProject($project, [], "echo \"mode\\n\";\n");

		$debug = scpp_run_build_service($project, $project . '/prism.json', parse_build_command_arguments(['--mode=debug', '--build-runtime']));
		$this->assertSame(true, $debug['ok'], 'debug mode build should succeed');
		$debugResult = is_array($debug['result'] ?? null) ? $debug['result'] : [];
		$this->assertSame(normalize_path($project . '/.prism/build/debug'), normalize_path((string) ($debugResult['build_dir'] ?? '')), 'debug mode should use the debug build root');
		$this->assertFileExists($project . '/.prism/build/debug/main', 'debug mode executable should be under the debug build root');
		$this->assertFileExists($project . '/.prism/generated/debug/main.cpp', 'debug mode generated source should be under the debug generated root');
		$debugExplanation = is_array($debugResult['build_explanation'] ?? null) ? $debugResult['build_explanation'] : [];
		$this->assertSame('debug', $debugExplanation['build_mode'] ?? null, 'debug build explanation should record the active mode');
		$debugRoots = is_array($debugExplanation['build_roots'] ?? null) ? $debugExplanation['build_roots'] : [];
		$this->assertSame('.prism/build/debug', $debugRoots['build_dir'] ?? null, 'debug build explanation should record the selected build root');
		$debugOutputMtime = $this->mtime($project . '/.prism/build/debug/main');

		$this->sleepForTimestamp();
		$release = scpp_run_build_service($project, $project . '/prism.json', parse_build_command_arguments(['--mode=release', '--build-runtime']));
		$this->assertSame(true, $release['ok'], 'release mode build should succeed');
		$releaseResult = is_array($release['result'] ?? null) ? $release['result'] : [];
		$this->assertSame(normalize_path($project . '/.prism/build/release'), normalize_path((string) ($releaseResult['build_dir'] ?? '')), 'release mode should use the release build root');
		$this->assertFileExists($project . '/.prism/build/release/main', 'release mode executable should be under the release build root');
		$this->assertFileExists($project . '/.prism/generated/release/main.cpp', 'release mode generated source should be under the release generated root');
		$this->assertSame($debugOutputMtime, $this->mtime($project . '/.prism/build/debug/main'), 'release mode build should not touch the debug executable');
		$releaseOutputMtime = $this->mtime($project . '/.prism/build/release/main');

		$this->sleepForTimestamp();
		$debugAgain = scpp_run_build_service($project, $project . '/prism.json', parse_build_command_arguments(['--mode=debug']));
		$this->assertSame(true, $debugAgain['ok'], 'warm debug mode build should succeed after release mode build');
		$this->assertSame($releaseOutputMtime, $this->mtime($project . '/.prism/build/release/main'), 'debug mode rebuild should not touch the release executable');
	}

	private function assertBuildNinjaReusesPrebuiltRuntime(string $projectRoot): void
	{
		$buildFile = $projectRoot . '/.prism/build/build.ninja';
		$this->assertFileExists($buildFile, 'build.ninja should exist after build');
		$contents = $this->read($buildFile);
		$this->assertTrue(!str_contains($contents, 'rule compile_runtime'), 'project-local build.ninja should reuse the prebuilt shared runtime instead of compiling it directly');
		$this->assertTrue(!str_contains($contents, 'rule link_runtime_shared'), 'project-local build.ninja should not link the shared runtime directly after prebuild');
	}

	private function assertConcurrentRuntimeBuildsAreSerialized(): void
	{
		$root = $this->root . '/parallel_runtime_builds';
		$projectA = $root . '/vector_unset_repro';
		$projectB = $root . '/hash_unset_repro';
		$this->writeProject($projectA, [], <<<'PHS'
$items vector<string> = ["a", "b", "c"];
unset($items[1]);
echo "count=", count($items), "\n";
PHS, 'strict');
		$this->writeProject($projectB, [], <<<'PHS'
$items hash<int> = [];
$items["a"] = 10;
$items["b"] = 20;
unset($items["a"]);
echo "has_a=", isset($items["a"]) ? "yes" : "no", "\n";
echo "has_b=", isset($items["b"]) ? "yes" : "no", "\n";
PHS, 'strict');

		$buildScript = resolve_repo_root() . '/bin/scpp.php';
		$command = [PHP_BINARY, $buildScript, 'build', '--build-runtime', '--build-dependencies'];

		$runA = $this->startParallelBuild($command, $projectA);
		$runB = $this->startParallelBuild($command, $projectB);
		$resultA = $this->finishParallelBuild($runA);
		$resultB = $this->finishParallelBuild($runB);

		$this->assertSame(0, $resultA['exit_code'], "parallel runtime build A should succeed:\n" . $resultA['combined']);
		$this->assertSame(0, $resultB['exit_code'], "parallel runtime build B should succeed:\n" . $resultB['combined']);
		$this->assertTrue(!str_contains($resultA['combined'], 'PHP Warning'), 'parallel runtime build A should not leak PHP warnings while coordinating shared runtime creation');
		$this->assertTrue(!str_contains($resultB['combined'], 'PHP Warning'), 'parallel runtime build B should not leak PHP warnings while coordinating shared runtime creation');
		$this->assertBuildNinjaReusesPrebuiltRuntime($projectA);
		$this->assertBuildNinjaReusesPrebuiltRuntime($projectB);
	}

	/** @param list<string> $command @return array{process:resource,pipes:array<int,resource>} */
	private function startParallelBuild(array $command, string $cwd): array
	{
		$descriptor = [
			0 => ['file', 'php://stdin', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = proc_open($command, $descriptor, $pipes, $cwd, scpp_build_process_environment());
		if (!is_resource($process)) {
			throw new RuntimeException('Failed to start parallel runtime build in ' . $cwd);
		}
		return ['process' => $process, 'pipes' => $pipes];
	}

	/** @param array{process:resource,pipes:array<int,resource>} $run @return array{exit_code:int,stdout:string,stderr:string,combined:string} */
	private function finishParallelBuild(array $run): array
	{
		$stdout = stream_get_contents($run['pipes'][1]);
		$stderr = stream_get_contents($run['pipes'][2]);
		fclose($run['pipes'][1]);
		fclose($run['pipes'][2]);
		$exitCode = proc_close($run['process']);
		$stdout = is_string($stdout) ? $stdout : '';
		$stderr = is_string($stderr) ? $stderr : '';
		return [
			'exit_code' => is_int($exitCode) ? $exitCode : 1,
			'stdout' => $stdout,
			'stderr' => $stderr,
			'combined' => $stdout . $stderr,
		];
	}

	private function findDependencyObject(string $projectRoot): string
	{
		$buildDir = $projectRoot . '/.prism/build';
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($buildDir, FilesystemIterator::SKIP_DOTS)
		);
		foreach ($iterator as $item) {
			if (!$item->isFile()) {
				continue;
			}
			$path = normalize_path($item->getPathname());
			if (str_ends_with($path, '.o') && !str_contains($path, 'pch')) {
				return $path;
			}
		}
		throw new RuntimeException('Failed to locate dependency object in ' . $buildDir);
	}

	private function assertSameProjectStrictUnitsComposeWithoutSourceIncludes(): void
	{
		$project = $this->root . '/strict_units';
		$this->writeProject($project, [], "echo \"placeholder\\n\";\n", 'strict');
		$this->write($project . '/model.phs', <<<'PHS'
class Model {
    public string $name = "";
}
PHS);
		$this->write($project . '/main.phs', <<<'PHS'
$m = new Model();
$m->name = "ok";
echo $m->name, "\n";
PHS);

		$build = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'compile_dependencies' => true,
		]);
		$this->assertSame(true, $build['ok'], 'strict same-project units should build without source-level generated-header includes');

		$unitHeader = $project . '/.prism/generated/__project_units.hpp';
		$unitPackHeaders = glob($project . '/.prism/generated/__project_units/*.hpp');
		$unitPackHeaders = is_array($unitPackHeaders) ? $unitPackHeaders : [];
		$buildFile = $project . '/.prism/build/build.ninja';
		$this->assertFileExists($unitHeader, 'project unit force-include header should be generated');
		$this->assertTrue($unitPackHeaders !== [], 'broad-equivalent project unit pack header should be generated');
		$this->assertFileExists($buildFile, 'strict same-project build should emit build.ninja');
		$this->assertContains('#include "model.hpp"', $this->read($unitHeader), 'project unit header should include same-project generated model header');
		$this->assertContains('-include ../generated/__project_units/', $this->read($buildFile), 'generated unit compile edges should force-include the broad-equivalent project unit pack header');
	}

	private function assertSameProjectStrictNamespacedUnitsComposeBeforeIncludeOrder(): void
	{
		$project = $this->root . '/strict_namespaced_units';
		$this->writeProject($project, [], "echo \"placeholder\\n\";\n", 'strict');
		$this->mkdir($project . '/db');
		$this->mkdir($project . '/orm');
		$this->mkdir($project . '/schema');
		$this->write($project . '/db/holder.phs', <<<'PHS'
namespace App\Db;

class Holder {
    public \App\Schema\Item $item;
}
PHS);
		$this->write($project . '/orm/child_node.phs', <<<'PHS'
namespace App\Orm;

class ChildNode extends \App\Schema\BaseNode {
}
PHS);
		$this->write($project . '/schema/base_node.phs', <<<'PHS'
namespace App\Schema;

class BaseNode {
}
PHS);
		$this->write($project . '/schema/item.phs', <<<'PHS'
namespace App\Schema;

class Item {
    public string $name = "";
}
PHS);
		$this->write($project . '/main.phs', <<<'PHS'
$i = new App\Schema\Item();
$i->name = "ok";
echo $i->name, "\n";
PHS);

		$build = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'compile_dependencies' => true,
		]);
		$this->assertSame(true, $build['ok'], 'strict namespaced same-project units should build without generated-header source includes');

		$forwardHeader = $project . '/.prism/generated/__project_fwd.hpp';
		$unitHeader = $project . '/.prism/generated/__project_units.hpp';
		$this->assertFileExists($forwardHeader, 'project forward declaration header should be generated');
		$this->assertContains('namespace scpp::App::Schema {', $this->read($forwardHeader), 'forward header should declare nested source namespaces');
		$this->assertContains('class Item;', $this->read($forwardHeader), 'forward header should declare referenced cross-unit classes');
		$this->assertContains('#include "__project_fwd.hpp"', $this->read($unitHeader), 'project unit header should include project forward declarations before generated headers');
		$this->assertOrderBefore('#include "schema/base_node.hpp"', '#include "orm/child_node.hpp"', $this->read($unitHeader), 'base-class header should be included before derived-class header');
	}

	private function assertStrictProjectDependencyHeadersComposeBeforeLocalUnits(): void
	{
		$dependency = $this->root . '/strict_dependency_export';
		$project = $this->root . '/strict_dependency_consumer';
		$this->writeProject($dependency, [], "echo \"dependency\\n\";\n", 'strict');
		$this->mkdir($dependency . '/base');
		$this->write($dependency . '/base/node_base.phs', <<<'PHS'
namespace Vendor\Base;

/** @lib-export */
class NodeBase {
    public string $name = "";
}
PHS);
		$this->write($dependency . '/base/node.phs', <<<'PHS'
namespace Vendor\Base;

/** @lib-export */
class Node extends NodeBase {
}
PHS);
		$standaloneDependencyBuild = scpp_run_build_service($dependency, $dependency . '/prism.json', [
			'compile_runtime' => true,
			'compile_dependencies' => true,
		]);
		$this->assertSame(true, $standaloneDependencyBuild['ok'], 'strict dependency should build standalone before it is reused as a dependency');

		$this->writeProject($project, ['../strict_dependency_export'], "echo \"consumer\\n\";\n", 'strict');
		$this->write($project . '/consumer.phs', <<<'PHS'
namespace App;

class Reporter {
    public static function printNode(\Vendor\Base\Node $node): void {
        echo $node->name, "\n";
    }
}
PHS);
		$this->write($project . '/main.phs', <<<'PHS'
$node = new Vendor\Base\Node();
$node->name = "ok";
App\Reporter::printNode($node);
PHS);

		$build = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'compile_dependencies' => true,
		]);
		$this->assertSame(true, $build['ok'], 'strict dependency project exports should be visible before local generated headers');

		$dependencyProjectHeader = $dependency . '/.prism/generated/__project.hpp';
		$unitHeader = $project . '/.prism/generated/__project_units.hpp';
		$this->assertFileExists($dependencyProjectHeader, 'dependency project export header should be generated');
		$this->assertOrderBefore('#include "base/node_base.hpp"', '#include "base/node.hpp"', $this->read($dependencyProjectHeader), 'dependency project export header should include base headers before derived headers');
		$this->assertOrderBefore('#include "../../../strict_dependency_export/.prism/generated/__project.hpp"', '#include "consumer.hpp"', $this->read($unitHeader), 'dependency project header should be included before local generated headers');
	}

	private function sleepForTimestamp(): void
	{
		usleep(1200000);
		clearstatcache();
	}

	/** @param list<string> $dependencies */
	private function writeProject(string $path, array $dependencies, string $source, string $phpProfile = 'legacy'): void
	{
		$this->mkdir($path);
		$this->mkdir($path . '/native_cpp');
		$this->write($path . '/main.phs', $source);
		$config = [
			'config_version' => 1,
			'project_name' => basename($path),
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
			'native_cpp_dir' => 'native_cpp',
			'dependencies' => $dependencies,
			'libraries' => [],
			'build' => [
				'backend' => 'ninja',
				'cxx' => null,
				'mode' => 'debug',
			],
			'runtime' => [
				'languages' => ['php'],
				'modules' => ['json', 'filesystem'],
				'language_profiles' => [
					'php' => ['profile' => $phpProfile],
				],
			],
		];
		$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		if (!is_string($json)) {
			throw new RuntimeException('Failed to encode prism.json');
		}
		$this->write($path . '/prism.json', $json . PHP_EOL);
	}

	private function mtime(string $path): int
	{
		clearstatcache(true, $path);
		$mtime = filemtime($path);
		if (!is_int($mtime)) {
			throw new RuntimeException('Failed to read mtime for ' . $path);
		}
		return $mtime;
	}

	private function write(string $path, string $contents): void
	{
		$dir = dirname($path);
		if (!is_dir($dir)) {
			$this->mkdir($dir);
		}
		if (file_put_contents($path, $contents) === false) {
			throw new RuntimeException('Failed to write ' . $path);
		}
	}

	private function read(string $path): string
	{
		$contents = file_get_contents($path);
		if (!is_string($contents)) {
			throw new RuntimeException('Failed to read ' . $path);
		}
		return $contents;
	}

	private function mkdir(string $path): void
	{
		if (!is_dir($path) && !mkdir($path, 0777, true)) {
			throw new RuntimeException('Failed to create ' . $path);
		}
	}

	private function removeTree(string $path): void
	{
		if (!is_dir($path)) {
			return;
		}
		$items = scandir($path);
		if ($items === false) {
			return;
		}
		foreach ($items as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}
			$child = $path . '/' . $item;
			if (is_dir($child) && !is_link($child)) {
				$this->removeTree($child);
				continue;
			}
			unlink($child);
		}
		rmdir($path);
	}

	private function assertSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected !== $actual) {
			throw new RuntimeException($message . ' expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
		}
	}

	private function assertTrue(bool $condition, string $message): void
	{
		if (!$condition) {
			throw new RuntimeException($message);
		}
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' missing `' . $needle . '`');
		}
	}

	private function assertOrderBefore(string $left, string $right, string $haystack, string $message): void
	{
		$leftPos = strpos($haystack, $left);
		$rightPos = strpos($haystack, $right);
		if (!is_int($leftPos) || !is_int($rightPos) || $leftPos >= $rightPos) {
			throw new RuntimeException($message . ' expected `' . $left . '` before `' . $right . '`');
		}
	}

	private function assertFileExists(string $path, string $message): void
	{
		if (!is_file($path)) {
			throw new RuntimeException($message . ': ' . $path);
		}
	}
}

exit((new ScppBuildReuseIntegrationTest())->run());
