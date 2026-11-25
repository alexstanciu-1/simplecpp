<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
$directory = $argv[1];
mkdir($directory);
$path = $directory . '/main.phs';
$valid = 'struct S {} $s shared<S> = null; $u unique<S> = null; $w weak<S> = null; return 0;';
foreach ([false, true] as $prelower) {
	Compiler_Lifecycle::reset();
	file_put_contents($path, $valid);
	$compiler = new Compiler(new cpp_lowering_options($prelower));
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$original = CPP_Probe::text(Model::$cpp_files);
	foreach (['shared<S>', 'unique<S>', 'weak<S>'] as $owner) {
		foreach (['?' . $owner, 'nullable<' . $owner . '>'] as $type) {
			foreach (['$v ' . $type . ';', 'struct Holder { public ' . $type . ' $value; }',
				'function input(' . $type . ' $value): void {}', 'function output(): ' . $type . ' { return null; }',
				'$items vector<' . $type . '>;'] as $source) {
				file_put_contents($path, 'struct S {} ' . $source);
				foreach ([[$path], []] as $changes) {
					$failed = false;
					try { $compiler->sync($changes); $compiler->prepare(); }
					catch (\RuntimeException $error) {
						$failed = str_contains($error->getMessage(), 'redundant nullable wrapper');
						if (!$failed) { throw $error; }
					}
					if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Nullable ownership rejection failed: ' . $source); }
				}
				file_put_contents($path, $valid);
				$compiler->update_cpp([$path]);
				if (CPP_Probe::text(Model::$cpp_files) !== $original) { throw new \LogicException('Ownership recovery changed output'); }
			}
		}
	}
}
echo "Nullable ownership source admission and recovery passed\n";
