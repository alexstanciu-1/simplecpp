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
			$this->assertCompactLayoutScopedPacksIncludeValueDependencies();
			$this->assertTopLevelConstantScopedPackSafety();
			$this->assertMethodBodyScopedPackSafety();
			$this->assertBodyOnlyEditsKeepRebuildFanoutMinimal();
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
		$this->write($project . '/schema/kind.phs', <<<'PHS'
namespace App\Schema;

class Kind {
    public const ITEM = 7;
    public const LABEL = "item";
}
PHS);
		$this->write($project . '/orm/child_node.phs', <<<'PHS'
namespace App\Orm;

class ChildNode extends \App\Schema\BaseNode {
}
PHS);
		$this->write($project . '/config/defaults.phs', <<<'PHS'
namespace App\Config;

class Defaults {
    public const ITEM_KIND = \App\Schema\Kind::ITEM;
    public const ITEM_LABEL = "kind-" . \App\Schema\Kind::LABEL;
}
PHS);
		$this->write($project . '/contracts/sink.phs', <<<'PHS'
namespace App\Contracts;

interface Sink {
    public function accept(\App\Schema\Item $item): \App\Schema\BaseNode;
}
PHS);
		$this->write($project . '/helpers/factory.phs', <<<'PHS'
namespace App\Helpers;

function make_child(): \App\Orm\ChildNode {
    return new \App\Orm\ChildNode();
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
		$this->assertSame(9, $projectUnits['total_units'] ?? null, 'nested project should report nine generated units');
		$this->assertSame(8, $projectUnits['active_scoped_units'] ?? null, 'nested project should activate scoped packs for declaration-only, signature-only, property-layout, safe class constants, and safe helper units');
		$this->assertSame(1, $projectUnits['active_broad_fallback_units'] ?? null, 'nested project should keep only the executable unit broad');
		$this->assertSame(8, $projectUnits['candidate_scoped_units'] ?? null, 'nested project should report eight scoped candidates');
		$this->assertSame(1, $projectUnits['candidate_blocked_units'] ?? null, 'nested project should report one blocked candidate');

		$childSummary = $this->findSummary($projectUnits, 'orm/child_node.phs', '');
		$this->assertSame('scoped', $childSummary['status'] ?? null, 'inheritance-only child should compile with a scoped pack');
		$this->assertSame('candidate_scoped', $childSummary['candidate_status'] ?? null, 'inheritance-only child should be a scoped candidate');
		$this->assertSame(['schema/base_node.phs'], $childSummary['direct_source_dependencies'] ?? null, 'child should report its nested base source dependency');
		$this->assertSame(['.prism/generated/schema/base_node.hpp'], $childSummary['direct_local_headers'] ?? null, 'child should include the nested base header directly');
		$this->assertSame(['schema/base_node.phs'], $this->dependencyCategorySources($childSummary, 'inheritance'), 'child should categorize its base dependency as inheritance');

		$childPack = $project . '/' . (string) ($childSummary['candidate_pack_header'] ?? '');
		$childPackContents = $this->read($childPack);
		$this->assertOrderBefore('#include "../schema/base_node.hpp"', '#include "../orm/child_node.hpp"', $childPackContents, 'child scoped pack should include the base header before the child header');
		$this->assertNotContains('db/holder.hpp', $childPackContents, 'child scoped pack should not include unrelated property-layout headers');

		$sinkSummary = $this->findSummary($projectUnits, 'contracts/sink.phs', '');
		$this->assertSame('scoped', $sinkSummary['status'] ?? null, 'signature-only interface should compile with a scoped pack');
		$this->assertSame('candidate_scoped', $sinkSummary['candidate_status'] ?? null, 'signature-only interface should be a scoped candidate');
		$this->assertSame(['schema/base_node.phs', 'schema/item.phs'], $sinkSummary['direct_source_dependencies'] ?? null, 'method signatures should report direct type source dependencies');
		$this->assertSame(['.prism/generated/schema/base_node.hpp', '.prism/generated/schema/item.hpp'], $sinkSummary['direct_local_headers'] ?? null, 'method signatures should map direct type dependencies to generated headers');
		$this->assertSame(['schema/base_node.phs', 'schema/item.phs'], $this->dependencyCategorySources($sinkSummary, 'method signature'), 'method signatures should categorize direct type source dependencies');

		$factorySummary = $this->findSummary($projectUnits, 'helpers/factory.phs', '');
		$this->assertSame('scoped', $factorySummary['status'] ?? null, 'safe top-level helper body should compile with a scoped pack');
		$this->assertSame('candidate_scoped', $factorySummary['candidate_status'] ?? null, 'safe top-level helper body should be a scoped candidate');
		$this->assertSame(['orm/child_node.phs'], $factorySummary['direct_source_dependencies'] ?? null, 'safe helper should report its direct return/body type dependency');
		$this->assertSame(['.prism/generated/orm/child_node.hpp'], $factorySummary['direct_local_headers'] ?? null, 'safe helper direct local headers should stay direct');
		$this->assertSame(['.prism/generated/orm/child_node.hpp', '.prism/generated/schema/base_node.hpp'], $factorySummary['scoped_local_headers'] ?? null, 'safe helper scoped local headers should include transitive base dependencies');
		$this->assertSame(['orm/child_node.phs'], $this->dependencyCategorySources($factorySummary, 'function body'), 'safe helper should categorize constructed return type as a function-body dependency');
		$this->assertSame(['orm/child_node.phs'], $this->dependencyCategorySources($factorySummary, 'function signature'), 'safe helper should categorize declared return type as a function signature dependency');
		$factoryPack = $project . '/' . (string) ($factorySummary['candidate_pack_header'] ?? '');
		$factoryPackContents = $this->read($factoryPack);
		$this->assertOrderBefore('#include "../schema/base_node.hpp"', '#include "../orm/child_node.hpp"', $factoryPackContents, 'helper scoped pack should include child transitive base before child');
		$this->assertOrderBefore('#include "../orm/child_node.hpp"', '#include "../helpers/factory.hpp"', $factoryPackContents, 'helper scoped pack should include direct child before helper header');

		$holderSummary = $this->findSummary($projectUnits, 'db/holder.phs', '');
		$this->assertSame('scoped', $holderSummary['status'] ?? null, 'property-layout class should compile with a scoped pack once direct headers are modeled');
		$this->assertSame('candidate_scoped', $holderSummary['candidate_status'] ?? null, 'property-layout class should be a scoped candidate once direct headers are modeled');
		$this->assertSame(['schema/base_node.phs', 'schema/item.phs'], $holderSummary['direct_source_dependencies'] ?? null, 'property types should report direct source dependencies');
		$this->assertSame(['.prism/generated/schema/base_node.hpp', '.prism/generated/schema/item.hpp'], $holderSummary['direct_local_headers'] ?? null, 'property types should map direct type dependencies to generated headers');
		$this->assertSame(['schema/base_node.phs', 'schema/item.phs'], $this->dependencyCategorySources($holderSummary, 'property layout'), 'property types should categorize direct dependencies as layout-sensitive');
		$this->assertSame([], $holderSummary['candidate_blocking_reasons'] ?? null, 'resolved property-layout dependencies should not block scoped activation');
		$holderPack = $project . '/' . (string) ($holderSummary['candidate_pack_header'] ?? '');
		$holderPackContents = $this->read($holderPack);
		$this->assertOrderBefore('#include "../schema/base_node.hpp"', '#include "../db/holder.hpp"', $holderPackContents, 'property-layout scoped pack should include base node before holder');
		$this->assertOrderBefore('#include "../schema/item.hpp"', '#include "../db/holder.hpp"', $holderPackContents, 'property-layout scoped pack should include item before holder');

		$kindSummary = $this->findSummary($projectUnits, 'schema/kind.phs', '');
		$this->assertSame('scoped', $kindSummary['status'] ?? null, 'scalar class-constant file should compile with a scoped pack');
		$this->assertSame('candidate_scoped', $kindSummary['candidate_status'] ?? null, 'scalar class-constant file should be a scoped candidate');
		$this->assertSame([], $kindSummary['direct_source_dependencies'] ?? null, 'scalar class constants should not add direct source dependencies');
		$this->assertSame([], $kindSummary['candidate_blocking_reasons'] ?? null, 'scalar class constants should not block scoped activation');

		$defaultsSummary = $this->findSummary($projectUnits, 'config/defaults.phs', '');
		$this->assertSame('scoped', $defaultsSummary['status'] ?? null, 'resolved class-constant dependency file should compile with a scoped pack');
		$this->assertSame('candidate_scoped', $defaultsSummary['candidate_status'] ?? null, 'resolved class-constant dependency file should be a scoped candidate');
		$this->assertSame(['schema/kind.phs'], $defaultsSummary['direct_source_dependencies'] ?? null, 'class constant values should report direct source dependencies');
		$this->assertSame(['.prism/generated/schema/kind.hpp'], $defaultsSummary['direct_local_headers'] ?? null, 'class constant values should map direct dependencies to generated headers');
		$this->assertSame(['schema/kind.phs'], $this->dependencyCategorySources($defaultsSummary, 'class constant value'), 'class constant values should categorize direct dependencies');
		$this->assertSame(['App\Config\Defaults::ITEM_KIND', 'App\Config\Defaults::ITEM_LABEL'], $this->dependencyCategoryOwners($defaultsSummary, 'class constant value'), 'class constant values should preserve nested string-concat dependency owners');
		$this->assertSame([], $defaultsSummary['candidate_blocking_reasons'] ?? null, 'resolved class constant values should not block scoped activation');
		$defaultsPackContents = $this->read($project . '/' . (string) ($defaultsSummary['candidate_pack_header'] ?? ''));
		$this->assertOrderBefore('#include "../schema/kind.hpp"', '#include "../config/defaults.hpp"', $defaultsPackContents, 'class-constant scoped pack should include referenced class before owning class');
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

		$reuseBuild = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => false,
			'compile_dependencies' => false,
		]);
		$this->assertSame(true, $reuseBuild['ok'], 'dependency reuse-mode scoped-pack project should build after artifacts exist');
		$reuseProjectUnits = $this->loadProjectUnits($project);
		$reuseLocalSummary = $this->findSummary($reuseProjectUnits, 'local_node.phs', '');
		$this->assertSame('scoped', $reuseLocalSummary['status'] ?? null, 'dependency reuse-mode build should keep root scoped pack active');
		$this->assertSame(['../lib/.prism/generated/__project.hpp'], $reuseLocalSummary['dependency_export_headers'] ?? null, 'dependency reuse-mode build should still expose dependency project exports');
		$reuseReport = $this->loadLastRunReport($project);
		$reuseDetails = is_array($reuseReport['details'] ?? null) ? $reuseReport['details'] : [];
		$reuseExplanation = is_array($reuseDetails['build_explanation'] ?? null) ? $reuseDetails['build_explanation'] : [];
		$reuseDependencies = is_array($reuseExplanation['dependencies'] ?? null) ? $reuseExplanation['dependencies'] : [];
		$this->assertSame('reuse', $reuseDependencies['action'] ?? null, 'dependency reuse-mode build explanation should record dependency reuse');

		$clean = scpp_run_clean_service($project, $project . '/prism.json');
		$this->assertSame(true, $clean['ok'], 'dependency scoped-pack project clean should succeed');
		$this->assertFileMissing($project . '/.prism/generated/__project_units/manifest.json', 'clean should remove root project unit pack manifest');
		$this->assertFileMissing($dependency . '/.prism/generated/__project_units/manifest.json', 'clean should remove dependency project unit pack manifest');
		$cleanBuild = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'compile_dependencies' => true,
		]);
		$this->assertSame(true, $cleanBuild['ok'], 'clean dependency scoped-pack project should rebuild');
		$cleanProjectUnits = $this->loadProjectUnits($project);
		$cleanLocalSummary = $this->findSummary($cleanProjectUnits, 'local_node.phs', '');
		$this->assertSame('scoped', $cleanLocalSummary['status'] ?? null, 'clean rebuild should restore the root scoped pack');
		$this->assertSame(['../lib/.prism/generated/__project.hpp'], $cleanLocalSummary['dependency_export_headers'] ?? null, 'clean rebuild should restore dependency project exports in scoped packs');
	}

	private function assertCompactLayoutScopedPacksIncludeValueDependencies(): void
	{
		$project = $this->root . '/compact_layout_units';
		$this->writeProject($project, [], "echo \"compact\\n\";\n", 'strict');
		$this->write($project . '/kind.phs', <<<'PHS'
enum ExpressionKind : uint16 {
    case Error = 0;
    case Access = 1;
}
PHS);
		$this->write($project . '/span.phs', <<<'PHS'
struct CompactChildSpan {
    public uint32 $first_child_index = 0;
    public uint32 $child_count = 0;
}
PHS);
		$this->write($project . '/access_payload.phs', <<<'PHS'
struct ParsedAccessPayload {
    public uint32 $subject_id = 0;
    public uint32 $member_id = 0;
}
PHS);
		$this->write($project . '/payload.phs', <<<'PHS'
union ParsedExpressionPayload {
    public uint32 $name_id;
    public ParsedAccessPayload $access;
}
PHS);
		$this->write($project . '/record.phs', <<<'PHS'
struct CompactParsedExpressionRecord {
    public ExpressionKind $kind = ExpressionKind::Error;
    public CompactChildSpan $children;
    public ParsedExpressionPayload $payload;
}
PHS);
		$this->write($project . '/z_row.phs', <<<'PHS'
struct ZRow {
    public uint32 $id = 0;
}
PHS);
		$this->write($project . '/a_table.phs', <<<'PHS'
struct ATable {
    public $rows vector<ZRow> = [];
}
PHS);
		$this->write($project . '/table_consumer.phs', <<<'PHS'
class TableConsumer {
    public ATable $table;
}
PHS);

		$build = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'compile_dependencies' => true,
		]);
		$this->assertSame(true, $build['ok'], 'compact-layout scoped-pack project should build');

		$projectUnits = $this->loadProjectUnits($project);
		$this->assertSame(9, $projectUnits['total_units'] ?? null, 'compact-layout project should report nine generated units');
		$this->assertSame(8, $projectUnits['active_scoped_units'] ?? null, 'compact-layout declaration units should activate scoped packs');
		$this->assertSame(1, $projectUnits['active_broad_fallback_units'] ?? null, 'compact-layout executable unit should stay broad');
		$this->assertSame(8, $projectUnits['candidate_scoped_units'] ?? null, 'compact-layout declarations should be scoped candidates');
		$this->assertSame(1, $projectUnits['candidate_blocked_units'] ?? null, 'compact-layout main should be the only blocked candidate');

		$payloadSummary = $this->findSummary($projectUnits, 'payload.phs', '');
		$this->assertSame('scoped', $payloadSummary['status'] ?? null, 'union payload should compile with a scoped pack');
		$this->assertSame(['access_payload.phs'], $payloadSummary['direct_source_dependencies'] ?? null, 'union payload should report its nested struct dependency');
		$this->assertSame(['.prism/generated/access_payload.hpp'], $payloadSummary['direct_local_headers'] ?? null, 'union payload should direct-include its nested struct header');
		$this->assertSame(['access_payload.phs'], $this->dependencyCategorySources($payloadSummary, 'property layout'), 'union value fields should categorize dependencies as property layout');
		$payloadPackContents = $this->read($project . '/' . (string) ($payloadSummary['candidate_pack_header'] ?? ''));
		$this->assertOrderBefore('#include "../access_payload.hpp"', '#include "../payload.hpp"', $payloadPackContents, 'union scoped pack should include nested struct before union');

		$recordSummary = $this->findSummary($projectUnits, 'record.phs', '');
		$this->assertSame('scoped', $recordSummary['status'] ?? null, 'compact record should compile with a scoped pack');
		$this->assertSame(['kind.phs', 'payload.phs', 'span.phs'], $recordSummary['direct_source_dependencies'] ?? null, 'compact record should report enum, union, and struct direct dependencies');
		$this->assertSame(['.prism/generated/kind.hpp', '.prism/generated/payload.hpp', '.prism/generated/span.hpp'], $recordSummary['direct_local_headers'] ?? null, 'compact record direct headers should stay direct');
		$this->assertSame(['.prism/generated/access_payload.hpp', '.prism/generated/kind.hpp', '.prism/generated/payload.hpp', '.prism/generated/span.hpp'], $recordSummary['scoped_local_headers'] ?? null, 'compact record scoped headers should include transitive union payload dependencies before dependent headers');
		$this->assertSame(['kind.phs', 'payload.phs', 'span.phs'], $this->dependencyCategorySources($recordSummary, 'property layout'), 'compact record value fields should categorize direct dependencies as property layout');
		$recordPackContents = $this->read($project . '/' . (string) ($recordSummary['candidate_pack_header'] ?? ''));
		$this->assertOrderBefore('#include "../access_payload.hpp"', '#include "../payload.hpp"', $recordPackContents, 'record scoped pack should include transitive nested struct before union');
		$this->assertOrderBefore('#include "../kind.hpp"', '#include "../record.hpp"', $recordPackContents, 'record scoped pack should include enum before record');
		$this->assertOrderBefore('#include "../span.hpp"', '#include "../record.hpp"', $recordPackContents, 'record scoped pack should include child span before record');
		$this->assertOrderBefore('#include "../payload.hpp"', '#include "../record.hpp"', $recordPackContents, 'record scoped pack should include union before record');

		$tableConsumerSummary = $this->findSummary($projectUnits, 'table_consumer.phs', '');
		$this->assertSame('scoped', $tableConsumerSummary['status'] ?? null, 'container-table consumer should compile with a scoped pack');
		$this->assertSame(['a_table.phs'], $tableConsumerSummary['direct_source_dependencies'] ?? null, 'container-table consumer should report its direct table dependency');
		$this->assertSame(['.prism/generated/a_table.hpp', '.prism/generated/z_row.hpp'], $tableConsumerSummary['scoped_local_headers'] ?? null, 'container-table scoped headers should report the table and its transitive element row');
		$tableConsumerPackContents = $this->read($project . '/' . (string) ($tableConsumerSummary['candidate_pack_header'] ?? ''));
		$this->assertOrderBefore('#include "../z_row.hpp"', '#include "../a_table.hpp"', $tableConsumerPackContents, 'consumer scoped pack should include generic element row before the table header');
		$this->assertOrderBefore('#include "../a_table.hpp"', '#include "../table_consumer.hpp"', $tableConsumerPackContents, 'consumer scoped pack should include table before consumer');
	}

	private function assertTopLevelConstantScopedPackSafety(): void
	{
		$project = $this->root . '/top_level_constant_units';
		$this->writeProject($project, [], "echo \"constants\\n\";\n", 'strict');
		$this->write($project . '/kind.phs', <<<'PHS'
class Kind {
    public const LABEL = "item";
}
PHS);
		$this->write($project . '/safe_label.phs', <<<'PHS'
const SAFE_LABEL = "safe-" . Kind::LABEL;
PHS);
		$this->write($project . '/array_labels.phs', <<<'PHS'
const LABEL_FLAGS = [
    "dynamic" => true,
];
PHS);

		$build = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'compile_dependencies' => true,
		]);
		$this->assertSame(true, $build['ok'], 'top-level constant scoped-pack project should build');

		$projectUnits = $this->loadProjectUnits($project);
		$this->assertSame(4, $projectUnits['total_units'] ?? null, 'top-level constant project should report four generated units');
		$this->assertSame(2, $projectUnits['active_scoped_units'] ?? null, 'safe top-level constant and class-constant provider should activate scoped packs');
		$this->assertSame(2, $projectUnits['active_broad_fallback_units'] ?? null, 'executable and unmodeled top-level constant units should stay broad');
		$this->assertSame(2, $projectUnits['candidate_scoped_units'] ?? null, 'top-level constant project should report two scoped candidates');
		$this->assertSame(2, $projectUnits['candidate_blocked_units'] ?? null, 'top-level constant project should report two blocked candidates');

		$safeSummary = $this->findSummary($projectUnits, 'safe_label.phs', '');
		$this->assertSame('scoped', $safeSummary['status'] ?? null, 'safe top-level class-constant reference should compile with a scoped pack');
		$this->assertSame('candidate_scoped', $safeSummary['candidate_status'] ?? null, 'safe top-level constant should be a scoped candidate');
		$this->assertSame(['kind.phs'], $safeSummary['direct_source_dependencies'] ?? null, 'top-level constant values should report class-constant source dependencies');
		$this->assertSame(['.prism/generated/kind.hpp'], $safeSummary['direct_local_headers'] ?? null, 'top-level constant values should map class-constant dependencies to generated headers');
		$this->assertSame(['kind.phs'], $this->dependencyCategorySources($safeSummary, 'constant value'), 'top-level constants should categorize direct dependencies');
		$this->assertSame(['SAFE_LABEL'], $this->dependencyCategoryOwners($safeSummary, 'constant value'), 'top-level constant dependency rows should preserve the constant owner');
		$safePackContents = $this->read($project . '/' . (string) ($safeSummary['candidate_pack_header'] ?? ''));
		$this->assertOrderBefore('#include "../kind.hpp"', '#include "../safe_label.hpp"', $safePackContents, 'top-level constant scoped pack should include referenced class before owning constant header');

		$arraySummary = $this->findSummary($projectUnits, 'array_labels.phs', '');
		$this->assertSame('fallback_broad', $arraySummary['status'] ?? null, 'unmodeled top-level constant initializer should stay on broad fallback');
		$this->assertSame('blocked_broad_fallback', $arraySummary['candidate_status'] ?? null, 'unmodeled top-level constant initializer should block scoped candidacy');
		$this->assertContains('top-level constants contain unmodeled dependency evidence', implode("\n", is_array($arraySummary['candidate_blocking_reasons'] ?? null) ? $arraySummary['candidate_blocking_reasons'] : []), 'unmodeled top-level constant initializer should report a blocker');
	}

	private function assertMethodBodyScopedPackSafety(): void
	{
		$project = $this->root . '/method_body_units';
		$this->writeProject($project, [], "echo \"method\\n\";\n", 'strict');
		$this->write($project . '/row.phs', <<<'PHS'
struct MetricRow {
    public int32 $value = 0;
}
PHS);
		$this->write($project . '/report.phs', <<<'PHS'
class MetricReport {
    public MetricRow $row;
    public int $count = 0;
}
PHS);
		$this->write($project . '/ids.phs', <<<'PHS'
class MetricIds {
    public static function from_int(int $value): int {
        return $value;
    }
}
PHS);
		$this->write($project . '/metrics.phs', <<<'PHS'
class Metrics {
    public static function update(MetricReport &$report, MetricRow $row): int {
        $report->row = $row;
        $report->count = strlen("abc");
        if (MetricIds::from_int($report->count) === $row->value) {
            return $report->count + 1;
        }
        return $report->count;
    }
}
PHS);
		$this->write($project . '/layout_probe.phs', <<<'PHS'
class LayoutProbe {
    public static function bytes(): int {
        return layout_sizeof(MetricRow);
    }
}
PHS);
		$this->write($project . '/reader.phs', <<<'PHS'
class Reader {
    public static function content_or_empty(string $path): string {
        $text string = "";
        $err /** error */;
        if (take($text, $err, fs_get($path))) {
            return $text;
        }
        return "";
    }
}
PHS);
		$this->write($project . '/accumulator.phs', <<<'PHS'
class Accumulator {
    public static function count_to(int $limit): int {
        $value int = 0;
        while ($value < $limit) {
            $value = $value + 1;
        }
        return $value;
    }
}
PHS);
		$this->write($project . '/mixed_probe.phs', <<<'PHS'
class MixedProbe {
    public static function read_count(string $path): int {
        $text string = "";
        $err /** error */;
        if (!take($text, $err, fs_get($path))) {
            return 0;
        }
        $value int = 0;
        while ($value < 3) {
            $value = $value + 1;
        }
        return $value;
    }
}
PHS);

		$build = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'compile_dependencies' => true,
		]);
		$this->assertSame(
			true,
			$build['ok'],
			"method-body scoped-pack project should build\nSTDOUT:\n"
				. (string) ($build['output'] ?? '')
				. "\nSTDERR:\n"
				. (string) ($build['error'] ?? '')
		);

		$projectUnits = $this->loadProjectUnits($project);
		$this->assertSame(9, $projectUnits['total_units'] ?? null, 'method-body project should report nine generated units');
		$this->assertSame(8, $projectUnits['active_scoped_units'] ?? null, 'resolved method-body units should activate scoped packs');
		$this->assertSame(1, $projectUnits['active_broad_fallback_units'] ?? null, 'only executable unit should stay broad');
		$this->assertSame(8, $projectUnits['candidate_scoped_units'] ?? null, 'resolved method-body units should be scoped candidates');
		$this->assertSame(1, $projectUnits['candidate_blocked_units'] ?? null, 'only executable unit should be blocked');

		$metricsSummary = $this->findSummary($projectUnits, 'metrics.phs', '');
		$this->assertSame('scoped', $metricsSummary['status'] ?? null, 'resolved method body should compile with a scoped pack');
		$this->assertSame('candidate_scoped', $metricsSummary['candidate_status'] ?? null, 'resolved method body should be a scoped candidate');
		$this->assertSame([], $metricsSummary['candidate_blocking_reasons'] ?? null, 'resolved method body and runtime helper dependency should not block scoped activation');
		$this->assertSame([], $metricsSummary['unresolved_dependency_keys'] ?? null, 'strict runtime shallow dependency should not be reported as an unresolved project-header dependency');
		$this->assertSame(['ids.phs', 'report.phs', 'row.phs'], $metricsSummary['direct_source_dependencies'] ?? null, 'method body should report only project-header dependencies');
		$this->assertSame(['ids.phs'], $this->projectDependencyCategorySources($metricsSummary, 'method body'), 'method body should categorize resolved local body dependencies separately from runtime shallow helpers');

		$metricsPackContents = $this->read($project . '/' . (string) ($metricsSummary['candidate_pack_header'] ?? ''));
		$this->assertContains('#include "../ids.hpp"', $metricsPackContents, 'method-body scoped pack should include static-call dependency');
		$this->assertContains('#include "../report.hpp"', $metricsPackContents, 'method-body scoped pack should include parameter/property dependency');
		$this->assertContains('#include "../row.hpp"', $metricsPackContents, 'method-body scoped pack should include value-row dependency');
		$this->assertNotContains('runtime_symbols_strict', $metricsPackContents, 'method-body scoped pack should not include runtime shallow symbols as project headers');

		$layoutSummary = $this->findSummary($projectUnits, 'layout_probe.phs', '');
		$this->assertSame('scoped', $layoutSummary['status'] ?? null, 'layout probe method body should compile with a scoped pack');
		$this->assertSame(['row.phs'], $layoutSummary['direct_source_dependencies'] ?? null, 'layout probe should report the probed type as a body dependency');
		$this->assertSame(['row.phs'], $this->projectDependencyCategorySources($layoutSummary, 'method body'), 'layout probe should categorize its type operand as method-body dependency evidence');
		$layoutPackContents = $this->read($project . '/' . (string) ($layoutSummary['candidate_pack_header'] ?? ''));
		$this->assertContains('#include "../row.hpp"', $layoutPackContents, 'layout probe scoped pack should include the probed row header');

		$readerSummary = $this->findSummary($projectUnits, 'reader.phs', '');
		$this->assertSame('scoped', $readerSummary['status'] ?? null, 'runtime error slot method should compile with a scoped pack');
		$this->assertSame('candidate_scoped', $readerSummary['candidate_status'] ?? null, 'runtime error slot method should be a scoped candidate');
		$this->assertSame([], $readerSummary['candidate_blocking_reasons'] ?? null, 'runtime error slot dependency should not block scoped activation');
		$this->assertSame(['error'], $this->dependencyCategoryTargets($readerSummary, 'unresolved symbol'), 'runtime error slot should remain visible as unresolved runtime evidence');

		$accumulatorSummary = $this->findSummary($projectUnits, 'accumulator.phs', '');
		$this->assertSame('scoped', $accumulatorSummary['status'] ?? null, 'method local-invalidation body should compile with a scoped pack');
		$this->assertSame('candidate_scoped', $accumulatorSummary['candidate_status'] ?? null, 'method local-invalidation body should be a scoped candidate');
		$this->assertSame([], $accumulatorSummary['candidate_blocking_reasons'] ?? null, 'method local invalidations should not block scoped activation when dependency rows are otherwise safe');

		$mixedProbeSummary = $this->findSummary($projectUnits, 'mixed_probe.phs', '');
		$this->assertSame('scoped', $mixedProbeSummary['status'] ?? null, 'combined runtime-error and local-invalidation method should compile with a scoped pack');
		$this->assertSame('candidate_scoped', $mixedProbeSummary['candidate_status'] ?? null, 'combined runtime-error and local-invalidation method should be a scoped candidate');
		$this->assertSame([], $mixedProbeSummary['candidate_blocking_reasons'] ?? null, 'combined runtime-error and local-invalidation evidence should not block scoped activation');
	}

	private function assertBodyOnlyEditsKeepRebuildFanoutMinimal(): void
	{
		$project = $this->root . '/body_only_rebuild';
		$this->writeProject($project, [], <<<'PHS'
echo helper_value(), "\n";
$calculator = new Calculator();
echo $calculator->value(), "\n";
PHS, 'strict');
		$this->write($project . '/helper.phs', <<<'PHS'
function helper_value(): int {
    return 1;
}
PHS);
		$this->write($project . '/calculator.phs', <<<'PHS'
class Calculator {
    public function value(): int {
        return 10;
    }
}
PHS);

		$initialBuild = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'compile_dependencies' => true,
		]);
		$this->assertSame(true, $initialBuild['ok'], 'body-only rebuild project should build initially');
		$initialUnits = $this->loadProjectUnits($project);
		$calculatorSummary = $this->findSummary($initialUnits, 'calculator.phs', '');
		$this->assertSame('scoped', $calculatorSummary['status'] ?? null, 'method-only class should activate a scoped pack before body-edit measurement');
		$helperSummary = $this->findSummary($initialUnits, 'helper.phs', '');
		$this->assertSame('scoped', $helperSummary['status'] ?? null, 'top-level helper should activate a scoped pack before body-edit measurement');

		$this->write($project . '/helper.phs', <<<'PHS'
function helper_value(): int {
    return 2;
}
PHS);
		$helperEditBuild = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'compile_dependencies' => true,
		]);
		$this->assertSame(true, $helperEditBuild['ok'], 'top-level function body-only edit should rebuild successfully');
		$helperEditReport = $this->loadLastRunReport($project);
		$this->assertBodyOnlyFanout($helperEditReport, 1, 'top-level function body-only edit');

		$this->write($project . '/calculator.phs', <<<'PHS'
class Calculator {
    public function value(): int {
        return 11;
    }
}
PHS);
		$methodEditBuild = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'compile_dependencies' => true,
		]);
		$this->assertSame(true, $methodEditBuild['ok'], 'method body-only edit should rebuild successfully');
		$methodEditReport = $this->loadLastRunReport($project);
		$this->assertBodyOnlyFanout($methodEditReport, 1, 'method body-only edit');
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
		$report = $this->loadLastRunReport($project);
		$details = is_array($report['details'] ?? null) ? $report['details'] : [];
		$explanation = is_array($details['build_explanation'] ?? null) ? $details['build_explanation'] : [];
		$projectUnits = is_array($explanation['project_unit_force_includes'] ?? null) ? $explanation['project_unit_force_includes'] : [];
		if ($projectUnits === []) {
			throw new RuntimeException('last_run.json should contain project unit force-include details');
		}
		return $projectUnits;
	}

	/** @return array<string,mixed> */
	private function loadLastRunReport(string $project): array
	{
		$report = json_decode($this->read($project . '/.prism/last_run.json'), true);
		if (!is_array($report)) {
			throw new RuntimeException('last_run.json should decode as an object');
		}
		return $report;
	}

	private function assertBodyOnlyFanout(array $report, int $expectedGeneratedObjects, string $label): void
	{
		$details = is_array($report['details'] ?? null) ? $report['details'] : [];
		$fanout = is_array($details['rebuild_fanout'] ?? null) ? $details['rebuild_fanout'] : [];
		$this->assertSame(1, $details['transpiled_count'] ?? null, $label . ' should transpile only the edited source');
		$this->assertSame(2, $details['skipped_count'] ?? null, $label . ' should skip unchanged sources');
		$this->assertSame($expectedGeneratedObjects, $fanout['rebuilt_generated_object_count'] ?? null, $label . ' should rebuild only the edited generated object');
		$this->assertSame(0, $fanout['rebuilt_native_object_count'] ?? null, $label . ' should not rebuild native objects');
		$this->assertSame(0, $fanout['rebuilt_runtime_object_count'] ?? null, $label . ' should not rebuild runtime objects');
		$this->assertSame(0, $fanout['changed_project_unit_pack_count'] ?? null, $label . ' should not rewrite project-unit packs when headers are unchanged');
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

	/** @return list<string> */
	private function dependencyCategorySources(array $summary, string $category): array
	{
		$sources = [];
		foreach (is_array($summary['dependency_categories'] ?? null) ? $summary['dependency_categories'] : [] as $row) {
			if (!is_array($row) || ($row['category'] ?? null) !== $category) {
				continue;
			}
			foreach (is_array($row['source_dependencies'] ?? null) ? $row['source_dependencies'] : [] as $source) {
				$source = trim((string) $source);
				if ($source !== '') {
					$sources[$source] = true;
				}
			}
		}
		$result = array_keys($sources);
		sort($result, SORT_STRING);
		return $result;
	}

	/** @return list<string> */
	private function projectDependencyCategorySources(array $summary, string $category): array
	{
		$sources = [];
		foreach ($this->dependencyCategorySources($summary, $category) as $source) {
			if (!project_unit_dependency_key_requires_project_header_resolution($source)) {
				continue;
			}
			$sources[] = $source;
		}
		return $sources;
	}

	/** @return list<string> */
	private function dependencyCategoryOwners(array $summary, string $category): array
	{
		$owners = [];
		foreach (is_array($summary['dependency_categories'] ?? null) ? $summary['dependency_categories'] : [] as $row) {
			if (!is_array($row) || ($row['category'] ?? null) !== $category) {
				continue;
			}
			$owner = trim((string) ($row['owner'] ?? ''));
			if ($owner !== '') {
				$owners[$owner] = true;
			}
		}
		$result = array_keys($owners);
		sort($result, SORT_STRING);
		return $result;
	}

	/** @return list<string> */
	private function dependencyCategoryTargets(array $summary, string $category): array
	{
		$targets = [];
		foreach (is_array($summary['dependency_categories'] ?? null) ? $summary['dependency_categories'] : [] as $row) {
			if (!is_array($row) || ($row['category'] ?? null) !== $category) {
				continue;
			}
			$target = trim((string) ($row['target'] ?? ''));
			if ($target !== '') {
				$targets[$target] = true;
			}
		}
		$result = array_keys($targets);
		sort($result, SORT_STRING);
		return $result;
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

	private function assertFileMissing(string $path, string $message): void
	{
		if (file_exists($path)) {
			throw new RuntimeException($message . ' unexpected file `' . $path . '`');
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
