<?php

declare(strict_types=1);

namespace Prism\Bridge\Php;

/**
 * Runtime and startup configuration for a Simple C++ FastCGI application.
 */
final class ScppAppConfig {
	public string $app_id;
	public string $bin_path;
	public string $socket_path;
	public string $health_path = '/__health';
	public string $cwd = '';
	public string $bind = '';
	public bool $auto_start = true;
	public int $connect_timeout_ms = 200;
	public int $request_timeout_ms = 5000;
	public int $startup_timeout_ms = 3000;
	public string $startup_lock_path = '';
	public string $stderr_log_path = '';

	/**
	 * @var list<string>
	 */
	public array $command = [];

	/**
	 * @var array<string, string>
	 */
	public array $env = [];

	/**
	 * @param array<string, mixed> $data
	 */
	public static function from_array(array $data): self {
		foreach (['app_id', 'bin_path', 'socket_path'] as $required_key) {
			if (!isset($data[$required_key]) || !is_string($data[$required_key]) || $data[$required_key] === '') {
				throw new ScppBridgeException('Missing required app config key: ' . $required_key);
			}
		}

		$config = new self();
		$config->app_id = $data['app_id'];
		$config->bin_path = $data['bin_path'];
		$config->socket_path = $data['socket_path'];
		$config->health_path = (string) ($data['health_path'] ?? $config->health_path);
		$config->cwd = (string) ($data['cwd'] ?? dirname($config->bin_path));
		$config->bind = (string) ($data['bind'] ?? ('unix:' . $config->socket_path));
		$config->auto_start = (bool) ($data['auto_start'] ?? $config->auto_start);
		$config->connect_timeout_ms = (int) ($data['connect_timeout_ms'] ?? $config->connect_timeout_ms);
		$config->request_timeout_ms = (int) ($data['request_timeout_ms'] ?? $config->request_timeout_ms);
		$config->startup_timeout_ms = (int) ($data['startup_timeout_ms'] ?? $config->startup_timeout_ms);
		$config->startup_lock_path = (string) ($data['startup_lock_path'] ?? ($config->socket_path . '.lock'));
		$config->stderr_log_path = (string) ($data['stderr_log_path'] ?? ($config->socket_path . '.log'));
		$config->command = self::normalize_command($data['command'] ?? [$config->bin_path, '--bind=' . $config->bind]);
		$config->env = self::normalize_env($data['env'] ?? []);

		return $config;
	}

	/**
	 * @param mixed $command
	 * @return list<string>
	 */
	private static function normalize_command(mixed $command): array {
		if (!is_array($command) || $command === []) {
			throw new ScppBridgeException('App command must be a non-empty string list.');
		}

		$result = [];
		foreach ($command as $part) {
			if (!is_string($part) || $part === '') {
				throw new ScppBridgeException('App command contains an invalid segment.');
			}
			$result[] = $part;
		}

		return $result;
	}

	/**
	 * @param mixed $env
	 * @return array<string, string>
	 */
	private static function normalize_env(mixed $env): array {
		if (!is_array($env)) {
			throw new ScppBridgeException('App env must be an associative array of strings.');
		}

		$result = [];
		foreach ($env as $key => $value) {
			if (!is_string($key) || !is_string($value)) {
				throw new ScppBridgeException('App env must contain only string keys and values.');
			}
			$result[$key] = $value;
		}

		return $result;
	}
}
