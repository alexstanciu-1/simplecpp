<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

// Host fixture injects independent backend entries to isolate assembly from semantic demand.
function partition_artifacts(array $names): array
{
	$engine = new CPP_Specializations();
	$install = new \ReflectionMethod($engine, 'install');
	foreach ($names as $name) {
		$install->invoke($engine, new cpp_specialization_entry($name, 'specializations/' . $name . '.hpp',
			"#pragma once\nint " . $name . "();\n", 'int ' . $name . "() { return 1; }\n", []));
	}
	$artifacts = new Storage();
	$engine->append_artifacts($artifacts);
	$result = [];
	foreach ($artifacts as $artifact) {
		if (isset($result[$artifact->file_name])) { throw new \LogicException('Duplicate artifact'); }
		$result[$artifact->file_name] = $artifact->text;
	}
	ksort($result);
	return $result;
}

$names = [];
for ($i = 0; $i < 512; $i++) { $names[] = 'proof_' . $i; }
$before = partition_artifacts($names);
if ($before !== partition_artifacts(array_reverse($names))) { throw new \LogicException('Discovery-order churn'); }
$names[] = 'proof_added';
$after = partition_artifacts($names);
$changed = [];
foreach ($after as $path => $text) {
	if (($before[$path] ?? null) !== $text) { $changed[] = $path; }
}
if (count($changed) !== 2) { throw new \LogicException('Addition changed unrelated artifacts'); }
$units = array_filter(array_keys($before), fn($path) => str_contains($path, '/containers_'));
if (count($units) < 100 || isset($before['specializations/containers.cpp'])) { throw new \LogicException('Not partitioned'); }
$definitions = implode('', array_intersect_key($before, array_flip($units)));
foreach (array_slice($names, 0, 512) as $name) {
	if (substr_count($definitions, 'int ' . $name . '() {') !== 1) { throw new \LogicException('Definition ownership'); }
}

function publish_partition_fixture(string $directory, array $files): void
{
	$artifacts = new Storage();
	foreach ($files as $path => $text) {
		$kind = str_ends_with($path, '.hpp') ? cpp_artifact_kind::header : cpp_artifact_kind::translation_unit;
		$artifacts->append(new cpp_artifact($path, $kind, $path, $text));
	}
	$publication = new Output_Publication($directory);
	try { $publication->publish($artifacts, new runtime_cpp_build_spec()); }
	finally { $publication->close(); }
}
$directory = $argv[1];
publish_partition_fixture($directory, ['specializations/containers.cpp' => $definitions]);
publish_partition_fixture($directory, $before);
if (file_exists($directory . '/specializations/containers.cpp')) { throw new \LogicException('Obsolete monolith retained'); }
$sample = array_values($units)[0];
$inode = fileinode($directory . '/' . $sample);
publish_partition_fixture($directory, partition_artifacts(array_reverse(array_slice($names, 0, 512))));
clearstatcache();
if (fileinode($directory . '/' . $sample) !== $inode) { throw new \LogicException('Warm partition rewritten'); }
publish_partition_fixture($directory, partition_artifacts([]));
if (file_exists($directory . '/' . $sample)) { throw new \LogicException('Obsolete partition retained'); }
echo "partition ordering, ownership, isolated addition, warm reuse and stale cleanup: pass\n";
