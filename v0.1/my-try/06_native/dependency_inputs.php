<?php
namespace scpp\compiler;

/** Native consumer's dependency-discovery cache; publication owns the generated graph. */
final class Native_Dependency_Inputs
{
	private array $generated /** hash<native_cpp_input> */ = [];
	private array $digests /** hash<string> */ = [];
	private array $visited /** hash<bool> */ = [];
	private array $directories /** hash<bool> */ = [];
	private string $lookup = '';
	public bool $complete = true;

	public function __construct(private string $cache, private string $runtime, private string $published,
		\scpp\Json_View $files)
	{
		for ($index = 0; $index < $files->size(); $index++) {
			$row = $files->at($index);
			if (!$row->has('includes')) { $this->complete = false; continue; }
			$headers = $row->member('includes');
			if ($headers->kind() === 'null') { $this->complete = false; continue; }
			if ($headers->kind() !== 'array') { throw new \RuntimeException('Invalid published include list'); }
			$input = new native_cpp_input();
			$input->digest = $row->member('sha256')->text();
			for ($item = 0; $item < $headers->size(); $item++) { $input->includes[] = $headers->at($item)->text(); }
			$this->generated[$row->member('path')->text()] = $input;
		}
	}

	/** Clang supplies its actual search roots, including absent configured directories. */
	public function observe_search(string $diagnostics): void
	{
		$roots /** vector<string> */ = [$this->published];
		$active = false;
		$line = '';
		for ($index = 0; $index <= string_byte_len($diagnostics); $index++) {
			$byte = 10;
			if ($index < string_byte_len($diagnostics)) { $byte = string_byte_at($diagnostics, $index); }
			if ($byte !== 10) { $line .= string_byte_from_int($byte); continue; }
			if (string_byte_starts_with($line, '#include <...> search starts here:')) { $active = true; }
			elseif ($line === 'End of search list.') { $active = false; }
			elseif ($active) {
				if (!string_byte_starts_with($line, ' /')) { throw new \RuntimeException('Unsupported Clang include search output'); }
				$roots[] = string_byte_slice($line, 1, string_byte_len($line));
			}
			elseif (string_byte_starts_with($line, 'ignoring nonexistent directory "')) {
				$roots[] = string_byte_slice($line, 32, string_byte_len($line) - 33);
			}
			$line = '';
		}
		if (q_count($roots) === 1) { throw new \RuntimeException('Clang did not report include search roots'); }
		$text = '';
		foreach ($roots as $root) {
			if ($root === $this->runtime) { continue; }
			$text .= $this->inventory($root);
		}
		$this->lookup = content_sha256($text);
	}

	/** Names/types/resolved symlink targets detect additions and shadowing without hashing all system headers. */
	private function inventory(string $path): string
	{
		$text = json_quote($path);
		if (fs_is_dir($path)) {
			$resolved = fs_require_realpath($path);
			$text .= ':directory:' . json_quote($resolved) . "\n";
			if (isset($this->directories[$resolved])) { return $text; }
			$this->directories[$resolved] = true;
			$names /** vector<string> */ = [];
			if (!take_false($names, fs_scan($path))) { throw new \RuntimeException('Cannot inventory include directory: ' . $path); }
			foreach ($names as $name) { $text .= $this->inventory($path . '/' . $name); }
		}
		elseif (fs_is_file($path)) {
			$text .= ':file';
			if (fs_is_link($path)) { $text .= ':' . json_quote(fs_require_realpath($path)); }
			$text .= "\n";
		}
		else { $text .= ":absent\n"; }
		return $text;
	}

	/** Body bytes affect object identity, but cannot change an explicitly described preamble. */
	public function discovery_key(string $command_key, string $source): string
	{
		$empty /** hash<bool> */ = [];
		$this->visited = $empty;
		$text = $command_key . $this->lookup;
		$relative = '';
		if (string_byte_starts_with($source, $this->published . '/')) {
			$relative = string_byte_slice($source, string_byte_len($this->published) + 1, string_byte_len($source));
		}
		if (isset($this->generated[$relative])) {
			$input = $this->generated[$relative];
			foreach ($input->includes as $include) { $text .= $this->header_key($include); }
		}
		elseif (!string_byte_starts_with($source, $this->runtime . '/')) { $text .= $this->digest($source); }
		return content_sha256($text);
	}

	private function header_key(string $name): string
	{
		$text = json_quote($name);
		if (isset($this->visited[$name])) { return $text; }
		$this->visited[$name] = true;
		if (isset($this->generated[$name])) {
			$input = $this->generated[$name];
			$text .= ':' . $input->digest;
			foreach ($input->includes as $include) { $text .= $this->header_key($include); }
		}
		return $text . "\n";
	}

	/** Empty means rediscover; external header changes invalidate discovery as well as compilation. */
	public function previous(string $key): string
	{
		$bytes = fs_output_read($this->cache, 'dependencies/' . $key . '.json');
		if ($bytes === null) { return ''; }
		$document = json_read($bytes);
		if ($document->member('format')->text() !== 'scpp-native-dependencies-v1') { throw new \RuntimeException('Unknown dependency record'); }
		$rows = $document->member('files');
		for ($index = 0; $index < $rows->size(); $index++) {
			$row = $rows->at($index);
			$path = $row->member('path')->text();
			if (string_byte_starts_with($path, $this->runtime . '/')) { continue; }
			if (string_byte_starts_with($path, $this->published . '/')) {
				$relative = string_byte_slice($path, string_byte_len($this->published) + 1, string_byte_len($path));
				if (isset($this->generated[$relative])) { continue; }
			}
			if (!fs_is_file($path)) { return ''; }
			if ($this->digest($path) !== $row->member('sha256')->text()) { return ''; }
		}
		return $bytes;
	}

	public function remember(string $key, array $paths /** vector<string> */): string
	{
		$bytes = '{"format":"scpp-native-dependencies-v1","files":[';
		$separator = '';
		foreach ($paths as $path) {
			$bytes .= $separator . '{"path":' . json_quote($path) . ',"sha256":' . json_quote($this->digest($path)) . '}';
			$separator = ',';
		}
		$bytes .= ']}';
		fs_output_write($this->cache, 'dependencies/' . $key . '.json', $bytes);
		return $bytes;
	}

	/** Runtime contents are pinned to the recorded scan; generated/external bytes stay current. */
	public function inputs_key(string $bytes): string
	{
		$document = json_read($bytes);
		$rows = $document->member('files');
		$text = '';
		for ($index = 0; $index < $rows->size(); $index++) {
			$row = $rows->at($index);
			$path = $row->member('path')->text();
			$digest = $row->member('sha256')->text();
			if (!string_byte_starts_with($path, $this->runtime . '/')) { $digest = $this->digest($path); }
			$text .= json_quote($path) . ':' . $digest . "\n";
		}
		return $text;
	}

	private function digest(string $path): string
	{
		if (!isset($this->digests[$path])) {
			$this->digests[$path] = fs_file_digest(fs_require_realpath($path));
			if ($this->digests[$path] === '') { throw new \RuntimeException('Missing native dependency: ' . $path); }
		}
		return $this->digests[$path];
	}
}
