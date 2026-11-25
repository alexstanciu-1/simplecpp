<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
$directory = $argv[1]; mkdir($directory);
$path = $directory . '/main.phs';
$source = 'function &identity(int &$value): int { return $value; } echo 1;';
file_put_contents($path, $source);
Compiler_Lifecycle::reset(); $compiler = new Compiler();
$compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
function advisory_check(Compiler $compiler, int $count, int $line): string {
	$records /** Storage<compiler_diagnostic> */ = $compiler->diagnostics();
	if ($records->count() !== $count) { throw new \LogicException('Unexpected advisory count'); }
	foreach ($records as $record) {
		if (($record->code !== 'REF001') || ($record->severity !== 'warning') || ($record->line !== $line)) {
			throw new \LogicException('Incorrect advisory record');
		}
	}
	return Diagnostic_Reporting::text($records);
}
$initial = advisory_check($compiler, 1, 1);
$compiler->update_cpp([]);
if (advisory_check($compiler, 1, 1) !== $initial) { throw new \LogicException('Duplicate or unstable no-op advisory'); }
file_put_contents($path, "\n\n" . $source); $compiler->update_cpp([$path]); advisory_check($compiler, 1, 3);
file_put_contents($path, 'echo 1;'); $compiler->update_cpp([$path]); advisory_check($compiler, 0, 0);
file_put_contents($path, 'function &bad(): int { $value = 1; return $value; }');
try { $compiler->update_cpp([$path]); throw new \LogicException('Invalid reference return accepted'); }
catch (\RuntimeException $expected) { advisory_check($compiler, 0, 0); }
file_put_contents($path, $source); $compiler->update_cpp([$path]);
if (advisory_check($compiler, 1, 1) !== $initial) { throw new \LogicException('Repair changed advisory'); }
Compiler_Lifecycle::reset(); $compiler = new Compiler();
$compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
if (advisory_check($compiler, 1, 1) !== $initial) { throw new \LogicException('Fresh advisory differs'); }
// Each source form owns one advisory; no stale warning after a non-reference replacement.
foreach ([
 'trait Unused {public function &identity(int &$v):int{return $v;}}',
 'class Box { public function &identity(int &$v):int{return $v;} }',
 'class Box { public static function &identity(int &$v):int{return $v;} }',
 '$f = function &(int &$v):int{return $v;};',
 'trait T { public function &identity(int &$v):int{return $v;} } class A {use T;} class B {use T;}',
] as $form) {
 file_put_contents($path,$form);$compiler->update_cpp([$path]);advisory_check($compiler,1,1);
 $compiler->update_cpp([]);advisory_check($compiler,1,1);
}
file_put_contents($path,"\r\n  ".$source);$compiler->update_cpp([$path]);advisory_check($compiler,1,2);
$rows=$compiler->diagnostics();if($rows[0]->column!==3){throw new \LogicException('CRLF/column mapping differs');}
file_put_contents($path,'function broken(');$failed=false;
try{$compiler->update_cpp([$path]);}catch(\RuntimeException $error){$failed=true;}
if(!$failed){throw new \LogicException('Parse failure accepted');}advisory_check($compiler,0,0);
file_put_contents($path,$source);$compiler->update_cpp([$path]);advisory_check($compiler,1,1);
unlink($path);try{$compiler->update_cpp([$path]);}catch(\RuntimeException $error){
 if(!str_contains($error->getMessage(),'Select an existing program entry')){throw $error;}
}advisory_check($compiler,0,0);
file_put_contents($path,$source);$compiler->update_cpp([$path]);advisory_check($compiler,1,1);
Compiler_Lifecycle::reset(); unlink($path); rmdir($directory);
echo $initial;
echo "Reference advisory replacement/recovery passed\n";
