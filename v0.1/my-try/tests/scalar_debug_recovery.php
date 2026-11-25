<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
$directory = $argv[1];
mkdir($directory, 0777, true);
$path = $directory . '/main.phs';
$source = "function show():void {\n dbg(42);\n}\nshow();\n";
file_put_contents($path, $source);
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->exec_cpp();
$first = CPP_Probe::text(Model::$cpp_files);
if (!str_contains($first, '"main.phs", 2,')) { throw new \LogicException('Initial callsite missing'); }
file_put_contents($path, "\n\n" . $source);
$compiler->update_cpp([$path]);
$shifted = CPP_Probe::text(Model::$cpp_files);
if (!str_contains($shifted, '"main.phs", 4,') || str_contains($shifted, '"main.phs", 2,')) {
	throw new \LogicException('Whitespace edit retained stale callsite');
}
$compiler->update_cpp([]);
if (CPP_Probe::text(Model::$cpp_files) !== $shifted) { throw new \LogicException('No-op changed callsite'); }
$fresh = new Compiler();
$fresh->init([$directory]);
$fresh->exec_cpp();
if (CPP_Probe::text(Model::$cpp_files) !== $shifted) { throw new \LogicException('Incremental callsite differs from fresh'); }
foreach (['dbg();', 'dbg([1,2]);', 'dbg("label", 42, "bad flags");'] as $invalid) {
	file_put_contents($path, $invalid);
	foreach ([[$path], []] as $changes) {
		$rejected = false;
		try { $fresh->update_cpp($changes); } catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected || !Model::$cpp_files->is_empty()) { throw new \LogicException('Invalid dbg published output'); }
	}
	file_put_contents($path, "\n\n" . $source);
	$fresh->update_cpp([$path]);
	if (CPP_Probe::text(Model::$cpp_files) !== $shifted) { throw new \LogicException('Debug repair differs from fresh'); }
}
// Ordinary generation consumes the same source context, not generated __FILE__/__LINE__.
$ordinary = new Compiler(new cpp_lowering_options(false));
$ordinary->init([$directory]);
$ordinary->exec_cpp();
if (!str_contains(CPP_Probe::text(Model::$cpp_files), '"main.phs", 4,')) { throw new \LogicException('Ordinary source context missing'); }
echo "source movement, fresh/no-op, three rejection/repair cycles and ordinary context pass\n";
