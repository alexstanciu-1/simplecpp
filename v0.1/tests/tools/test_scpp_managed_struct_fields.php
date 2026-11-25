<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

use Scpp\S2S\Stan\StanWorkspaceSession;

final class ScppManagedStructFieldsTest
{
	private string $root;

	public function run(): int
	{
		$this->root = sys_get_temp_dir() . '/scpp_managed_struct_fields_' . getmypid() . '_' . bin2hex(random_bytes(4));
		try {
			foreach (['strict', 'legacy', 'jss'] as $surface) {
				$this->checkSurface($surface);
			}
			echo "PASS: managed struct fields (strict, legacy, JSS; copy semantics and exclusions)\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function checkSurface(string $surface): void
	{
		$project = $this->root . '/' . $surface;
		$extension = $surface === 'jss' ? 'jss' : 'phs';
		$this->write($project . '/prism.json', json_encode([
			'config_version' => 1, 'project_name' => 'managed_struct_' . $surface,
			'entrypoint' => 'main.' . $extension,
			'build_dir' => '.prism/build', 'generated_dir' => '.prism/generated', 'cache_dir' => '.prism/cache',
			'dependencies' => [], 'libraries' => [],
			'build' => ['backend' => 'ninja', 'mode' => 'debug'],
			'runtime' => ['languages' => ['php'], 'modules' => [],
				'language_profiles' => ['php' => ['profile' => $surface === 'legacy' ? 'legacy' : 'strict']]],
		], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
		$this->write($project . '/object.' . $extension, $surface === 'jss' ? <<<'JSS'
class Some_Custom_Class {
    name: string = "original";
}
JSS
 : <<<'PHS'
class Some_Custom_Class {
    public string $name = "original";
}
PHS
);
		$this->write($project . '/model.' . $extension, $surface === 'jss' ? <<<'JSS'
struct Leaf { text: string = "leaf"; }
struct Row {
    my_string: string;
    my_property: Some_Custom_Class;
    strings: vector<string>;
    objects: vector<Some_Custom_Class>;
    labels: hash<string>;
    lookup: hash<Some_Custom_Class>;
    nested: vector<hash<string>>;
    leaf: Leaf;
    defaults: vector<string> = ["default"];
    fixed: fixed_array<string, 2> = ["a", "b"];
}
JSS
 : <<<'PHS'
struct Leaf { string $text = "leaf"; }
struct Row {
    string $my_string;
    Some_Custom_Class $my_property;
    public $strings vector<string>;
    public $objects vector<Some_Custom_Class>;
    public $labels hash_t<string>;
    public $lookup hash<Some_Custom_Class>;
    public $nested vector<hash<string>>;
    Leaf $leaf;
    public $defaults vector<string> = ["default"];
    public $fixed fixed_array<string, 2> = ["a", "b"];
}
PHS
);
		$this->write($project . '/main.' . $extension, $surface === 'jss' ? <<<'JSS'
function pass(row: Row): Row { return row; }
let row: Row;
print(strlen(row.my_string), ":", count(row.strings), ":", row.defaults[0], ":", row.fixed[1], "\n");
row.my_string = "original";
row.my_property = new Some_Custom_Class();
let strings: vector<string> = ["one"];
row.strings = strings;
let objects: vector<Some_Custom_Class> = [row.my_property];
row.objects = objects;
let labels: hash<string> = {"key": "one"};
row.labels = labels;
let lookup: hash<Some_Custom_Class> = {"key": row.my_property};
row.lookup = lookup;
let nestedValue: hash<string> = {"key": "nested"};
let nestedValues: vector<hash<string>> = [nestedValue];
row.nested = nestedValues;
let copy: Row = pass(row);
copy.my_string = "changed";
copy.strings[0] = "changed";
copy.labels["key"] = "changed";
copy.leaf.text = "changed";
copy.my_property.name = "shared";
let fromVector: Some_Custom_Class = copy.objects[0];
fromVector.name = "vector";
let fromHash: Some_Custom_Class = copy.lookup["key"];
fromHash.name = "hash";
print(row.my_string, ":", row.strings[0], ":", row.labels["key"], ":", row.leaf.text, ":", row.my_property.name, "\n");
let nestedCopy: vector<hash<string>> = row.nested;
let nested: hash<string> = nestedCopy[0];
print(nested["key"], "\n");
JSS
 : <<<'PHS'
function pass(Row $row): Row { return $row; }
$row Row = [];
echo strlen($row->my_string), ":", count($row->strings), ":", $row->defaults[0], ":", $row->fixed[1], "\n";
$row->my_string = "original";
$row->my_property = new Some_Custom_Class();
$strings vector<string> = ["one"];
$row->strings = $strings;
$objects vector<Some_Custom_Class> = [$row->my_property];
$row->objects = $objects;
$labels hash_t<string> = ["key" => "one"];
$row->labels = $labels;
$lookup hash<Some_Custom_Class> = ["key" => $row->my_property];
$row->lookup = $lookup;
$nestedValues vector<hash<string>> = [["key" => "nested"]];
$row->nested = $nestedValues;
$copy Row = pass($row);
$copy->my_string = "changed";
$copy->strings[0] = "changed";
$copy->labels["key"] = "changed";
$copy->leaf->text = "changed";
$copy->my_property->name = "shared";
$fromVector Some_Custom_Class = $copy->objects[0];
$fromVector->name = "vector";
$fromHash Some_Custom_Class = $copy->lookup["key"];
$fromHash->name = "hash";
echo $row->my_string, ":", $row->strings[0], ":", $row->labels["key"], ":", $row->leaf->text, ":", $row->my_property->name, "\n";
$nestedCopy vector<hash<string>> = $row->nested;
$nested hash<string> = $nestedCopy[0];
echo $nested["key"], "\n";
PHS
);
		if ($surface !== 'jss') {
			$this->write($project . '/model.phs', $this->read($project . '/model.phs') . "\n" . <<<'PHS'
function make_seed_row(): Row {
    $row Row = ["labels" => ["key" => "seed"], "my_property" => new Some_Custom_Class(), "my_string" => "seed", "nested" => [["key" => "seed"]]];
    return $row;
}
PHS
);
			$this->write($project . '/main.phs', $this->read($project . '/main.phs') . "\n" . <<<'PHS'
$seed Row = make_seed_row();
echo $seed->my_string, ":", $seed->labels["key"], ":", $seed->my_property->name, "\n";
PHS
);
		}
		$build = scpp_run_build_service($project, $project . '/prism.json', ['compile_runtime' => true]);
		$this->assertSame(true, $build['ok'], $surface . " build: " . ($build['output'] ?? '') . ($build['error'] ?? ''));
		$result = $build['result'];
		$run = $this->runBuiltProgram($project, $result['output_path'], $result['runtime_library_dir'] ?? null);
		$this->assertSame(0, $run['exit_code'], $surface . ' execution: ' . $run['stderr']);
		$this->assertSame("0:0:default:b\noriginal:one:one:leaf:hash\nnested\n" . ($surface === 'jss' ? '' : "seed:seed:original\n"), str_replace("\r\n", "\n", $run['stdout']), $surface . ' field copy semantics');
		echo 'PASS: ' . $surface . " managed struct execution\n";
		if ($surface !== 'jss') {
			$this->write($project . '/invalid.phs', <<<'PHS'
struct InvalidFields {
    mixed $data;
    public $nested vector<hash<mixed>>;
    dynamic $dynamic_data;
}
struct StringPayload { string $text; }
struct ObjectPayload { Some_Custom_Class $object; }
struct NestedPayload { StringPayload $inner; }
union InvalidPayload {
    StringPayload $text;
    ObjectPayload $object;
    NestedPayload $nested;
    string $direct;
}
PHS
);
			$diagnostics = (new StanWorkspaceSession())->runDiagnostics($project, $project . '/prism.json');
			$contracts = array_values(array_filter($diagnostics['diagnostics'] ?? [], static fn (array $d): bool => in_array($d['code'] ?? '', ['stan.struct_contract_mismatch', 'stan.union_contract_mismatch'], true)));
			$this->assertSame(7, count($contracts), $surface . ' mixed/dynamic and managed union payloads remain rejected: ' . json_encode($contracts));
			$rejected = scpp_run_build_service($project, $project . '/prism.json', ['disable_stan' => true]);
			$this->assertSame(false, $rejected['ok'], 'generator must also reject excluded field types');
			$messages = ($rejected['output'] ?? '') . ($rejected['error'] ?? '');
			foreach (['mixed', 'vector<hash<mixed>>', 'dynamic'] as $type) {
				$this->assertContains('unsupported first-slice field type ' . $type, $messages, 'generator struct exclusion');
			}
			foreach (['StringPayload', 'ObjectPayload', 'NestedPayload', 'string'] as $type) {
				$this->assertContains('unsupported first-slice payload type ' . $type, $messages, 'generator union exclusion');
			}
		}
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

exit((new ScppManagedStructFieldsTest())->run());
