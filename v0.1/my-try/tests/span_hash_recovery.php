<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load(dirname(__DIR__, 3) . '/runtime/metadata/modules', ['source']));
$directory = $argv[1];
mkdir($directory, 0777, true);
$path = $directory . '/main.phs';
$source = '$text="abc";$buffer=source_buffer_take($text);$span=source_buffer_span($buffer,0,3);echo hash_bytes($span);';
file_put_contents($path, $source);
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->exec_cpp();
$first = CPP_Probe::text(Model::$cpp_files);
$compiler->update_cpp([]);
if ($first !== CPP_Probe::text(Model::$cpp_files)) { throw new \LogicException('Span no-op changed output'); }
foreach ([
	'source_buffer_span(source_buffer_empty(),0,0);',
	'function bad(const source_buffer &$b):void {source_buffer_release($b);}',
	'$b=source_buffer_empty();source_buffer_span($b,"0",0);',
	'$b=source_buffer_empty();$copy=move($b);',
	'echo stable_hash_string_u64("abc") === stable_hash_string_u64("abc");',
] as $invalid) {
	file_put_contents($path, $invalid);
	foreach ([[$path], []] as $changes) {
		$rejected = false;
		try { $compiler->update_cpp($changes); } catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected || !Model::$cpp_files->is_empty()) { throw new \LogicException('Invalid span/hash call emitted'); }
	}
	file_put_contents($path, $source);
	$compiler->update_cpp([$path]);
	if ($first !== CPP_Probe::text(Model::$cpp_files)) { throw new \LogicException('Span/hash recovery changed output'); }
}
$edited = str_replace('abc', 'xyz', $source);
file_put_contents($path, $edited);
$compiler->update_cpp([$path]);
$incremental = CPP_Probe::text(Model::$cpp_files);
$fresh = new Compiler();
$fresh->init([$directory]);
$fresh->exec_cpp();
if ($first === $incremental || $incremental !== CPP_Probe::text(Model::$cpp_files)) { throw new \LogicException('Span edit differs from fresh'); }
echo "default prelowering: no-op, edit/fresh and five rejection/repair cycles pass\n";
