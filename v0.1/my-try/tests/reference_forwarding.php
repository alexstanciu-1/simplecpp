<?php

/* Cross-file body-only cycle edits must invalidate completion without inventing body dependencies. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function forwarding_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

$directory = sys_get_temp_dir() . '/scpp_forwarding_' . bin2hex(random_bytes(6));
mkdir($directory);
$caller_path = $directory . '/caller.phs';
$provider_path = $directory . '/provider.phs';
$caller = 'function &forward(int &$x): int { return middle($x); } $n = 1; $a =& forward($n); $a = 8; return $n;';
$valid = 'function &middle(int &$x): int { $a =& leaf($x); return $a; } function &leaf(int &$x): int { return $x; }';
try
{
	file_put_contents($caller_path, $caller);
	file_put_contents($provider_path, $valid);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(new cpp_lowering_options(false));
	$compiler->init([$directory]);
	$compiler->sync([]);
	// Multi-source preparation is supported; the current C++ assembly still accepts one file.
	$compiler->prepare();
	forwarding_check(count(Model::$prepared_files) === 2, 'Cross-file preparation did not complete');
	$forward = Model::$global_scope->functions_named('forward')[0]->syntax();
	$forward_body = $forward->body;
	$original_place = $forward_body->statements[0]->expression->require_call_preparation()->place;
	forwarding_check($original_place->storage() === $forward->require_preparation()->parameters[0], 'Forwarded place lost its caller parameter');

	foreach ([
		[str_replace('return $x;', 'return forward($x);', $valid), 'recursive reference-return cycles'],
		[str_replace('return $x;', '$local = 1; return $local;', $valid), 'cannot escape'],
		[str_replace('function &leaf', 'function leaf', $valid), 'stable storage'],
	] as [$invalid, $diagnostic])
	{
		file_put_contents($provider_path, $invalid);
		foreach ([[$provider_path], []] as $changed)
		{
			$compiler->sync($changed);
			$failed = false;
			try {
				$compiler->prepare();
			}
			catch (\RuntimeException $error) {
				$failed = str_contains($error->getMessage(), $diagnostic);
			}
			forwarding_check($failed && Model::$prepared_files->is_empty(), 'Unsafe cross-file edit published completed preparation: ' . $diagnostic);
			if ($diagnostic === 'recursive reference-return cycles') {
				forwarding_check(!Preparation_Status::complete($forward_body->work()), 'Cycle rejection left caller work ready for emission');
			}
		}
		forwarding_check($forward->body === $forward_body, 'Unchanged forwarding syntax was replaced');
		file_put_contents($provider_path, $valid);
		$compiler->sync([$provider_path]);
		$compiler->prepare();
		forwarding_check(count(Model::$prepared_files) === 2, 'Reference forwarding recovery did not settle');
	}
	// Removing a formerly cyclic but unused declaration must also remove its call edges.
	file_put_contents($provider_path, $valid . ' function &unused(int &$x): int { return unused($x); }');
	$failed = false;
	try {
		$compiler->sync([$provider_path]);
		$compiler->prepare();
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), 'recursive reference-return cycles');
	}
	forwarding_check($failed, 'Unused recursive reference function escaped validation');
	file_put_contents($provider_path, $valid);
	$compiler->sync([$provider_path]);
	$compiler->prepare();
	forwarding_check(count(Model::$prepared_files) === 2, 'Retired reference call remained in the cycle graph');
	Compiler_Lifecycle::reset();
	$clean = new Compiler(new cpp_lowering_options(false));
	$clean->init([$directory]);
	$clean->sync([]);
	$clean->prepare();
	$clean_forward = Model::$global_scope->functions_named('forward')[0]->syntax();
	$clean_place = $clean_forward->body->statements[0]->expression->require_call_preparation()->place;
	forwarding_check(count(Model::$prepared_files) === 2
		&& $clean_place->storage() === $clean_forward->require_preparation()->parameters[0], 'Clean preparation lost forwarding origin');
	echo "Reference forwarding: cross-file origins, cycles, body/signature edits, retries, retirement and recovery passed\n";
}
finally {
	unlink($caller_path);
	unlink($provider_path);
	rmdir($directory);
}
