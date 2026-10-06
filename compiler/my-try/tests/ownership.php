<?php

/* Source admission/recovery for the ownership model; no allocation or move execution is claimed. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function ownership_source_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Always exercise the real preparation publication boundary before emission. */
function ownership_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$input_module = new module('/ownership', '/ownership', '/ownership');
	$input = new file();
	$input->path = 'main.phs';
	$input->content = $source;
	Source_Registry::add($input_module, $input);
	Model::$modules->add($input_module->name, $input_module);
	(new Compiler())->exec_cpp();
	return Model::$cpp_files[0]->text;
}

$cases = [
	'empty_reset' => 'function reset_unique(unique<int> &$u): void { $u = null; } $s shared<int> = null; $u unique<int> = null; $w weak<int> = null; $a =& $u; $a = null; reset_unique($u); $s = null; $w = null; unset($s); unset($u); unset($w); if (isset($u)) { return 1; } if (!empty($s)) { return 2; } return 0;',
	'presence' => '$s shared<int>; $u unique<int>; if ($s) { return 1; } elseif ($u) { return 2; } while ($u) { return 3; } for (; $s;) { return 4; } do { if (!$u) { break; } return 5; } while ($s); $n = $u ? 1 : 0; if ($s !== null) { return 6; } if (null !== $u) { return 7; } if (!($s === null)) { return 8; } if (!(null === $u)) { return 9; } return $n;',
	'observe_once' => 'function observe(shared<int> $s, int &$calls): shared<int> { $calls += 1; return $s; } $s shared<int>; $calls = 0; if (observe($s, $calls)) { return 1; } $p = observe($s, $calls) === null; $q = isset(observe($s, $calls)); $r = empty(observe($s, $calls)); if ($calls !== 4) { return 2; } if (!$p) { return 3; } if ($q) { return 4; } if (!$r) { return 5; } return 0;',
	'handles' => '$s shared<int>; $copy = $s; $w weak<int>; $observer = $w; $u unique<int>; $alias =& $u; return 0;',
	'holder' => 'struct Holder { unique<int> $item; } $h shared<Holder>; $copy = $h; $w weak<Holder>; $observer = $w; $owned unique<Holder>; return 0;',
	'container' => '$v vector<shared<int>>; $h hash<weak<int>>; $s shared<vector<shared<int>>>; return 0;',
	'borrow' => 'function &identity(unique<int> &$u): unique<int> { return $u; } $u unique<int>; $a =& identity($u); return 0;',
];
$output = $argv[1] ?? sys_get_temp_dir() . '/scpp_ownership_' . bin2hex(random_bytes(6));
if (!is_dir($output)) {
	mkdir($output, 0777, true);
}
$executions = [];
foreach ($cases as $name => $source) {
	$path = $output . '/' . $name . '.cpp';
	file_put_contents($path, ownership_source($source));
	$executions[] = ['path' => $path, 'exit_code' => 0];
}
$rejections = [];
foreach (['shared', 'unique', 'weak'] as $outer) {
	foreach (['shared', 'unique', 'weak'] as $inner) {
		$rejections[] = ['$v ' . $outer . '<' . $inner . '<int>>;', 'Direct ownership wrapper nesting'];
	}
	$rejections[] = ['$v value<' . $outer . '<int>>;', 'Value modifier'];
}
$rejections[] = ['struct Bad { shared<weak<int>> $field; }', 'Direct ownership wrapper nesting'];
$rejections[] = ['function bad(shared<unique<int>> $v): void {}', 'Direct ownership wrapper nesting'];
$rejections[] = ['$v vector<shared<shared<int>>>;', 'Direct ownership wrapper nesting'];
$rejections[] = ['$v unique<int>; $copy = $v;', 'copyable'];
$rejections[] = ['$w weak<int>; if ($w) {}', 'truthiness'];
foreach (['!$w', 'isset($w)', 'empty($w)', '$w === null', 'null !== $w'] as $expression) {
	$rejections[] = ['$w weak<int>; $test = ' . $expression . ';', 'explicit lock'];
}
foreach (['shared', 'unique', 'weak'] as $family) {
	$rejections[] = ['$v ' . $family . '<int>; clean($v);', 'clean'];
	$rejections[] = ['$v ' . $family . '<int>; $alias =& $v; unset($alias);', 'alias'];
	$rejections[] = ['$v ' . $family . '<int> = 1;', 'ownership boundary'];
}
$rejections[] = ['$u unique<int>; $copy = ($u = null);', 'copyable'];
$rejections[] = ['function consume(unique<int> $u): void {} consume(null);', 'ownership boundary'];
$rejections[] = ['function make(): unique<int> { return null; }', 'ownership boundary'];
foreach ($rejections as [$source, $message])
{
	$failed = false;
	try {
		ownership_source($source);
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $message);
		ownership_source_check($failed, 'Unexpected ownership diagnostic: ' . $error->getMessage());
	}
	ownership_source_check($failed && Model::$cpp_files->is_empty(), 'Invalid ownership source was published');
}

$directory = sys_get_temp_dir() . '/scpp_ownership_recovery_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try
{
	$valid = 'function borrow(shared<int> &$v): void {} function absent(shared<int> $v): bool { return !$v; } $s shared<int>; borrow($s); if (!absent($s)) { return 1; } return 0;';
	file_put_contents($path, $valid);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$original = Model::$cpp_files[0]->text;
	foreach (['shared<shared<int>>', 'value<shared<int>>', 'weak<int>'] as $invalid_type)
	{
		file_put_contents($path, str_replace('shared<int>', $invalid_type, $valid));
		foreach ([[$path], []] as $changes)
		{
			$failed = false;
			try {
				$compiler->update_cpp($changes);
			}
			catch (\RuntimeException $error) {
				$failed = true;
			}
			ownership_source_check($failed && Model::$cpp_files->is_empty(), 'Rejected type edit published output');
		}
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		ownership_source_check(Model::$cpp_files[0]->text === $original, 'Ownership repair changed output');
	}
	Compiler_Lifecycle::reset_preparation();
	$compiler->prepare();
	$compiler->cpp();
	ownership_source_check(Model::$cpp_files[0]->text === $original, 'Ownership cleanup/reprepare changed output');
}
finally {
	unlink($path);
	rmdir($directory);
}
file_put_contents($output . '/executions.json', json_encode($executions, JSON_PRETTY_PRINT));
echo 'Ownership source: ' . count($cases) . ' programs, ' . count($rejections) . " rejections and recovery passed\n";
