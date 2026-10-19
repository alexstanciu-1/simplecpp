<?php
namespace scpp\compiler;

/** Explicit Linux/Clang build configuration, independent of semantic preparation. */
final class native_cpp_config
{
	public string $compiler;
	public string $runtime_include;
	public string $cache_directory;
	public bool $pch = true;
	public bool $native_debug = false;
	public int $optimization = 0;
	public function __construct(string $compiler, string $runtime_include, string $cache_directory)
	{
		$this->compiler = $compiler;
		$this->runtime_include = $runtime_include;
		$this->cache_directory = $cache_directory;
	}
}

final class native_cpp_result
{
	public string $executable = '';
	public int $compiled = 0;
	public int $reused = 0;
	public int $pch_built = 0;
	public int $linked = 0;
	public int $layouts_built = 0;
	public int $layouts_reused = 0;
}

/** Invocation-local action result, not a retained semantic record. */
final class native_cpp_artifact
{
	public string $path;
	public string $digest;
	public bool $reused;
	public function __construct(string $path, string $digest, bool $reused)
	{
		$this->path = $path;
		$this->digest = $digest;
		$this->reused = $reused;
	}
}
