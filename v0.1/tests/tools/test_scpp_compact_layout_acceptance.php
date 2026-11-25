<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppCompactLayoutAcceptanceTest
{
	private const READABLE_PARSED_EXPRESSION_RECORD_BYTES = 296;

	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_compact_layout_acceptance_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		try {
			if (find_command_path(['ninja']) === null || resolve_compiler(['build' => []]) === null) {
				echo "SKIP: scpp compact layout acceptance (native compiler unavailable)\n";
				return 0;
			}

			$project = $this->root . '/app';
			$this->mkdir($project . '/native_cpp');
			$this->write($project . '/model.phs', implode("\n", [
				'enum ExpressionKind : uint16 {',
				'	case Error = 0;',
				'	case Variable = 1;',
				'	case IntLiteral = 2;',
				'	case BinaryOperator = 3;',
				'	case Call = 4;',
				'	case CandidateAccessCall = 5;',
				'}',
				'struct CompactChildSpan {',
				'	public uint32 $first_child_index = 0;',
				'	public uint32 $child_count = 0;',
				'}',
				'struct ParsedAccessPayload {',
				'	public uint32 $subject_id = 0;',
				'	public uint32 $member_id = 0;',
				'	public uint16 $operator_id = 0;',
				'}',
				'union ParsedExpressionPayload {',
				'	public uint32 $name_id;',
				'	public uint32 $literal_id;',
				'	public uint32 $operator_id;',
				'	public uint32 $callee_id;',
				'	public ParsedAccessPayload $access;',
				'}',
				'struct CompactParsedExpressionRecord {',
				'	public ExpressionKind $kind = ExpressionKind::Error;',
				'	public uint32 $source_range_id = 0;',
				'	public CompactChildSpan $children;',
				'	public ParsedExpressionPayload $payload;',
				'	public uint16 $flags = 0;',
				'}',
				'',
			]));
			$this->write($project . '/main.phs', implode("\n", [
				'$record_size int = layout_sizeof(CompactParsedExpressionRecord);',
				'$record_align int = layout_alignof(CompactParsedExpressionRecord);',
				'$payload_offset int = layout_offsetof(CompactParsedExpressionRecord, payload);',
				'$payload_size int = layout_field_sizeof(CompactParsedExpressionRecord, payload);',
				'$access_size int = layout_sizeof(ParsedAccessPayload);',
				'$children_size int = layout_sizeof(CompactChildSpan);',
				'echo "record=", $record_size, "\n";',
				'echo "align=", $record_align, "\n";',
				'echo "payload_offset=", $payload_offset, "\n";',
				'echo "payload_size=", $payload_size, "\n";',
				'echo "access_size=", $access_size, "\n";',
				'echo "children_size=", $children_size, "\n";',
				'',
			]));
			$this->write($project . '/native_cpp/probe.cpp', implode("\n", [
				'#include "model.hpp"',
				'namespace scpp_compact_layout_acceptance {',
				'void force_compact_record_value_construction() {',
				'	scpp::CompactParsedExpressionRecord row{};',
				'	(void) row;',
				'}',
				'}',
				'',
			]));
			$this->writeProjectConfig($project, 'compact_layout_acceptance', 'main.phs');

			$build = scpp_run_build_service($project, $project . '/prism.json', [
				'compile_runtime' => true,
				'disable_stan' => true,
			]);
			$this->assertSame(
				true,
				$build['ok'],
				"compact parsed-expression-like project should compile\nSTDOUT:\n"
					. (string) ($build['output'] ?? '')
					. "\nSTDERR:\n"
					. (string) ($build['error'] ?? '')
			);
			if (!is_array($build['result'] ?? null) || !is_string($build['result']['output_path'] ?? null)) {
				throw new RuntimeException('Build result should contain an output binary path.');
			}

			$run = $this->runBuiltProgram($project, (string) $build['result']['output_path'], is_string($build['result']['runtime_library_dir'] ?? null) ? (string) $build['result']['runtime_library_dir'] : null);
			$this->assertSame(0, $run['exit_code'], "compact layout acceptance binary should run\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
			$measurements = $this->parseMeasurements($run['stdout']);

			$recordSize = $measurements['record'] ?? null;
			if (!is_int($recordSize)) {
				throw new RuntimeException('Expected record layout measurement in output: ' . $run['stdout']);
			}
			if ($recordSize >= self::READABLE_PARSED_EXPRESSION_RECORD_BYTES) {
				throw new RuntimeException('Compact row should be below ' . self::READABLE_PARSED_EXPRESSION_RECORD_BYTES . ' bytes; measured ' . $recordSize . ' bytes.');
			}
			if ($recordSize > 64) {
				throw new RuntimeException('Compact row should remain in the expected compact class; measured ' . $recordSize . ' bytes.');
			}
			foreach (['align', 'payload_offset', 'payload_size', 'access_size', 'children_size'] as $key) {
				if (!isset($measurements[$key])) {
					throw new RuntimeException('Missing layout measurement `' . $key . '` in output: ' . $run['stdout']);
				}
			}

			$modelHeader = $this->read($project . '/.prism/generated/model.hpp');
			$this->assertContains('enum class ExpressionKind : std::uint16_t {', $modelHeader, 'ExpressionKind should use exact uint16 enum backing');
			$this->assertContains('struct CompactChildSpan {', $modelHeader, 'child span should lower as an inline struct');
			$this->assertContains('struct ParsedAccessPayload {', $modelHeader, 'nested access payload should lower as an inline struct');
			$this->assertContains('union ParsedExpressionPayload {', $modelHeader, 'payload should lower as a C++ union');
			$this->assertContains('ParsedAccessPayload access;', $modelHeader, 'union should contain nested access payload inline');
			$this->assertContains('struct CompactParsedExpressionRecord {', $modelHeader, 'compact record should lower as an inline struct');
			$this->assertContains('ParsedExpressionPayload payload;', $modelHeader, 'compact record should contain union payload inline');

			echo "PASS: scpp compact layout acceptance (record=" . $recordSize . " bytes)\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function writeProjectConfig(string $projectRoot, string $projectName, string $entrypoint): void
	{
		$config = [
			'config_version' => 1,
			'project_name' => $projectName,
			'entrypoint' => $entrypoint,
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
			'native_cpp_dir' => 'native_cpp',
			'dependencies' => [],
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
					'php' => ['profile' => 'strict'],
				],
			],
		];
		$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		if (!is_string($json)) {
			throw new RuntimeException('Failed to encode prism.json');
		}
		$this->write($projectRoot . '/prism.json', $json . PHP_EOL);
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

	/** @return array<string,int> */
	private function parseMeasurements(string $stdout): array
	{
		$out = [];
		foreach (preg_split('/\R/', trim($stdout)) ?: [] as $line) {
			if (preg_match('/^([a-z_]+)=([0-9]+)$/', trim($line), $matches) === 1) {
				$out[(string) $matches[1]] = (int) $matches[2];
			}
		}
		return $out;
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

exit((new ScppCompactLayoutAcceptanceTest())->run());
