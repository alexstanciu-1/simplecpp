<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
$root = dirname(__DIR__, 3) . '/runtime/metadata/modules';
$core = file_get_contents($root . '/core.json');
$base = Runtime_Catalog_Loader::load($root, ['core']);
Compiler_Lifecycle::configure_runtime($base);
foreach ([
	str_replace('"source_context":"phs_file_line"', '"source_context":"generated_file_line"', $core),
	str_replace('"source_context":"phs_file_line"', '"source_context":true', $core),
	str_replace('"type":"int","passing":"value"', '"type":"int","passing":"reference"', $core),
] as $invalid) {
	$rejected = false;
	try {
		$candidate = new runtime_catalog_input();
		$candidate->modules->add('core', Runtime_Catalog_Reader::decode($invalid, 'core.json'));
		Compiler_Lifecycle::configure_runtime($candidate);
	} catch (\Throwable $error) { $rejected = true; }
	if (!$rejected || Model::require_runtime_metadata() !== $base) { throw new \LogicException('Invalid context changed runtime configuration'); }
}
// A different source spelling uses the same binding: no dbg-name special case.
$renamed = str_replace('"name":"dbg"', '"name":"inspect_scalar"', $core);
$candidate = new runtime_catalog_input();
$candidate->modules->add('core', Runtime_Catalog_Reader::decode($renamed, 'core.json'));
Compiler_Lifecycle::configure_runtime($candidate);
$directory = $argv[1];mkdir($directory, 0777, true);
file_put_contents($directory . '/main.phs', 'inspect_scalar("alias",42);');
$compiler = new Compiler();$compiler->init([$directory]);$compiler->exec_cpp();
$text = CPP_Probe::text(Model::$cpp_files);
if (!str_contains($text, 'scpp::php::dbg_at("main.phs", 1,')) { throw new \LogicException('Binding context depends on source name'); }
echo "metadata rejects invalid context and preserves name-independent binding\n";
