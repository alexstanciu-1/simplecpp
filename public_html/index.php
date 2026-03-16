<?php

require_once __DIR__ . '/../generator/cpp.php';

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
	$compiler = new \simplecpp\generator\cpp();
	$cpp_output = $compiler->compile($ast);

	// --- COMPILATION & EXECUTION ---

	// 1. Save to a temporary file
	$file_path = __DIR__ . '/temp_output.cpp';
	$bin_path = __DIR__ . '/temp_output.out';
	file_put_contents($file_path, $cpp_output);

	// 2. Compile using g++
	// '2>&1' captures any compiler errors so we can see them in PHP
	$compile_cmd = 'export PATH=$PATH:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/games:/usr/local/games:/snap/bin && ' . 
					"g++ " . escapeshellarg($file_path) . " -o " . escapeshellarg($bin_path) . " 2>&1";
	$compile_cmd = 'export PATH=$PATH:/usr/local/bin:/usr/bin && ' . 
               "g++ -std=c++20 -Wshadow -Werror " . escapeshellarg($file_path) . 
               " -o " . escapeshellarg($bin_path) . " 2>&1";
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
