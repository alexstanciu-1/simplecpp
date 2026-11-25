<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load(
	dirname(__DIR__, 3) . '/runtime/metadata/modules', ['curl']));
$directory = $argv[1];
mkdir($directory, 0777, true);
$path = $directory . '/main.phs';
$valid = '$r curl_response;$r->body="ok";$headers vector<string> = [];'
	. '$r->headers=$headers;$copy=$r->headers;$m mixed;$n int=$m;$s string=$m;';
$invalid = [
	'$r curl_response;$r->body=1;',
	'$r curl_response;$r->missing="x";',
	'$r curl_response;$r->headers="x";',
	'$r curl_response;$r->status_code++;',
	'$r curl_response;$r->status_code+=1;',
	'$r curl_response;$r->headers[]="x";',
	'$m mixed;$f float=$m;',
];
file_put_contents($path, $valid);
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->exec_cpp();
$fresh = CPP_Probe::text(Model::$cpp_files);
foreach ($invalid as $source) {
	file_put_contents($path, $source);
	foreach ([[$path], []] as $changes) {
		$rejected = false;
		try { $compiler->update_cpp($changes); }
		catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected || !Model::$cpp_files->is_empty()) {
			throw new \LogicException('Invalid/unsupported transport published output: ' . $source);
		}
	}
	file_put_contents($path, $valid);
	$compiler->update_cpp([$path]);
	if (CPP_Probe::text(Model::$cpp_files) !== $fresh) {
		throw new \LogicException('Transport repair differs from fresh output');
	}
	$compiler->update_cpp([]);
	if (CPP_Probe::text(Model::$cpp_files) !== $fresh) {
		throw new \LogicException('Transport no-op changed output');
	}
}
echo "seven transport rejection/recovery cases pass\n";
