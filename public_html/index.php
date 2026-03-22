<?php

if (false) {
	$files = glob(realpath("../simple_cpp/php_generator/samples/")."/*/*.php");
	var_dump(realpath("../simple_cpp/php_generator/samples/"));
	
	foreach ($files as $f) {
		
		$source_code = file_get_contents($f);
		$ast_vers_used = 120; # max(\ast\get_supported_versions());
		$ast = \ast\parse_code($source_code, $ast_vers_used);
		$json = 
				[
					'php_version' => PHP_VERSION,
					'php_ast_extension_version' => phpversion('ast'),
					'ast_version_used' => $ast_vers_used,
					'supported_versions' => \ast\get_supported_versions(),
					'tokens' => token_get_all($source_code),
					'ast' => $ast,
				];
		file_put_contents($f.".json", json_encode($json));
	}
	
	var_dump($files);
	die;
}

if ($_GET['export_php_ast'] ?? false) {
	
	$source_code = file_get_contents("php://input") ?: '<?php echo "works";';
	// echo "ok!";
	
	$ast_vers_used = 120; # max(\ast\get_supported_versions());
	
	$ast = \ast\parse_code($source_code, $ast_vers_used);
	/*
	var_dump( max(\ast\get_supported_versions()),$ast);
	
	function ast_for_json(\ast\Node $node) {
		$ret = (object)$node;
		foreach ($node->chidren ?? [] as $child) {
			$ret->chidren[] = ($child instanceof \ast\Node) ? ast_for_json($child) : $child;
		}
		return $ret;
	}
	*/
	// var_dump($ast);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode([
		'php_version' => PHP_VERSION,
		'php_ast_extension_version' => phpversion('ast'),
		'ast_version_used' => $ast_vers_used,
		'supported_versions' => \ast\get_supported_versions(),
		'tokens' => token_get_all($source_code),
		'ast' => $ast,
	]);
	
	exit;
}

if ($_SERVER['REQUEST_URI'] === '/simple-cpp/master_specs.csv') {
	$mime = mime_content_type(__DIR__."/master_specs.csv");
	header('Content-Type: application/csv');
	header('Content-Disposition: attachment; filename="master_specs.csv";');
	readfile(__DIR__."/master_specs.csv");
	exit;
}

require_once __DIR__ . '/../generator/from_php.php';
require_once __DIR__ . '/../tests/public_html/index.php';

/*
// 1. The User's Code
$user_code = <<<'PHP'
<?php
function test(int $a): void {
    echo $a;
}

test(10);
PHP;

try
{
	$ast = ast\parse_code($user_code, $version = 85);
	$compiler = new \simplecpp\generator\from_php();
	$cpp_output = $compiler->compile($ast);

	// --- COMPILATION & EXECUTION ---

	// 1. Save to a temporary file
	$file_path = __DIR__ . '/temp_output.from_php';
	$bin_path = __DIR__ . '/temp_output.out';
	file_put_contents($file_path, $cpp_output);

	// 2. Compile using g++
	// '2>&1' captures any compiler errors so we can see them in PHP
	$compile_cmd = 'export PATH=$PATH:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/games:/usr/local/games:/snap/bin && ' . 
					"g++ " . escapeshellarg($file_path) . " -o " . escapeshellarg($bin_path) . " 2>&1";
	$compile_cmd = 'export PATH=$PATH:/usr/local/bin:/usr/bin && ' . 
               "g++ -std=c++20 -Wshadow -Werror " . escapeshellarg($file_path) . 
               " -o " . escapeshellarg($bin_path) . " 2>&1";
	$compile_cmd = 'export PATH=$PATH:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/games:/usr/local/games:/snap/bin && ' . 
                "g++ -std=c++23 " . escapeshellarg($file_path) . " -o " . escapeshellarg($bin_path) . " 2>&1";
	exec($compile_cmd, $compile_output, $compile_status);

	if ($compile_status !== 0) {
		echo "<h3>C++ Compilation Error:</h3><pre>" . implode("\n", $compile_output) . "</pre>";
	} else {
		// 3. Run the binary
		$run_cmd = escapeshellcmd($bin_path);
		exec($run_cmd, $run_output);

		echo "<h3>Execution Result:</h3><pre>" . implode("\n", $run_output) . "</pre>";
	}

	echo "<h3>Generated C++ Source:</h3><pre>" . htmlspecialchars($cpp_output) . "</pre>";
	echo "<h3>PHP Source:</h3><pre>" . htmlspecialchars($user_code) . "</pre>";
}
catch (\Exception $e) {
	echo "<h3 style='color:red;'>Transpilation Error:</h3>";
	echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
	echo "\n";
	echo "<pre>
File: {$e->getFile()}
Line: {$e->getLine()}\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
*/