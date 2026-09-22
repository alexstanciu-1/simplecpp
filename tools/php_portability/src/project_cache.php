<?php
declare(strict_types=1);

namespace scpp\portability;

require_once __DIR__ . '/declaration_index.php';
require_once __DIR__ . '/import_policy.php';

/** Single-writer filesystem discovery and immutable, OPcache-compatible token artifacts. */
final class Project_Cache {
	private array $previous;
	private array $directories = [];
	private array $files = [];
	private array $tokens = [];
	private array $pending = [];
	public array $stats = ['listed_directories' => 0, 'read_sources' => 0, 'tokenized' => 0, 'loaded_tokens' => 0];
	private string $version;
	private string $statePath;

	public function __construct(private string $source, private ?string $output, private array $map) {
		$this->version = hash('sha256', PHP_VERSION_ID . file_get_contents(__FILE__) . file_get_contents(__DIR__ . '/declaration_index.php')
			. file_get_contents(__DIR__ . '/import_policy.php') . serialize($map));
		// An in-memory scan uses the same discovery/import/token/index rules, without
		// reading or publishing persistent state. Used by the read-only check command.
		if ($output === null) { $this->previous = []; $this->statePath = ''; return; }
		$this->statePath = $output . '/.scpp-portability-index.json';
		self::safe($this->statePath);
		self::safe($output . '/.scpp-token-cache');
		$this->previous = is_file($this->statePath) ? json_decode(file_get_contents($this->statePath), true, 512, JSON_THROW_ON_ERROR) : [];
		if ($this->previous !== [] && (($this->previous['schema'] ?? null) !== 1 || !is_array($this->previous['files'] ?? null)
			|| !is_array($this->previous['directories'] ?? null))) { throw new \RuntimeException('Invalid portability index'); }
		foreach ($this->previous['files'] ?? [] as $relative => $row) {
			if (!is_string($relative) || str_starts_with($relative, '/') || str_contains($relative, '..')
				|| str_contains($relative, '\\') || !str_ends_with($relative, '.php') || !is_array($row)
				|| !is_array($row['stat'] ?? null) || !is_int($row['checked_at'] ?? null) || !is_array($row['declarations'] ?? null)) {
				throw new \RuntimeException('Invalid portability index file record');
			}
			foreach (['hash', 'cache', 'cache_hash'] as $field) {
				if (!is_string($row[$field] ?? null) || !preg_match('/^[a-f0-9]{64}$/D', $row[$field])) {
					throw new \RuntimeException('Invalid portability index fingerprint');
				}
			}
		}
		foreach ($this->previous['directories'] ?? [] as $row) {
			if (!is_array($row) || !is_array($row['stat'] ?? null) || !is_int($row['checked_at'] ?? null) || !is_array($row['names'] ?? null)) {
				throw new \RuntimeException('Invalid portability index directory record');
			}
		}
	}

	private static function safe(string $path): void {
		if (is_link($path)) { throw new \RuntimeException('Symlink portability cache is unsupported: ' . $path); }
	}

	private static function signature(string $path): array {
		clearstatcache(true, $path);
		self::safe($path);
		$stat = stat($path);
		if ($stat === false) { throw new \RuntimeException('Cannot stat ' . $path); }
		return array_intersect_key($stat, array_flip(['dev', 'ino', 'mode', 'size', 'mtime', 'ctime']));
	}

	private static function stable(array $old, array $stat): bool {
		// A same-second change can leave all coarse PHP stat fields unchanged. Hash/list
		// again until the previous observation is strictly later than both timestamps.
		return ($old['stat'] ?? null) === $stat && ($old['checked_at'] ?? 0) > max($stat['mtime'], $stat['ctime']);
	}

	private function discover(string $relative): array {
		$path = $this->source . ($relative === '' ? '' : '/' . $relative);
		$before = self::signature($path);
		$old = $this->previous['directories'][$relative] ?? [];
		if (($this->previous['source'] ?? null) === $this->source && self::stable($old, $before)) {
			$row = $old;
		} else {
			$checked = time();
			$names = scandir($path);
			if ($names === false) { throw new \RuntimeException('Cannot list source directory ' . $path); }
			$names = array_values(array_diff($names, ['.', '..']));
			$row = ['stat' => $before, 'checked_at' => $checked, 'names' => $names];
			++$this->stats['listed_directories'];
		}
		$this->directories[$relative] = $row;
		$found = [];
		foreach ($row['names'] as $name) {
			if (!is_string($name) || $name === '' || str_contains($name, '/') || in_array($name, ['.', '..'], true)) { throw new \RuntimeException('Invalid cached directory member'); }
			$child = ($relative === '' ? '' : $relative . '/') . $name;
			$absolute = $this->source . '/' . $child;
			self::safe($absolute);
			if (is_dir($absolute)) { array_push($found, ...$this->discover($child)); }
			elseif (is_file($absolute) && str_ends_with($name, '.php')) { $found[] = $child; }
		}
		if (self::signature($path) !== $before) { throw new \RuntimeException('Source directory changed during discovery: ' . $path); }
		return $found;
	}

