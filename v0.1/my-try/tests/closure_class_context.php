<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$directory = $argv[1];
mkdir($directory);
$path = $directory . '/main.phs';
$valid = 'class Owner { private static function secret(): int { return 7; } '
	. 'public static function run(): int { $f = function(): int { return self::secret(); }; return $f(); } } echo Owner::run();';
foreach ([false, true] as $prelower) {
	Compiler_Lifecycle::reset();
	file_put_contents($path, $valid);
	$compiler = new Compiler(new cpp_lowering_options($prelower));
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$original = CPP_Probe::text(Model::$cpp_files);
	foreach ([
		'class Owner {private static function secret(): int {return 7;}} $f=function():int{return Owner::secret();};echo $f();' => 'Private method is not accessible',
		'class Owner {public static function run():int {$f=function():int{return $this->read();};return $f();} public function read():int{return 7;}} echo Owner::run();' => '$this',
		'class Owner {private static function secret():int{return 7;}} class Other {public static function run():int {$f=function():int{return Owner::secret();};return $f();}} echo Other::run();' => 'Private method is not accessible',
	] as $source => $diagnostic) {
		file_put_contents($path, $source);
		foreach ([[$path], []] as $changed) {
			$failed = false;
			try { $compiler->sync($changed); $compiler->prepare(); }
			catch (\RuntimeException $error) {
				$failed = str_contains($error->getMessage(), $diagnostic);
				if (!$failed) { throw $error; }
			}
			if (!$failed || !Model::$cpp_files->is_empty()) {
				throw new \LogicException('Invalid closure access admitted or stale output retained');
			}
		}
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		if (CPP_Probe::text(Model::$cpp_files) !== $original) {
			throw new \LogicException('Closure context recovery changed output');
		}
	}
}
echo "Closure class context rejection and recovery passed\n";
