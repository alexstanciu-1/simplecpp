<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function concurrent_stage_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}
function concurrent_stage_artifacts(): array
{
	$result = [];
	foreach (Model::$cpp_files as $artifact) { $result[$artifact->file_name] = $artifact->text; }
	ksort($result);
	return $result;
}
function concurrent_stage_versions(): array
{
	$result = [];
	foreach (Callable_Inventory::source_collections(Model::collected_files()) as $source) {
		foreach ($source->entries as $entry) {
			if ($entry instanceof collected_source_callable) {
				$syntax = $entry->callable_syntax();
				if ($syntax->has_parsed_body()) {
					$work = $syntax->require_body()->work();
					if ($work !== null) { $result[$entry->qualified_name()] = $work->version; }
				}
			}
		}
	}
	ksort($result);
	return $result;
}
$directory = $argv[1];
mkdir($directory);
$sources = [
	'types.phs' => 'namespace shared_types; struct Packet { public int $count = 0; } class Base { public static function value(int $x): int { return $x + \\settings\\OFFSET; } } class Child extends Base {}',
	'config.phs' => 'namespace settings; const OFFSET = 3;',
	'main.phs' => 'echo left(), ":", right();',
	'left.phs' => 'function left(): int { $packets vector<\\shared_types\\Packet> = []; $values vector<int> = []; $values[] = 1; $callback = function(int $x): int { return $x + 1; }; return $callback($values[0]) + \\shared_types\\Child::value(2); }',
	'right.phs' => 'function right(): int { $packets vector<\\shared_types\\Packet> = []; $values vector<int> = []; $values[] = 4; $callback = function(int $x): int { return $x + 1; }; return $callback($values[0]) + \\shared_types\\Child::value(2); }',
];
foreach ([false, true] as $concrete) {
	$baseline = null;
	foreach ([1, 2, 32] as $jobs) {
		foreach ($sources as $name => $text) { file_put_contents($directory . '/' . $name, $text); }
		Compiler_Lifecycle::reset();
		$compiler = new Compiler(new cpp_lowering_options($concrete));
		$compiler->jobs = $jobs;
		$compiler->init([$directory]);
		$compiler->select_entry('main', 'main.phs');
		$compiler->exec_cpp();
		$registry = Model::$type_catalog->registry();
		$definition_count = $registry->definition_count();
		foreach (Model::collected_files() as $source) {
			foreach ($source->entries as $entry) {
				if ($entry instanceof collected_type_definition) {
					$definition = Source_Types::definition($entry);
					concurrent_stage_check(Source_Types::definition($entry) === $definition, 'Source lookup allocated another identity');
				}
			}
		}
		concurrent_stage_check($registry->definition_count() === $definition_count, 'Source lookup mutated the registry');
		$initial = concurrent_stage_artifacts();
		if ($baseline === null) { $baseline = $initial; }
		concurrent_stage_check($initial === $baseline, 'Worker count changed output');
		$versions = concurrent_stage_versions();
		$compiler->update_cpp([]);
		concurrent_stage_check(concurrent_stage_artifacts() === $initial && concurrent_stage_versions() === $versions, 'Warm build changed output or rebuilt bodies');
		foreach (['namespace settings; const OFFSET = "bad";', 'namespace settings; const OTHER = 3;'] as $invalid) {
			file_put_contents($directory . '/config.phs', $invalid);
			foreach ([[$directory . '/config.phs'], []] as $changed) {
				$failed = false;
				try { $compiler->update_cpp($changed); }
				catch (\RuntimeException $error) { $failed = true; }
				concurrent_stage_check($failed && Model::$cpp_files->is_empty(), 'Failure published stale output or retry lost diagnostic');
			}
			file_put_contents($directory . '/config.phs', $sources['config.phs']);
			$compiler->update_cpp([$directory . '/config.phs']);
			concurrent_stage_check(concurrent_stage_artifacts() === $initial, 'Dependency/lookup recovery differs');
		}
	}
}
echo "Cold/warm output, source batches, shared applications, closures, called-type rounds and failure recovery: pass\n";
