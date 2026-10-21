<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

/** Default-prelowering retained/fresh equivalence across selection and lifecycle edits. */
function verify_recovery(string $directory, string $initial, string $edited, string $invalid, string $diagnostic): void
{
	mkdir($directory);
	$path = $directory . '/main.phs';
	file_put_contents($path, $initial);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	$before = CPP_Output::encode(Model::$cpp_files);
	$compiler->update_cpp([]);
	if (CPP_Output::encode(Model::$cpp_files) !== $before) { throw new \LogicException('No-op changed output'); }
	file_put_contents($path, $edited); $compiler->update_cpp([$path]);
	$after = CPP_Output::encode(Model::$cpp_files);
	if ($after === $before) { throw new \LogicException('Edit did not change output'); }
	file_put_contents($path, $invalid);
	foreach ([[$path], []] as $changes) {
		try { $compiler->update_cpp($changes); throw new \LogicException('Invalid edit accepted'); }
		catch (\RuntimeException $error) {
			if (!str_contains($error->getMessage(), $diagnostic)) { throw $error; }
		}
	}
	file_put_contents($path, $edited); $compiler->update_cpp([$path]);
	if (CPP_Output::encode(Model::$cpp_files) !== $after) { throw new \LogicException('Repair changed output'); }
	Compiler_Lifecycle::reset_preparation(); $compiler->prepare(); $compiler->cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $after) { throw new \LogicException('Preparation reset changed output'); }
	Compiler_Lifecycle::reset(); $compiler = new Compiler();
	$compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $after) { throw new \LogicException('Fresh output differs'); }
	file_put_contents($path, $initial); $compiler->update_cpp([$path]);
	if (CPP_Output::encode(Model::$cpp_files) !== $before) { throw new \LogicException('Reverse edit changed output'); }
	Compiler_Lifecycle::reset(); unlink($path); rmdir($directory);
}
$root = $argv[1]; mkdir($root);
$trait = 'trait T { function __construct(){echo "T";} function __destruct(){echo "t";} } ';
$use = 'class A {use T;} $a=new A();';
$local = 'class A {use T;function __construct(){echo "L";}function __destruct(){echo "l";}}$a=new A();';
verify_recovery($root . '/traits', $trait . $use, $trait . $local,
	$trait . 'trait U {function __construct(){}}class A {use T,U;function __construct(){}}', 'Conflicting trait lifecycle');
$base = 'abstract class B {abstract function read():int;}class C extends B {function read():int{return 1;}}';
$selection = '$b unique<B>;$c unique<C> =new C();$v=true?move($c):move($b);';
verify_recovery($root . '/unique', $base . $selection, $base . str_replace('true?', 'false?', $selection),
	'class B {}class C extends B {}' . $selection, 'virtual destruction');
$weak = 'class B {}class C extends B {}$b weak<B>;$c weak<C>;$v=true?$c:$b;';
verify_recovery($root . '/weak', $weak, str_replace('true?', 'false?', $weak),
	str_replace('class C extends B', 'class C', $weak), 'sibling');
rmdir($root);
echo "Trait/ownership retained recovery: three edit cycles passed\n";