	public function scan(): array {
		$policy = new Import_Policy($this->map);
		foreach ($this->discover('') as $relative) {
			$path = $this->source . '/' . $relative;
			$stat = self::signature($path);
			$old = ($this->previous['version'] ?? null) === $this->version && ($this->previous['source'] ?? null) === $this->source
				? ($this->previous['files'][$relative] ?? []) : [];
			if (self::stable($old, $stat)) { $this->files[$relative] = $old; continue; }
			$checked = time();
			$bytes = file_get_contents($path);
			if ($bytes === false || self::signature($path) !== $stat) { throw new \RuntimeException('Source changed while reading: ' . $relative); }
			++$this->stats['read_sources'];
			$hash = hash('sha256', $bytes);
			if (($old['hash'] ?? null) === $hash) {
				$old['stat'] = $stat;
				$old['checked_at'] = $checked;
				$this->files[$relative] = $old;
				continue;
			}
			try { $raw = token_get_all($policy->prepare($bytes, $relative), TOKEN_PARSE); }
			catch (\ParseError $error) { throw new \RuntimeException($relative . ':' . $error->getLine() . ': ' . $error->getMessage()); }
			$tokens = [];
			$line = 1;
			foreach ($raw as $token) {
				[$id, $text, $start] = is_array($token) ? $token : [0, $token, $line];
				$tokens[] = [$id, $text, $start, $relative];
				$line = $start + substr_count($text, "\n");
			}
			++$this->stats['tokenized'];
			$key = hash('sha256', $this->version . $relative . $hash);
			$artifact = '<?php return ' . var_export($tokens, true) . ";\n";
			$this->files[$relative] = ['stat' => $stat, 'checked_at' => $checked, 'hash' => $hash, 'cache' => $key,
				'cache_hash' => hash('sha256', $artifact), 'declarations' => Declaration_Index::inspect($tokens, $relative)];
			$this->tokens[$relative] = $tokens;
			$this->pending[$key] = $artifact;
		}
		ksort($this->files);
		return $this->files;
	}

	private function cachePath(string $key): string {
		if ($this->output === null) { throw new \LogicException('In-memory source scan has no persistent cache'); }
		if (!preg_match('/^[a-f0-9]{64}$/D', $key)) { throw new \RuntimeException('Invalid token cache key'); }
		$path = $this->output . '/.scpp-token-cache/' . $key . '.php-cache';
		self::safe(dirname($path));
		self::safe($path);
		return $path;
	}

	public function load(string $relative): array {
		if (isset($this->tokens[$relative])) { return $this->tokens[$relative]; }
		$row = $this->files[$relative];
		$path = $this->cachePath($row['cache']);
		if (!is_file($path) || hash_file('sha256', $path) !== $row['cache_hash']) {
			throw new \RuntimeException('Missing or modified token cache; remove the portability index to rebuild: ' . $relative);
		}
		++$this->stats['loaded_tokens'];
		$tokens = require $path;
		if (!is_array($tokens)) { throw new \RuntimeException('Invalid token cache payload'); }
		return $this->tokens[$relative] = $tokens;
	}

	/** Preflight all cache collisions before publishing any generated source. */
	public function preflight(): void {
		foreach ($this->pending as $key => $bytes) {
			$path = $this->cachePath($key);
			if (file_exists($path) && (!is_file($path) || hash_file('sha256', $path) !== hash('sha256', $bytes))) {
				throw new \RuntimeException('Refusing to replace modified token cache: ' . $path);
			}
		}
	}

	public function publish(callable $publish): void {
		if ($this->output === null) { throw new \LogicException('Cannot publish an in-memory source scan'); }
		foreach ($this->pending as $key => $bytes) { $publish($this->cachePath($key), $bytes); }
		$state = ['schema' => 1, 'version' => $this->version, 'source' => $this->source,
			'directories' => $this->directories, 'files' => $this->files];
		$publish($this->statePath, json_encode($state, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");
		// Removed/changed file entries disappear from the index. Retire only verified
		// artifacts referenced by the old index, never unrelated cache-directory files.
		$retained = array_column($this->files, 'cache');
		foreach ($this->previous['files'] ?? [] as $row) {
			if (in_array($row['cache'], $retained, true)) { continue; }
			$path = $this->cachePath($row['cache']);
			if (is_file($path) && hash_file('sha256', $path) === $row['cache_hash']) { unlink($path); }
		}
	}
}
