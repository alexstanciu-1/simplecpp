<?php

declare(strict_types=1);
namespace scpp\compiler;

/** App-local, sequential host service; owns one frontend publication, never prepares or emits code. */
final class Frontend_Service
{
	private const SOCKET = '.scpp/compiler.sock';
	private const LOCK = '.scpp/compiler.lock';
	public const IDLE_SECONDS /** uint32; one hour after the last completed request */ = 3600;

	/** CLI client; only a frontend request starts a missing service. */
	public static function request(string $application, string $command = 'frontend'): array /** JSON response */
	{
		if (!in_array($command, ['frontend', 'status', 'stop'], true)) {
			throw new \RuntimeException('Expected frontend, status or stop');
		}
		$root = self::root($application);
		$cwd = getcwd();
		chdir($root); // Relative Unix-socket spelling avoids sockaddr_un's short absolute-path limit.
		try {
			if (file_exists('.scpp') || is_link('.scpp')) { self::directory(); }
			$socket = self::connect();
			if (($socket === false) && ($command !== 'frontend')) { return ['running' => false]; }
			if ($socket === false) {
				self::directory();
				$process = proc_open(['/usr/bin/setsid', '-f', PHP_BINARY, __DIR__ . '/frontend_daemon.php', $root],
					[0 => ['file', '/dev/null', 'r'], 1 => ['file', '.scpp/compiler.log', 'a'],
						2 => ['file', '.scpp/compiler.log', 'a']], $pipes, $root,
					array_merge(getenv(), ['XDEBUG_MODE' => 'off']));
				if (!is_resource($process) || proc_close($process) !== 0) {
					throw new \RuntimeException('Cannot launch frontend service');
				}
				$deadline = microtime(true) + 5;
				do {
					usleep(50000);
					$socket = self::connect();
				} while (($socket === false) && (microtime(true) < $deadline));
				if ($socket === false) { throw new \RuntimeException('Frontend service did not start; see .scpp/compiler.log'); }
			}
			try {
				stream_set_timeout($socket, 3600);
				self::write($socket, ['command' => $command]);
				$line = fgets($socket, 1048576);
				if (($line === false) || !str_ends_with($line, "\n")) {
					throw new \RuntimeException('Frontend service disconnected or timed out');
				}
				$response = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
				if (isset($response['error'])) { throw new \RuntimeException($response['error']); }
				return $response;
			} finally { fclose($socket); }
		} finally { chdir($cwd); }
	}

