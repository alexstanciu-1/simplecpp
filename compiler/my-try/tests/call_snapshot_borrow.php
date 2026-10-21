<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

/** Test-only syntax inspection counts facts, not emitted spelling as a semantic oracle. */
function snapshot_count(ast_node $node): int
{
	$count = 0;
	if ($node instanceof call_node) {
		foreach ($node->require_call_preparation()->arguments as $argument) {
			if ($argument->borrow_snapshot) {
				if ($argument->boundary !== value_boundary_operation::copy_value) { throw new \LogicException('Changed value semantics'); }
				$count++;
			}
		}
	}
	foreach ($node->children() as $child) { $count += snapshot_count($child); }
	return $count;
}

/** Independent source input, with preparation and emission through the production compiler. */
function snapshot_compile(string $source, bool $concrete, bool $enabled = true): source_record
{
	Compiler_Lifecycle::reset();
	if (!$enabled) {
		foreach (['core.strlen', 'strings.replace', 'strings.substr_compare.3'] as $identity) {
			Model::$runtime_callables->function_definition($identity)->snapshot_borrow_safe = false;
		}
	}
	$module = new module('/snapshot', '/snapshot', '/snapshot');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	$record = Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler(new cpp_lowering_options($concrete)))->exec_cpp();
	return $record;
}

$out = $argv[1];
$cases = [
	'length' => ['$s="abc";echo strlen($s);', 1],
	'compare' => ['$s="abc";$t="abd";echo substr_compare($s,$t,0);', 2],
	'compare_length' => ['$s="abc";$t="abc";echo substr_compare($s,$t,0,2);', 2],
	'compare_case' => ['$s="abc";$t="ABC";echo substr_compare($s,$t,0,3,true);', 2],
	'replace' => ['$n="a";$r="x";$s="aba";echo str_replace($n,$r,$s);', 3],
	'same_storage' => ['$s="abc";echo substr_compare($s,$s,0);', 2],
	'alias' => ['$s="abc";$a =& $s;echo substr_compare($s,$a,0);', 2],
	'readonly' => ['function read(const string &$s):int{return strlen($s);} $s="abc";echo read($s);', 1],
	'mutable_parameter' => ['function read(string &$s):int{return strlen($s);} $s="abc";echo read($s);', 1],
	'unknown_call' => ['function other():string{return "abc";} $s="abc";echo substr_compare($s,other(),0);', 0],
	'mutation' => ['function change(string &$s):string{$s="zzz";return "abc";} $s="abc";echo substr_compare($s,change($s),0);', 0],
	'conversion' => ['$s ?string="abc";echo strlen($s);', 0],
	'property' => ['class Box {public string $s="abc";} $b=new Box();echo strlen($b->s);', 0],
	'temporary_owner' => ['class Box {public string $s="abc";} function make():Box{return new Box();} echo strlen(make()->s);', 0],
	'index' => ['$v vector<string> = ["abc"];echo strlen($v[0]);', 0],
	'unregistered_provider' => ['$s="AbC";echo strtolower($s);', 0],
	'source_callee' => ['function read(string $s):string{return $s;} $s="abc";echo read($s);', 0],
	'literals' => ['echo substr_compare("abc","abc",0);', 0],
];
$checks = [];
foreach ([false, true] as $concrete) {
	foreach ($cases as $name => [$source, $expected]) {
		$record = snapshot_compile($source, $concrete);
		$actual = snapshot_count($record->parsed->root);
		if ($actual !== $expected) { throw new \LogicException($name . ': ' . $actual . ' != ' . $expected); }
		$checks[] = [$concrete, $name, $actual];
	}
	$control = snapshot_compile('$s="abc";echo substr_compare($s,$s,0);echo strlen($s);', $concrete, false);
	if (snapshot_count($control->parsed->root) !== 0) { throw new \LogicException('Disabled provider still borrows'); }
}

// The opt-in is a provider contract, never a source admission requirement.
$base = ['version'=>1,'module'=>'borrow_test','dependencies'=>['core'],'functions'=>[
	['id'=>'borrow.test','name'=>'borrow_test','snapshot_borrow_safe'=>true,
	'parameters'=>[['name'=>'s','type'=>'string','passing'=>'value']],'returns'=>'int']],
	'cpp'=>[['id'=>'borrow.test','symbol'=>'test::read','header'=>'test.hpp']]];
$rejections = [];
foreach (['non_boolean','reference','observation','variadic','default','generic','member'] as $name) {
	$schema = $base;
	if ($name === 'non_boolean') { $schema['functions'][0]['snapshot_borrow_safe'] = 'yes'; }
	elseif ($name === 'reference') { $schema['functions'][0]['parameters'][0]['passing'] = 'reference'; }
	elseif ($name === 'observation') { $schema['functions'][0]['parameters'][0]['passing'] = 'observe'; }
	elseif ($name === 'variadic') { $schema['functions'][0]['parameters'][0]['variadic'] = true; }
	elseif ($name === 'default') { $schema['functions'][0]['parameters'][0]['default'] = ['type'=>'string','value'=>'abc']; }
	elseif ($name === 'generic') { $schema['functions'][0]['type_parameters'] = ['T']; }
	else { $schema['functions'][0]['member'] = ['owner'=>'string','dispatch'=>'instance']; }
	$failed = false;
	try { Runtime_Catalog_Reader::decode(json_encode($schema), 'invalid-borrow.json'); }
	catch (\RuntimeException $error) { $failed = true; }
	if (!$failed) { throw new \LogicException('Invalid borrow metadata admitted: ' . $name); }
	$rejections[] = $name;
}
Runtime_Catalog_Reader::decode(json_encode($base), 'valid-borrow.json');

// Retained edits must discard the previous borrowing proof and restore it on repair.
$source_dir = $out . '/edits';
mkdir($source_dir);
$path = $source_dir . '/main.phs';
$plain = '$s="abc";echo substr_compare($s,$s,0);';
$effectful = 'function other():string{return "abc";} $s="abc";echo substr_compare($s,other(),0);';
foreach ([false, true] as $concrete) {
	file_put_contents($path, $plain);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(new cpp_lowering_options($concrete));
	$compiler->init([$source_dir]);
	$compiler->exec_cpp();
	$initial = CPP_Probe::content(Model::$cpp_files);
	foreach ([$effectful, $plain] as $revision) {
		file_put_contents($path, $revision);
		$compiler->update_cpp([$path]);
		$edited = CPP_Probe::content(Model::$cpp_files);
		$borrowed = 0;
		foreach (Model::$modules as $module) {
			foreach ($module->sources as $record) { $borrowed += snapshot_count($record->parsed->root); }
		}
		if ($borrowed !== ($revision === $plain ? 2 : 0)) { throw new \LogicException('Stale borrow facts after edit'); }
		if ($revision === $plain && $edited !== $initial) { throw new \LogicException('Borrow repair differs'); }
		$compiler->update_cpp([]);
		if (CPP_Probe::content(Model::$cpp_files) !== $edited) { throw new \LogicException('No-op differs'); }
	}
}
file_put_contents($out . '/checks.json', json_encode(['cases'=>$checks,'metadata_rejections'=>$rejections,'edit_modes'=>2], JSON_PRETTY_PRINT) . "\n");
