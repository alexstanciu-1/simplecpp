<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function runtime_type_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

// Host-only fixture mutation/IO. Production decoding goes straight to typed records.
$text = file_get_contents(__DIR__ . '/runtime_types.json');
$directory = $argv[1] ?? sys_get_temp_dir() . '/type-proof-' . bin2hex(random_bytes(6));
if (!is_dir($directory)) {
	mkdir($directory);
}
file_put_contents($directory . '/type_proof.json', $text);
copy(dirname(__DIR__, 3) . '/runtime/metadata/modules/core.json', $directory . '/core.json');
$input = Runtime_Catalog_Loader::load($directory, ['type_proof']);
unlink($directory . '/type_proof.json');
unlink($directory . '/core.json');
Compiler_Lifecycle::configure_runtime($input);

$core_types = Type_Definition_Registration::install(new Type_Registry());
$core_input = new runtime_catalog_input();
$core_input->modules->add('core', $input->modules['core']);
Runtime_Type_Registration::install($core_input, $core_types);
runtime_type_check($core_types->source()->type_or_null('parcel') === null
	&& $core_types->source()->type_or_null('receipt') === null, 'Unselected provider types leaked');

$missing_foundation = false;
try {
	Compiler_Lifecycle::configure_runtime(new runtime_catalog_input());
}
catch (\OutOfBoundsException $error) {
	$missing_foundation = str_contains($error->getMessage(), 'Unknown source type: vector');
}
runtime_type_check($missing_foundation && Model::$runtime_metadata === $input,
	'Missing protocol prerequisite was accepted until reset');

$schema = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
$invalids = [];
$bad = $schema; $bad['types'][0]['kind'] = 'nullable'; $invalids[] = [$bad, 'Unsupported runtime type kind'];
$bad = $schema; $bad['types'][0]['capabilities'][] = 'invented'; $invalids[] = [$bad, 'Unknown runtime capability'];
$bad = $schema; $bad['types'][0]['capabilities'][] = 'value_storable'; $invalids[] = [$bad, 'Duplicate runtime capability'];
$bad = $schema; unset($bad['types'][0]['parameters'][0]['requires']); $invalids[] = [$bad, 'Missing runtime type parameter'];
$bad = $schema; $bad['types'][0]['parameters'][0]['extra'] = true; $invalids[] = [$bad, 'Unknown runtime type parameter'];
$bad = $schema; $bad['types'][0]['parameters'][] = $bad['types'][0]['parameters'][0]; $invalids[] = [$bad, 'Duplicate runtime type parameter'];
$bad = $schema; $bad['types'][1]['id'] = 'proof.parcel'; $invalids[] = [$bad, 'Duplicate runtime type identity'];
$bad = $schema; $bad['types'][1]['name'] = 'parcel'; $invalids[] = [$bad, 'Duplicate runtime type name'];
foreach (['int', 'vector', 'value'] as $collision) {
	$bad = $schema; $bad['types'][0]['name'] = $collision; $invalids[] = [$bad, 'Duplicate runtime type name'];
}
$bad = $schema; $bad['types'][0]['name'] = 'invalid-name'; $invalids[] = [$bad, 'Invalid runtime type identifier'];
$bad = $schema; $bad['types'][0]['parameters'][0]['default'] = 'unknown'; $invalids[] = [$bad, 'Unknown source type'];
$bad = $schema; $bad['types'][0]['parameters'][0]['default'] = 'parcel'; $invalids[] = [$bad, 'Runtime type default cycle'];
$bad = $schema; $bad['types'][0]['parameters'][0]['default'] = ['parameter' => 'T']; $invalids[] = [$bad, 'require closed types'];
$bad = $schema; $bad['types'][0]['parameters'][0]['default'] = 'void'; $invalids[] = [$bad, 'default violates value_storable'];
$bad = $schema; $bad['types'][0]['parameters'][] = ['name' => 'U', 'requires' => []]; $invalids[] = [$bad, 'Required template parameter cannot follow'];
$bad = $schema; $bad['types'][0]['parameters'][0]['capability_dependencies'] = ['hashable']; $invalids[] = [$bad, 'requires a declared template capability'];
$bad = $schema; $bad['types'][0]['parameters'][0]['capability_dependencies'] = ['result_payload']; $invalids[] = [$bad, 'unconditional transport opt-in'];
$bad = $schema; array_pop($bad['cpp_types']); $invalids[] = [$bad, 'type binding count mismatch'];
$bad = $schema; $bad['cpp_types'][0]['id'] = 'orphan'; $invalids[] = [$bad, 'Missing runtime type binding'];
$bad = $schema; $bad['cpp_types'][0]['symbol'] = 'bad();'; $invalids[] = [$bad, 'Invalid runtime C++ binding'];
foreach ($invalids as [$invalid, $message]) {
	$failed = false;
	try {
		$candidate = new runtime_catalog_input();
		$candidate->modules->add('core', $input->modules['core']);
		$candidate->modules->add('type_proof', Runtime_Catalog_Reader::decode(json_encode($invalid), 'bad.json'));
		Compiler_Lifecycle::configure_runtime($candidate);
	}
	catch (\Throwable $error) {
		$failed = str_contains($error->getMessage(), $message);
	}
	runtime_type_check($failed && Model::$runtime_metadata === $input, 'Bad metadata was accepted/wrong diagnostic: ' . $message);
}

