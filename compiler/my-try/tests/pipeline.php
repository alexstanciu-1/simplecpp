<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
$directory = sys_get_temp_dir() . '/scpp_pipeline_' . bin2hex(random_bytes(6));
mkdir($directory);
try
{
	file_put_contents($directory . '/a.phs', 'return 1;');
	$compiler = new Compiler();
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$source = Model::$modules[$directory]->sources['a.phs']->file;
	if (!$source->disk_source || $source->content !== '' || !Model::tokens()->is_empty()) {
		throw new \LogicException('Discovery read source bytes');
	}
	file_put_contents($directory . '/a.phs', 'return 2;');
	$compiler->tokenize();
	if (Model::tokens()[0]->content !== 'return 2;' || !Model::syntax_files()->is_empty()) {
		throw new \LogicException('Tokenizer did not read the current file contents');
	}
	$compiler->parse();
	if (count(Model::syntax_files()) !== 1) {
		throw new \LogicException('Standalone parse failed');
	}
	file_put_contents($directory . '/b.phs', '$');
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$failed = false;
	try {
		$compiler->exec_llvm();
	}
	catch (\RuntimeException $expected) {
		$failed = true;
	}
	if (!$failed || count(Model::syntax_files()) !== 0 || count(Model::tokens()) !== 1 || !Model::$llvm_files->is_empty()) {
		throw new \LogicException('Tokenization failure did not stop at the phase barrier');
	}
	unlink($directory . '/b.phs');
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	unlink($directory . '/a.phs');
	try {
		$compiler->tokenize();
		throw new \LogicException('Missing file after discovery was accepted');
	}
	catch (\RuntimeException $expected) {
	}
	if (!Model::syntax_files()->is_empty() || !Model::tokens()->is_empty()) {
		throw new \LogicException('Failed read published a file result');
	}
}
finally {
	foreach (glob($directory . '/*') as $path) {
		unlink($path);
	}
	rmdir($directory);
}
echo "Pipeline: deferred reads, stage entrypoints, phase barriers and read-failure publication passed\n";
