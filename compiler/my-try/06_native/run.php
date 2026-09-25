<?php

/*
 * Role: compile and execute emitted LLVM using the configured Clang.
 * Call map: Compiler::run_native / tests -> Native_Runner::run -> process; dump -> dump_streams.
 */
namespace scpp\compiler;

/** @scpp-no-export */
final class Native_Runner
{
	/** Compile/link all module files and execute, then remove temporary artifacts.
	 */
	public function run(Storage $modules /** Storage<llvm_module> */): native_result
	{
		/** @var mixed $config Decoded host configuration must be validated before use. */
		$config = json_decode($this->read_text(__DIR__ . '/toolchain.json'), true, 512, JSON_THROW_ON_ERROR);
		if (!is_array($config) || !isset($config['clang']) || !is_string($config['clang']) || ($config['clang'] === '')) {
			throw new \RuntimeException('Native toolchain requires a nonempty clang path');
		}
		$directory = sys_get_temp_dir() . '/my-try-native-' . bin2hex(random_bytes(12));
		if (!mkdir($directory, 0700)) {
			throw new \RuntimeException('Cannot create native build directory');
		}
		$sources /** vector<string> */ = [];
		try
		{
			$executable = $directory . '/program';
			foreach ($modules as $index => $module)
			{
				// Per-module directories also allow different source folders to share a basename.
				$folder = $directory . '/source_' . $index;
				if (!mkdir($folder, 0700)) {
					throw new \RuntimeException('Cannot create LLVM module directory');
				}
				$source = $folder . '/' . basename($module->file_name);
				$sources[] = $source;
				if (file_put_contents($source, $module->text) !== strlen($module->text)) {
					throw new \RuntimeException('Cannot write LLVM input');
				}
			}
			$result = new native_result();
			$command /** vector<string> */ = array_merge([$config['clang'], '-Wno-override-module', '-x', 'ir'], $sources, ['-o', $executable]);
			$result->build = $this->process($command, $directory);
			if ($result->build->exit_code === 0) {
				$result->execution = $this->process([$executable], $directory);
			}
			return $result;
		}
		finally
		{
			foreach ($sources as $source) {
				if (is_file($source)) {
					unlink($source);
				}
				rmdir(dirname($source));
			}
			foreach (['program', 'stdout', 'stderr'] as $name) {
				if (is_file($directory . '/' . $name)) {
					unlink($directory . '/' . $name);
				}
			}
			rmdir($directory);
		}
	}

	/** Capture both streams without shell interpolation or pipe-buffer deadlocks. */
	private function process(array $command /** vector<string> */, string $directory): native_process_result
	{
		$descriptors /** hash<vector<string>, int> */ = [
			0 => ['file', '/dev/null', 'r'],
			1 => ['file', $directory . '/stdout', 'w'],
			2 => ['file', $directory . '/stderr', 'w'],
		];
		/** @var array<int, resource> $pipes Host process handles; file descriptors create no pipes. */
		$pipes = [];
		/** @var resource|false $process */
		$process = proc_open($command, $descriptors, $pipes, $directory);
		if (!is_resource($process)) {
			throw new \RuntimeException('Cannot start native process');
		}
		$result = new native_process_result();
		$result->exit_code = proc_close($process);
		$result->stdout = $this->read_text($directory . '/stdout');
		$result->stderr = $this->read_text($directory . '/stderr');
		return $result;
	}

	/** Empty output is valid; failed reads must never become empty strings. */
	private function read_text(string $path): string
	{
		/** @var string|false $text Host read result, including failure. */
		$text = file_get_contents($path);
		if ($text === false) {
			throw new \RuntimeException('Cannot read native input/output: ' . $path);
		}
		return $text;
	}

	/** Display compiler diagnostics and execution output safely within the page's pre block. */
	public function dump(native_result $result): void
	{
echo "\nNative build: exit {$result->build->exit_code}\n";
		$this->dump_streams($result->build);
		if ($result->execution === null) {
			echo "Executable was not run because compilation failed.\n";
			return;
		}
echo "Executable exit code: {$result->execution->exit_code}\n";
		$this->dump_streams($result->execution);
	}

	private function dump_streams(native_process_result $result): void
	{
		echo "stdout:\n" . htmlspecialchars($result->stdout === '' ? "(empty)\n" : $result->stdout, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
		echo "stderr:\n" . htmlspecialchars($result->stderr === '' ? "(empty)\n" : $result->stderr, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
	}
}
