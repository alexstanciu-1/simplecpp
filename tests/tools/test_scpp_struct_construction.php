<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

use Scpp\S2S\Transpiler;
use Scpp\S2S\Stan\StanWorkspaceSession;
use Scpp\S2S\Lowering\TypeMapper;

final class ScppStructConstructionTest
{
	private string $root;

	public function run(): int
	{
		$this->root = sys_get_temp_dir() . '/scpp_struct_construction_' . getmypid() . '_' . bin2hex(random_bytes(4));
		try {
			$this->checkLowering();
			$this->checkProject();
			echo "PASS: struct construction values, diagnostics, and class ownership (#229)\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function checkLowering(): void
	{
		$mapper = new TypeMapper();
		$mapper->setDeclaredTypeKinds(['Models\\Row' => 'struct']);
		$this->assertSame('Models::Row', $mapper->mapDeclaredType('Models::Row'), 'stored inferred struct types must stay values');
		$path = $this->root . '/probe.phs';
		$this->write($path, '');
		foreach (['strict', 'legacy'] as $profile) {
			$transpiler = new Transpiler(phpProfile: $profile);
			$source = <<<'PHS'
struct Row { int32 $value = 7; }
class Box { public int32 $value = 7; }
$typed Row = new Row();
$inferred = new Row();
$copy Row = $inferred;
$object = new Box();
PHS;
			$output = $transpiler->transpile($path, sourceOverride: $source);
			$this->assertSame([], $output->errors, $profile . ' diagnostics');
			$cpp = implode("\n", $output->sourceLines);
			foreach (['Row typed = Row{};', 'auto inferred = Row{};', 'Row copy = inferred;', 'auto object = create<Box>();'] as $expected) {
				$this->assertContains($expected, $cpp, $profile . ' construction type');
			}
			foreach (['1', 'value: 1', '...$args'] as $args) {
				$this->expectRejection($transpiler, $path, 'struct Row { int32 $value = 7; } $row = new Row(' . $args . ');', 'accepts no arguments at line 1');
			}
			$this->expectRejection($transpiler, $path, 'struct Row { int32 $value = 7; } function make(): Row { return new Row(1); }', 'accepts no arguments');
			$this->expectRejection($transpiler, $path, 'struct Row { int32 $value = 7; } class Owner { public Row $row = new Row(1); }', 'accepts no arguments');
			foreach (['value<Row>', 'unique<Row>', 'shared<Row>'] as $wrapper) {
				$this->expectRejection($transpiler, $path, 'struct Row { int32 $value = 7; } $row /** ' . $wrapper . ' */ = new Row();', 'Ownership-wrapper construction of struct Row');
			}
			$transpiler->setDeclaredTypeKinds(['Models\\Row' => 'struct', 'Objects\\Row' => 'class']);
			$source = <<<'PHS'
namespace App {
    use Models\Row as Record;
    use Models\Row;
    use Objects\Row as ObjectRow;
    $typed Record = new Record();
    $inferred = new Record();
    $plain = new Row();
    $qualified = new \Models\Row();
    $object = new ObjectRow();
}
PHS;
			$output = $transpiler->transpile($path, sourceOverride: $source);
			$this->assertSame([], $output->errors, $profile . ' imported diagnostics');
			$cpp = implode("\n", $output->sourceLines);
			foreach (['Models::Row typed = Models::Row{};', 'auto inferred = Models::Row{};', 'auto plain = Models::Row{};', 'auto qualified = Models::Row{};', 'auto object = create<ObjectRow>();'] as $expected) {
				$this->assertContains($expected, $cpp, $profile . ' imported construction');
			}
			$this->expectRejection($transpiler, $path, 'use Models\\Row as Record; $row = new Record(1);', 'Struct construction for Models\\Row accepts no arguments');
		}
	}

	private function expectRejection(Transpiler $transpiler, string $path, string $source, string $message): void
	{
		try {
			$output = $transpiler->transpile($path, sourceOverride: $source);
			$diagnostic = implode("\n", $output->errors);
		} catch (\Scpp\S2S\Support\GenerationException $error) {
			$diagnostic = $error->getMessage();
		}
		$this->assertContains($message, $diagnostic, 'unsupported construction must fail with source diagnostic');
	}

	private function checkProject(): void
	{
		$project = $this->root . '/app';
		$this->write($project . '/prism.json', json_encode([
			'config_version' => 1, 'project_name' => 'struct_construction', 'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build', 'generated_dir' => '.prism/generated', 'cache_dir' => '.prism/cache',
			'dependencies' => [], 'libraries' => [], 'build' => ['backend' => 'ninja', 'mode' => 'debug'],
			'runtime' => ['languages' => ['php'], 'modules' => [], 'language_profiles' => ['php' => ['profile' => 'strict']]],
		], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
		$this->write($project . '/main.phs', <<<'PHS'
struct Row { int32 $value = 7; }
function pass(Row $row): Row { return $row; }
$typed Row = new Row();
$inferred = new Row();
$copy Row = pass($inferred);
$copy->value = 9;
echo $typed->value, $inferred->value, $copy->value;
PHS);
		$checked = (new StanWorkspaceSession())->runDiagnostics($project, $project . '/prism.json');
		$this->assertSame(0, $checked['warning_count'] ?? null, 'STAN typed/inferred struct values: ' . json_encode($checked['diagnostics'] ?? []));
		$this->write($project . '/model.phs', <<<'PHS'
namespace Models {
    struct Row { int32 $value = 7; string $text = "seed"; }
    function make_row(): Row { return new Row(); }
    function read_row(Row $row): int32 { return $row->value; }
}
namespace Objects {
    class Row { public int32 $value = 7; }
}
namespace Consumers {
    use Models\Row as Record;
    function make_alias(): Record { return new Record(); }
}
PHS);
		$this->write($project . '/main.phs', <<<'PHS'
use Models\Row as Record;
use Objects\Row as ObjectRow;
struct Local { int32 $value = 3; }
$local Local = new Local();
$localInferred = new Local();
$localInferred->value = 4;
echo $local->value, ":", $localInferred->value, "\n";
$typed Record = new Record();
$inferred = new Record();
$copy Record = $inferred;
$inferredCopy = $inferred;
$copy->value = 9;
$inferredCopy->value = 12;
$copy->text = "changed";
echo $typed->value, ":", $inferred->value, ":", $copy->value, ":", $inferred->text, ":", $inferredCopy->value, "\n";
$qualified = new \Models\Row();
$returned Record = Models\make_row();
echo $qualified->value, ":", $returned->value, ":", Models\read_row(new Record()), "\n";
$fromAlias Record = Consumers\make_alias();
echo $fromAlias->value, "\n";
$object = new ObjectRow();
$alias = $object;
$alias->value = 11;
echo $object->value, "\n";
PHS);
		$this->write($project . '/native_cpp/probe.cpp', <<<'CPP'
#include "model.hpp"
#include <type_traits>
static_assert(std::is_same_v<decltype(scpp::Models::make_row()), scpp::Models::Row>);
CPP);
		$diagnostics = (new StanWorkspaceSession())->runDiagnostics($project, $project . '/prism.json');
		// STAN currently reports advisory unresolved/ambiguous names for imports.
		// Keep the checked build enabled, and reject actual type/contract mismatches.
		foreach ($diagnostics['diagnostics'] ?? [] as $diagnostic) {
			$this->assertSame(true, in_array($diagnostic['code'] ?? '', [
				'stan.ambiguous_dependency', 'stan.unresolved_dependency',
				'stan.unresolved_property_read', 'stan.unresolved_property_write',
			], true), 'unexpected STAN construction diagnostic: ' . json_encode($diagnostic));
		}
		$build = scpp_run_build_service($project, $project . '/prism.json', ['compile_runtime' => true]);
		$this->assertSame(true, $build['ok'], 'native build: ' . ($build['output'] ?? '') . ($build['error'] ?? ''));
		$result = $build['result'];
		$run = $this->runBuiltProgram($project, $result['output_path'], $result['runtime_library_dir'] ?? null);
		$this->assertSame(0, $run['exit_code'], 'native execution: ' . $run['stderr']);
		$this->assertSame("3:4\n7:7:9:seed:12\n7:7:7\n7\n11\n", str_replace("\r\n", "\n", $run['stdout']), 'value copies and shared class identity');
		$cpp = $this->read($project . '/.prism/generated/main.cpp');
		$this->assertContains('auto inferred = Models::Row{};', $cpp, 'project must preserve inferred struct values');
		$this->assertSame(false, str_contains($cpp, 'create<Record>'), 'struct alias must not use shared creation');
	}

	/** @return array{stdout:string,stderr:string,exit_code:int|null} */
	private function runBuiltProgram(string $projectRoot, string $binaryPath, ?string $runtimeLibraryDir): array
	{
		$descriptor = [
			0 => ['file', 'php://stdin', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$processEnv = scpp_runtime_library_process_environment($runtimeLibraryDir);
		$processEnv['SCPP_ERROR_FORMAT'] = 'json';
		$process = proc_open([$binaryPath], $descriptor, $pipes, $projectRoot, scpp_build_process_environment($processEnv));
		if (!is_resource($process)) {
			throw new RuntimeException('Failed to start built program.');
		}
		$output = scpp_collect_process_output($process, $pipes);
		return [
			'stdout' => (string) ($output['stdout'] ?? ''),
			'stderr' => (string) ($output['stderr'] ?? ''),
			'exit_code' => is_int($output['status'] ?? null) ? (int) $output['status'] : null,
		];
	}

	private function mkdir(string $path): void
	{
		if (!is_dir($path) && !mkdir($path, 0777, true)) {
			throw new RuntimeException('Failed to create ' . $path);
		}
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

	private function removeTree(string $path): void
	{
		if (!is_dir($path)) {
			return;
		}
		$items = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($items as $item) {
			if ($item->isDir()) {
				@rmdir($item->getPathname());
			} else {
				@unlink($item->getPathname());
			}
		}
		@rmdir($path);
	}

	private function assertSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected !== $actual) {
			throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . '.');
		}
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' Missing `' . $needle . '`.');
		}
	}
}

exit((new ScppStructConstructionTest())->run());