	/** Run detached; the optional shorter idle interval supports deterministic lifecycle tests. */
	public static function serve(string $application, int $idle_seconds /** uint32; positive seconds */ = self::IDLE_SECONDS): void
	{
		if ($idle_seconds < 1) { throw new \InvalidArgumentException('Idle interval must be positive'); }
		$root = self::root($application);
		chdir($root);
		self::directory();
		$lock = fopen(self::LOCK, 'c');
		if ($lock === false) { throw new \RuntimeException('Cannot open frontend service lock'); }
		// The service holds this lock for its entire lifetime. Concurrent starters simply exit.
		if (!flock($lock, LOCK_EX | LOCK_NB)) { fclose($lock); return; }
		$server = null;
		try {
			if (file_exists(self::SOCKET) || is_link(self::SOCKET)) {
				if (is_link(self::SOCKET) || filetype(self::SOCKET) !== 'socket') {
					throw new \RuntimeException('Frontend socket path is occupied by a non-socket');
				}
				unlink(self::SOCKET);
			}
			$server = stream_socket_server('unix://' . self::SOCKET, $errno, $error);
			if ($server === false) { throw new \RuntimeException('Cannot bind frontend socket: ' . $error); }
			chmod(self::SOCKET, 0600);
			$compiler = new Project_Compiler();
			$current = null;
			$generation = 0;
			$last_activity = hrtime(true);
			$stop = false;
			while (!$stop) {
				$remaining = $idle_seconds - (hrtime(true) - $last_activity) / 1e9;
				if ($remaining <= 0) { break; }
				$client = @stream_socket_accept($server, $remaining);
				if ($client === false) { continue; }
				try {
					stream_set_timeout($client, 5);
					$line = fgets($client, 4096);
					if (($line === false) || !str_ends_with($line, "\n")) { throw new \RuntimeException('Incomplete service request'); }
					$request = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
					$command = $request['command'] ?? '';
					$report = ['running' => true, 'pid' => getmypid(), 'application' => $root,
						'idle_seconds' => $idle_seconds, 'generation' => $generation];
					if ($command === 'frontend') {
						$start = hrtime(true);
						$candidate = $compiler->frontend($root, $current);
						$elapsed = (hrtime(true) - $start) / 1e6;
						$counts = ['added' => 0, 'changed' => 0, 'removed' => count($candidate->removed_sources), 'reused' => 0];
						foreach ($candidate->entry->sources as $path => $unit) {
							if ($unit->status === change_status::none) {
								if ($unit->ast !== $current->entry->sources[$path]->ast
									|| $unit->ast->tokens !== $current->entry->sources[$path]->ast->tokens) {
									throw new \LogicException('Frontend reuse lost AST/token identity');
								}
								$counts['reused']++;
							} else { $counts[$unit->status->name]++; }
						}
						$report += ['mode' => $current === null ? 'full' : 'incremental', 'frontend_ms' => $elapsed,
							'sources' => count($candidate->entry->sources), 'symbols' => count($candidate->symbols->declarations),
							'counts' => $counts];
						$report['generation'] = ++$generation;
						// No external AST readers: after publication, this host can retire older generations.
						$candidate->previous = null;
						$candidate->removed_sources = [];
						$current = $candidate;
						unset($candidate);
						gc_collect_cycles();
					} elseif ($command === 'stop') {
						$stop = true;
						$report['running'] = false;
					} elseif ($command !== 'status') { throw new \RuntimeException('Unknown service command'); }
					self::write($client, $report);
				} catch (\Throwable $error) {
					try { self::write($client, ['error' => $error->getMessage()]); } catch (\Throwable) {}
				} finally {
					fclose($client);
					$last_activity = hrtime(true); // Processing time never counts toward idle expiry.
				}
			}
		} finally {
			if (is_resource($server)) {
				fclose($server);
				unlink(self::SOCKET);
			}
			flock($lock, LOCK_UN);
			fclose($lock); // Keep the lock inode: unlinking it would permit split ownership.
		}
	}

	private static function root(string $application): string
	{
		$root = realpath($application);
		if (($root === false) || !is_dir($root)) { throw new \RuntimeException('Frontend service requires an application directory'); }
		return $root;
	}

	private static function directory(): void
	{
		if (is_link('.scpp')) { throw new \RuntimeException('Service directory must not be a symlink'); }
		if (!is_dir('.scpp') && !@mkdir('.scpp', 0700) && !is_dir('.scpp')) {
			throw new \RuntimeException('Cannot create .scpp service directory');
		}
		foreach ([self::LOCK, self::SOCKET, '.scpp/compiler.log'] as $path) {
			if (is_link($path)) { throw new \RuntimeException('Service paths must not be symlinks'); }
		}
	}

	private static function connect(): mixed /** resource|false; Unix client stream */
	{
		return @stream_socket_client('unix://' . self::SOCKET, $errno, $error, 0.2);
	}

	private static function write(mixed $stream /** stream resource */, array $message /** JSON object */): void
	{
		$wire = json_encode($message, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE) . "\n";
		while ($wire !== '') {
			$count = @fwrite($stream, $wire);
			if (($count === false) || ($count === 0)) { throw new \RuntimeException('Cannot write service message'); }
			$wire = substr($wire, $count);
		}
	}
}