Compiler_Lifecycle::reset();
$registry = Model::$type_catalog->registry();
$parcel = object_cast(Model::$type_catalog->definition('parcel'), abstract_template_type_definition::class);
$receipt = $registry->use(Model::$type_catalog->canonical('receipt')->type_id());
$defaulted = $registry->intern_application($parcel, []);
runtime_type_check($defaulted === $registry->intern_application($parcel, [$receipt]), 'Forward default changed identity');
$identity = Model::$type_catalog->providers()->provider_for($parcel->definition_id())->identity();
runtime_type_check($identity->provider() === 'simple_cpp.runtime.type_proof' && $identity->family() === 'proof.parcel', 'Provider identity conflated with source/C++ name');
$unique = object_cast(Model::$type_catalog->definition('unique'), abstract_template_type_definition::class);
$owned = $registry->use($registry->intern_application($unique, [$receipt])->type_id());
$noncopy = $registry->use($registry->intern_application($parcel, [$owned])->type_id());
runtime_type_check(!$registry->has_capability($noncopy, generic_contract::copyable_value)
	&& $registry->has_capability($noncopy, generic_contract::value_storable)
	&& $registry->has_capability($noncopy, generic_contract::result_payload), 'Formation and capability dependency conflated');
Compiler_Lifecycle::reset_syntax();
runtime_type_check(Model::$runtime_metadata === $input && Model::$type_catalog->definition('parcel') !== $parcel,
	'Reset reread metadata or reused semantic definitions');

/** Exercise ordinary preparation/emission and the retained no-op path. */
function runtime_type_compile(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/runtime-types', '/runtime-types', '/runtime-types');
	$file = new file(); $file->path = 'main.phs'; $file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	$compiler = new Compiler();
	$compiler->exec_cpp();
	$text = CPP_Probe::text(Model::$cpp_files);
	$compiler->exec_cpp();
	runtime_type_check($text === CPP_Probe::text(Model::$cpp_files), 'No-op output changed');
	return $text;
}

$cases = [
	'nominal' => '$v receipt = make_receipt(42); $copy = $v; $n = receipt_number($copy); if ($n !== 42) { return 1; } return 0;',
	'generic' => '$a parcel<int> = pack(7); $b parcel<string> = pack("ok"); $nested = pack($a); $n = unpack(unpack($nested)); $s = unpack($b); $length = strlen($s); if ($n !== 7 || $length !== 2) { return 1; } return 0;',
	'default' => '$p = default_parcel(9); $n = receipt_number(unpack($p)); if ($n !== 9) { return 1; } return 0;',
	'result' => 'function wrap(parcel<int> $p): result<parcel<int>> { return $p; } $p = pack(8); $r = wrap($p); $copy parcel<int> = $r; $error error; if (!take($copy, $error, $r)) { return 1; } if ($error) { return 2; } $n = unpack($copy); if ($n !== 8) { return 3; } return 0;',
	'core' => '$v vector<int> = [2, 3]; $h hash<int> = {x: 7}; $v[] = 4; $h["y"] = 8; $n = count($v); $m = count($h); $x = $h["x"]; if ($n !== 3 || $m !== 2 || $x !== 7) { return 1; } return 0;',
];
foreach ($cases as $name => $source) {
	file_put_contents($directory . '/type_' . $name . '.cpp', runtime_type_compile($source));
}
foreach ([
	'function bad(const parcel<unique<int>> &$p): void { $copy = $p; }',
	'function bad(parcel<void> $p): void {}',
	'$p parcel<int> = [1];', // copy/storage/Result opt-in does not grant initialization.
	'$p = pack(1); $v = $p[0];', // nor indexing.
] as $source) {
	$failed = false;
	try { runtime_type_compile($source); }
	catch (\RuntimeException $error) {
		$failed = true;
	}
	runtime_type_check($failed && Model::$cpp_files->is_empty(), 'Unregistered operation accepted: ' . $source);
}
runtime_type_check(runtime_type_compile($cases['generic']) !== '', 'Failed preparation did not recover');
echo "Runtime type metadata passed\n";
