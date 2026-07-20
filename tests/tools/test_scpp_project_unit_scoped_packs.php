<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppProjectUnitScopedPacksTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_project_unit_scoped_packs_' . getmypid() . '_' . bin2hex(random_bytes(4)));
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
			$this->assertNestedNamespaceInheritanceScopedPacks();
			$this->assertDependencyScopedPacksUseExportHeaders();
			echo "PASS: scpp project unit scoped packs\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertNestedNamespaceInheritanceScopedPacks(): void
	{
		$project = $this->root . '/nested_units';
		$this->writeProject($project, [], "echo \"nested\\n\";\n", 'strict');
		$this->write($project . '/schema/base_node.phs', <<<'PHS'
namespace App\Schema;

class BaseNode {
}
PHS);
		$this->write($project . '/schema/item.phs', <<<'PHS'
namespace App\Schema;

class Item {
}
PHS);
		$this->write($project . '/orm/child_node.phs', <<<'PHS'
namespace App\Orm;

class ChildNode extends \App\Schema\BaseNode {
}
PHS);
		$this->write($project . '/contracts/sink.phs', <<<'PHS'
namespace App\Contracts;

interface Sink {
    public function accept(\App\Schema\Item $item): \App\Schema\BaseNode;
}
PHS);
		$this->write($project . '/db/holder.phs', <<<'PHS'
namespace App\Db;

class Holder {
    public \App\Schema\BaseNode $node;
    public \App\Schema\Item $item;
}
PHS);
		$this->write($project . '/main.phs', <<<'PHS'
$node = new App\Orm\ChildNode();
echo "ok\n";
PHS);

		$build = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'compile_dependencies' => true,
		]);
		$this->assertSame(true, $build['ok'], 'nested namespace scoped-pack project should build');

		$projectUnits = $this->loadProjectUnits($project);
		$this->assertSame(6, $projectUnits['total_units'] ?? null, 'nested project should report six generated units');
		$this->assertSame(4, $projectUnits['active_scoped_units'] ?? null, 'nested project should activate scoped packs for declaration-only and signature-only units');
		$this->assertSame(2, $projectUnits['active_broad_fallback_units'] ?? null, 'nested project should keep the executable and property-layout unit broad');
		$this->assertSame(4, $projectUnits['candidate_scoped_units'] ?? null, 'nested project should report four scoped candidates');
		$this->assertSame(2, $projectUnits['candidate_blocked_units'] ?? null, 'nested project should report two blocked candidates');

		$childSummary = $this->findSummary($projectUnits, 'orm/child_node.phs', '');
		$this->assertSame('scoped', $childSummary['status'] ?? null, 'inheritance-only child should compile with a scoped pack');
		$this->assertSame('candidate_scoped', $childSummary['candidate_status'] ?? null, 'inheritance-only child should be a scoped candidate');
		$this->assertSame(['schema/base_node.phs'], $childSummary['direct_source_dependencies'] ?? null, 'child should report its nested base source dependency');
		$this->assertSame(['.prism/generated/schema/base_node.hpp'], $childSummary['direct_local_headers'] ?? null, 'child should include the nested base header directly');

		$childPack = $project . '/' . (string) ($childSummary['candidate_pack_header'] ?? '');
		$childPackContents = $this->read($childPack);
		$this->assertOrderBefore('#include "../schema/base_node.hpp"', '#include "../orm/child_node.hpp"', $childPackContents, 'child scoped pack should include the base header before the child header');
		$this->assertNotContains('db/holder.hpp', $childPackContents, 'child scoped pack should not include unrelated broad-fallback headers');

		$sinkSummary = $this->findSummary($projectUnits, 'contracts/sink.phs', '');
		$this->assertSame('scoped', $sinkSummary['status'] ?? null, 'signature-only interface should compile with a scoped pack');
		$this->assertSame('candidate_scoped', $sinkSummary['candidate_status'] ?? null, 'signature-only interface should be a scoped candidate');
		$this->assertSame(['schema/base_node.phs', 'schema/item.phs'], $sinkSummary['direct_source_dependencies'] ?? null, 'method signatures should report direct type source dependencies');
		$this->assertSame(['.prism/generated/schema/base_node.hpp', '.prism/generated/schema/item.hpp'], $sinkSummary['direct_local_headers'] ?? null, 'method signatures should map direct type dependencies to generated headers');

		$holderSummary = $this->findSummary($projectUnits, 'db/holder.phs', '');
		$this->assertSame('fallback_broad', $holderSummary['status'] ?? null, 'property-layout class should stay on broad fallback');
		$this->assertSame('blocked_broad_fallback', $holderSummary['candidate_status'] ?? null, 'property-layout class should be blocked from scoped activation');
		$this->assertSame(['schema/base_node.phs', 'schema/item.phs'], $holderSummary['direct_source_dependencies'] ?? null, 'property types should report direct source dependencies even while layout remains broad');
		$this->assertSame(['.prism/generated/schema/base_node.hpp', '.prism/generated/schema/item.hpp'], $holderSummary['direct_local_headers'] ?? null, 'property types should map direct type dependencies to generated headers');
		$this->assertContains('class properties require complete-type dependency modeling', implode("\n", $holderSummary['candidate_blocking_reasons'] ?? []), 'property-layout blocker should be reported');
	}

	private function assertDependencyScopedPacksUseExportHeaders(): void
	{
		$dependency = $this->root . '/lib';
		$project = $this->root . '/app';
		$this->writeProject($dependency, [], "echo \"dependency\\n\";\n", 'strict');
		$this->write($dependency . '/base/node_base.phs', <<<'PHS'
namespace Vendor\Base;

/** @lib-export */
class NodeBase {
}
PHS);
		$this->write($dependency . '/base/node.phs', <<<'PHS'
namespace Vendor\Base;

/** @lib-export */
class Node extends NodeBase {
}
PHS);

		$this->writeProject($project, ['../lib'], "echo \"app\\n\";\n", 'strict');
		$this->write($project . '/local_node.phs', <<<'PHS'
namespace App;

class LocalNode extends \Vendor\Base\Node {
}
PHS);
		$this->write($project . '/main.phs', <<<'PHS'
$node = new App\LocalNode();
echo "ok\n";
PHS);

		$build = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'compile_dependencies' => true,
		]);
		$this->assertSame(true, $build['ok'], 'dependency scoped-pack project should build');

		$projectUnits = $this->loadProjectUnits($project);
		$localSummary = $this->findSummary($projectUnits, 'local_node.phs', '');
		$this->assertSame('scoped', $localSummary['status'] ?? null, 'root declaration-only dependency consumer should compile with a scoped pack');
		$this->assertSame('candidate_scoped', $localSummary['candidate_status'] ?? null, 'root declaration-only dependency consumer should be a scoped candidate');
		$this->assertSame([], $localSummary['direct_local_headers'] ?? null, 'root scoped pack should not direct-include dependency generated headers');
		$this->assertSame(['../lib/.prism/generated/__project.hpp'], $localSummary['dependency_export_headers'] ?? null, 'root scoped pack should include dependency project exports');

		$localCandidateHeaders = $localSummary['candidate_scoped_headers'] ?? [];
		$this->assertTrue(in_array('../lib/.prism/generated/__project.hpp', $localCandidateHeaders, true), 'root scoped candidate should list the dependency export header');
		$this->assertTrue(!in_array('../lib/.prism/generated/base/node.hpp', $localCandidateHeaders, true), 'root scoped candidate should not list a dependency generated header directly');

		$localPack = $project . '/' . (string) ($localSummary['candidate_pack_header'] ?? '');
		$localPackContents = $this->read($localPack);
		$this->assertContains('lib/.prism/generated/__project.hpp', $localPackContents, 'root scoped pack should include dependency export header');
		$this->assertNotContains('base/node.hpp', $localPackContents, 'root scoped pack should not include dependency generated node header directly');

		$dependencyNodeSummary = $this->findSummary($projectUnits, 'base/node.phs', '../lib');
		$this->assertSame('scoped', $dependencyNodeSummary['status'] ?? null, 'dependency project inheritance-only unit should get a scoped local pack');
		$this->assertSame(['../lib/.prism/generated/base/node_base.hpp'], $dependencyNodeSummary['direct_local_headers'] ?? null, 'dependency project unit should keep same-project direct headers');
	}

	/** @param list<string> $dependencies */
	private function writeProject(string $path, array $dependencies, string $source, string $phpProfile): void
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

	/** @return array<string,mixed> */
	private function loadProjectUnits(string $project): array
	{
		$report = json_decode($this->read($project . '/.prism/last_run.json'), true);
		if (!is_array($report)) {
			throw new RuntimeException('last_run.json should decode as an object');
		}
		$details = is_array($report['details'] ?? null) ? $report['details'] : [];
		$explanation = is_array($details['build_explanation'] ?? null) ? $details['build_explanation'] : [];
		$projectUnits = is_array($explanation['project_unit_force_includes'] ?? null) ? $explanation['project_unit_force_includes'] : [];
		if ($projectUnits === []) {
			throw new RuntimeException('last_run.json should contain project unit force-include details');
		}
		return $projectUnits;
	}

	/** @return array<string,mixed> */
	private function findSummary(array $projectUnits, string $source, string $projectRoot): array
	{
		$projectRoot = $projectRoot === '' ? '.' : $projectRoot;
		foreach (is_array($projectUnits['dependency_summaries'] ?? null) ? $projectUnits['dependency_summaries'] : [] as $summary) {
			if (!is_array($summary)) {
				continue;
			}
			if (($summary['source'] ?? null) === $source && ($summary['project_root'] ?? null) === $projectRoot) {
				return $summary;
			}
		}
		throw new RuntimeException('Missing project unit dependency summary for ' . ($projectRoot === '' ? $source : $projectRoot . '/' . $source));
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

	private function assertNotContains(string $needle, string $haystack, string $message): void
	{
		if (str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' contained `' . $needle . '`');
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
}

exit((new ScppProjectUnitScopedPacksTest())->run());
