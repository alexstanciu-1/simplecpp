<?php

declare(strict_types=1);

namespace Prism\Bridge\Php;

/**
 * Minimal FastCGI bridge for calling Simple C++ applications from PHP.
 *
 * Design goals:
 * - web-shaped request surface
 * - auto-start on demand
 * - actual health probe instead of socket-only checks
 * - no dependency on nginx config syntax in the public API
 */
final class ScppFastCgiBridge {
	private const FCGI_VERSION_1 = 1;
	private const FCGI_BEGIN_REQUEST = 1;
	private const FCGI_END_REQUEST = 3;
	private const FCGI_PARAMS = 4;
	private const FCGI_STDIN = 5;
	private const FCGI_STDOUT = 6;
	private const FCGI_STDERR = 7;
	private const FCGI_RESPONDER = 1;
	private const FCGI_REQUEST_COMPLETE = 0;

	/**
	 * @var array<string, ScppAppConfig>
	 */
	private array $apps = [];

	/**
	 * @param array<string, array<string, mixed>|ScppAppConfig> $apps
	 */
	public function __construct(array $apps) {
		foreach ($apps as $app_id => $config) {
			$this->apps[$app_id] = $config instanceof ScppAppConfig
				? $config
				: ScppAppConfig::from_array(['app_id' => $app_id] + $config);
		}
	}

	/**
	 * Public convenience API with a simple signature.
	 *
	 * @param array<string, string>|string $query
	 * @param array<string, string> $headers
	 * @param array<string, string> $cookies
	 * @param array<string, string> $server
	 */
	public function scpp_call(
		string $app_id,
		string $path,
		string $method = 'GET',
		array|string $query = '',
		string $body = '',
		array $headers = [],
		array $cookies = [],
		array $server = []
	): ScppResponse {
		$request = ScppRequest::create($path, $method, $query, $body, $headers, $cookies, $server);
		return $this->call($app_id, $request);
	}

	public function call(string $app_id, ScppRequest $request): ScppResponse {
		$config = $this->require_app($app_id);
		$this->ensure_running($config);
		return $this->send_request($config, $request);
	}

	private function require_app(string $app_id): ScppAppConfig {
		if (!isset($this->apps[$app_id])) {
			throw new ScppBridgeException('Unknown Simple C++ app id: ' . $app_id);
		}

		return $this->apps[$app_id];
	}

	private function ensure_running(ScppAppConfig $config): void {
		if ($this->is_healthy($config)) {
			return;
		}

		if (!$config->auto_start) {
			throw new ScppBridgeException('Simple C++ app is not healthy and auto_start is disabled: ' . $config->app_id);
		}

		$lock_handle = $this->open_startup_lock($config->startup_lock_path);
		try {
			if (!flock($lock_handle, LOCK_EX)) {
				throw new ScppBridgeException('Failed to acquire startup lock: ' . $config->startup_lock_path);
			}

			if ($this->is_healthy($config)) {
				return;
			}

			$this->remove_stale_socket_if_possible($config);
			$this->start_process($config);
			$this->wait_until_healthy($config);
		} finally {
			flock($lock_handle, LOCK_UN);
			fclose($lock_handle);
		}
	}

	private function open_startup_lock(string $lock_path) {
		$lock_dir = dirname($lock_path);
		if (!is_dir($lock_dir) && !@mkdir($lock_dir, 0777, true) && !is_dir($lock_dir)) {
			throw new ScppBridgeException('Failed to create lock directory: ' . $lock_dir);
		}

		$handle = fopen($lock_path, 'c+');
		if ($handle === false) {
			throw new ScppBridgeException('Failed to open startup lock: ' . $lock_path);
		}

		return $handle;
	}

	private function is_healthy(ScppAppConfig $config): bool {
		if (!is_string($config->socket_path) || $config->socket_path === '') {
			return false;
		}

		if (!file_exists($config->socket_path)) {
			return false;
		}

		try {
			$response = $this->send_request($config, ScppRequest::create($config->health_path, 'GET'));
			return $response->status_code >= 200 && $response->status_code < 500;
		} catch (\Throwable) {
			return false;
		}
	}

	private function remove_stale_socket_if_possible(ScppAppConfig $config): void {
		if (!file_exists($config->socket_path)) {
			return;
		}

		if (@unlink($config->socket_path)) {
			return;
		}

		clearstatcache(true, $config->socket_path);
		if (file_exists($config->socket_path)) {
			throw new ScppBridgeException('Stale socket exists and could not be removed: ' . $config->socket_path);
		}
	}

	private function start_process(ScppAppConfig $config): void {
		$socket_dir = dirname($config->socket_path);
		if (!is_dir($socket_dir) && !@mkdir($socket_dir, 0777, true) && !is_dir($socket_dir)) {
			throw new ScppBridgeException('Failed to create socket directory: ' . $socket_dir);
		}

		$stderr_dir = dirname($config->stderr_log_path);
		if (!is_dir($stderr_dir) && !@mkdir($stderr_dir, 0777, true) && !is_dir($stderr_dir)) {
			throw new ScppBridgeException('Failed to create log directory: ' . $stderr_dir);
		}

		$env = $config->env + [
			'SCPP_BIND' => $config->bind,
			'SCPP_SOCKET_PATH' => $config->socket_path,
		];

		if (PHP_OS_FAMILY !== 'Windows') {
			$this->start_detached_unix_process($config, $env);
			return;
		}

		$this->start_attached_process($config, $env);
	}

	/**
	 * @param array<string, string> $env
	 */
	private function start_detached_unix_process(ScppAppConfig $config, array $env): void {
		$stdout_target = '/dev/null';
		$stderr_target = escapeshellarg($config->stderr_log_path);
		$cwd = escapeshellarg($config->cwd !== '' ? $config->cwd : getcwd());
		$command = $this->build_shell_command($config->command);
		$env_prefix = $this->build_shell_env_prefix($env);
		$shell_command = 'cd ' . $cwd . ' && ' . $env_prefix . ' nohup ' . $command . ' >>' . $stdout_target . ' 2>>' . $stderr_target . ' < /dev/null &';

		$descriptors = [
			0 => ['pipe', 'r'],
			1 => ['file', '/dev/null', 'a'],
			2 => ['file', '/dev/null', 'a'],
		];

		$process = proc_open(['/bin/sh', '-lc', $shell_command], $descriptors, $pipes);
		if (!is_resource($process)) {
			throw new ScppBridgeException('Failed to start detached Simple C++ app: ' . $config->app_id);
		}

		foreach ($pipes as $pipe) {
			if (is_resource($pipe)) {
				fclose($pipe);
			}
		}

		$status = proc_get_status($process);
		$exit_code = proc_close($process);
		if (($status['running'] ?? false) === false && $exit_code !== 0) {
			throw new ScppBridgeException('Detached launcher failed for Simple C++ app: ' . $config->app_id);
		}
	}

	/**
	 * @param array<string, string> $env
	 */
	private function start_attached_process(ScppAppConfig $config, array $env): void {
		$stdout_target = 'NUL';
		$stderr_target = $config->stderr_log_path;
		$descriptors = [
			0 => ['pipe', 'r'],
			1 => ['file', $stdout_target, 'a'],
			2 => ['file', $stderr_target, 'a'],
		];

		$process = proc_open(
			$config->command,
			$descriptors,
			$pipes,
			$config->cwd !== '' ? $config->cwd : null,
			$env,
			['bypass_shell' => true]
		);

		if (!is_resource($process)) {
			throw new ScppBridgeException('Failed to start Simple C++ app: ' . $config->app_id);
		}

		foreach ($pipes as $pipe) {
			if (is_resource($pipe)) {
				fclose($pipe);
			}
		}

		$status = proc_get_status($process);
		if (!$status['running']) {
			proc_close($process);
			throw new ScppBridgeException('Simple C++ app exited immediately: ' . $config->app_id);
		}

		// The process is intentionally left running; the bridge expects a long-lived FastCGI app.
	}

	/**
	 * @param list<string> $command
	 */
	private function build_shell_command(array $command): string {
		return implode(' ', array_map('escapeshellarg', $command));
	}

	/**
	 * @param array<string, string> $env
	 */
	private function build_shell_env_prefix(array $env): string {
		$parts = [];
		foreach ($env as $name => $value) {
			$parts[] = escapeshellarg($name . '=' . $value);
		}

		if ($parts === []) {
			return '';
		}

		return 'env ' . implode(' ', $parts) . ' ';
	}

	private function wait_until_healthy(ScppAppConfig $config): void {
		$deadline_us = microtime(true) + ($config->startup_timeout_ms / 1000.0);
		do {
			if ($this->is_healthy($config)) {
				return;
			}
			usleep(50_000);
		} while (microtime(true) < $deadline_us);

		throw new ScppBridgeException('Timed out waiting for Simple C++ FastCGI app startup: ' . $config->app_id);
	}

	private function send_request(ScppAppConfig $config, ScppRequest $request): ScppResponse {
		[$stream, $request_id] = $this->open_fastcgi_stream($config);
		try {
			$this->write_record($stream, self::FCGI_BEGIN_REQUEST, $request_id, pack('nCx5', self::FCGI_RESPONDER, 0));

			$params = $this->build_params($config, $request);
			$this->write_name_value_records($stream, self::FCGI_PARAMS, $request_id, $params);
			$this->write_record($stream, self::FCGI_PARAMS, $request_id, '');
			$this->write_chunked($stream, self::FCGI_STDIN, $request_id, $request->body);
			$this->write_record($stream, self::FCGI_STDIN, $request_id, '');

			[$stdout, $stderr, $app_status, $protocol_status] = $this->read_response_records($stream, $request_id);
			if ($protocol_status !== self::FCGI_REQUEST_COMPLETE) {
				throw new ScppBridgeException('FastCGI protocol did not complete normally for app: ' . $config->app_id);
			}
			if ($app_status !== 0 && $stdout === '') {
				throw new ScppBridgeException('Simple C++ FastCGI app returned non-zero app status: ' . $app_status . '; stderr=' . $stderr);
			}

			return $this->parse_http_like_response($stdout, $stderr);
		} finally {
			fclose($stream);
		}
	}

	/**
	 * @return array{0: resource, 1: int}
	 */
	private function open_fastcgi_stream(ScppAppConfig $config): array {
		$target = 'unix://' . $config->socket_path;
		$timeout_seconds = max(0.001, $config->connect_timeout_ms / 1000.0);
		$stream = @stream_socket_client($target, $errno, $error, $timeout_seconds, STREAM_CLIENT_CONNECT);
		if ($stream === false) {
			throw new ScppBridgeException('Failed to connect to Simple C++ FastCGI socket: ' . $config->socket_path . ' (' . $errno . ' ' . $error . ')');
		}

		stream_set_timeout($stream, intdiv($config->request_timeout_ms, 1000), ($config->request_timeout_ms % 1000) * 1000);

		return [$stream, random_int(1, 65535)];
	}

	/**
	 * @return array<string, string>
	 */
	private function build_params(ScppAppConfig $config, ScppRequest $request): array {
		$content_type = $this->find_header_value($request->headers, 'Content-Type') ?? ($request->body !== '' ? 'application/json' : '');
		$cookie_header = $this->build_cookie_header($request->cookies);
		$query_string = $request->query_string;
		$request_uri = $request->path . ($query_string !== '' ? '?' . $query_string : '');
		$server_name = $request->server['SERVER_NAME'] ?? 'localhost';
		$server_port = $request->server['SERVER_PORT'] ?? '80';
		$remote_addr = $request->server['REMOTE_ADDR'] ?? '127.0.0.1';
		$https = $request->server['HTTPS'] ?? 'off';

		$params = [
			'GATEWAY_INTERFACE' => 'CGI/1.1',
			'REQUEST_METHOD' => $request->method,
			'SCRIPT_FILENAME' => $config->bin_path,
			'SCRIPT_NAME' => $request->path,
			'REQUEST_URI' => $request_uri,
			'DOCUMENT_URI' => $request->path,
			'QUERY_STRING' => $query_string,
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'SERVER_SOFTWARE' => 'prism-php-bridge/1.0',
			'SERVER_NAME' => $server_name,
			'SERVER_PORT' => $server_port,
			'REMOTE_ADDR' => $remote_addr,
			'REMOTE_PORT' => $request->server['REMOTE_PORT'] ?? '0',
			'HTTPS' => $https,
			'CONTENT_LENGTH' => (string) strlen($request->body),
			'CONTENT_TYPE' => $content_type,
			'SCPP_APP_ID' => $config->app_id,
		];

		if ($cookie_header !== '') {
			$params['HTTP_COOKIE'] = $cookie_header;
		}

		foreach ($request->headers as $header_name => $header_value) {
			$normalized_name = strtoupper(str_replace('-', '_', $header_name));
			if ($normalized_name === 'CONTENT_TYPE' || $normalized_name === 'CONTENT_LENGTH') {
				$params[$normalized_name] = $header_value;
				continue;
			}
			$params['HTTP_' . $normalized_name] = $header_value;
		}

		foreach ($request->server as $server_key => $server_value) {
			if ($server_key === '') {
				continue;
			}
			$params[$server_key] = $server_value;
		}

		return $params;
	}

	private function build_cookie_header(array $cookies): string {
		$parts = [];
		foreach ($cookies as $name => $value) {
			$parts[] = $name . '=' . $value;
		}

		return implode('; ', $parts);
	}

	private function find_header_value(array $headers, string $lookup_name): ?string {
		foreach ($headers as $name => $value) {
			if (strcasecmp($name, $lookup_name) === 0) {
				return $value;
			}
		}

		return null;
	}

	/**
	 * @param resource $stream
	 */
	private function write_name_value_records($stream, int $type, int $request_id, array $params): void {
		$buffer = '';
		foreach ($params as $name => $value) {
			$buffer .= $this->encode_name_value_pair($name, $value);
			while (strlen($buffer) >= 65535) {
				$this->write_record($stream, $type, $request_id, substr($buffer, 0, 65535));
				$buffer = substr($buffer, 65535);
			}
		}

		if ($buffer !== '') {
			$this->write_record($stream, $type, $request_id, $buffer);
		}
	}

	/**
	 * @param resource $stream
	 */
	private function write_chunked($stream, int $type, int $request_id, string $content): void {
		if ($content === '') {
			return;
		}

		$offset = 0;
		$length = strlen($content);
		while ($offset < $length) {
			$chunk = substr($content, $offset, 65535);
			$this->write_record($stream, $type, $request_id, $chunk);
			$offset += strlen($chunk);
		}
	}

	/**
	 * @param resource $stream
	 */
	private function write_record($stream, int $type, int $request_id, string $content): void {
		$content_length = strlen($content);
		$padding_length = (8 - ($content_length % 8)) % 8;
		$header = pack(
			'CCnnCC',
			self::FCGI_VERSION_1,
			$type,
			$request_id,
			$content_length,
			$padding_length,
			0
		);

		$this->write_all($stream, $header . $content . str_repeat("\0", $padding_length));
	}

	/**
	 * @param resource $stream
	 */
	private function write_all($stream, string $payload): void {
		$length = strlen($payload);
		$written = 0;
		while ($written < $length) {
			$result = fwrite($stream, substr($payload, $written));
			if ($result === false || $result === 0) {
				throw new ScppBridgeException('Failed to write FastCGI payload.');
			}
			$written += $result;
		}
	}

	private function encode_name_value_pair(string $name, string $value): string {
		return $this->encode_length(strlen($name))
			. $this->encode_length(strlen($value))
			. $name
			. $value;
	}

	private function encode_length(int $length): string {
		if ($length < 128) {
			return chr($length);
		}

		return pack('N', $length | 0x80000000);
	}

	/**
	 * @param resource $stream
	 * @return array{0: string, 1: string, 2: int, 3: int}
	 */
	private function read_response_records($stream, int $request_id): array {
		$stdout = '';
		$stderr = '';
		$app_status = 0;
		$protocol_status = self::FCGI_REQUEST_COMPLETE;

		while (!feof($stream)) {
			$header = $this->read_exact($stream, 8);
			if ($header === '') {
				break;
			}

			['version' => $version, 'type' => $type, 'request_id' => $record_request_id, 'content_length' => $content_length, 'padding_length' => $padding_length] = unpack(
				'Cversion/Ctype/nrequest_id/ncontent_length/Cpadding_length/Creserved',
				$header
			);

			if ($version !== self::FCGI_VERSION_1) {
				throw new ScppBridgeException('Unexpected FastCGI protocol version: ' . $version);
			}

			$content = $content_length > 0 ? $this->read_exact($stream, $content_length) : '';
			if ($padding_length > 0) {
				$this->read_exact($stream, $padding_length);
			}

			if ($record_request_id !== $request_id) {
				continue;
			}

			switch ($type) {
				case self::FCGI_STDOUT:
					$stdout .= $content;
					break;

				case self::FCGI_STDERR:
					$stderr .= $content;
					break;

				case self::FCGI_END_REQUEST:
					if (strlen($content) !== 8) {
						throw new ScppBridgeException('Invalid FastCGI END_REQUEST payload length.');
					}
					$end_data = unpack('Napp_status/Cprotocol_status/C3reserved', $content);
					$app_status = $end_data['app_status'];
					$protocol_status = $end_data['protocol_status'];
					return [$stdout, $stderr, $app_status, $protocol_status];
			}
		}

		return [$stdout, $stderr, $app_status, $protocol_status];
	}

	/**
	 * @param resource $stream
	 */
	private function read_exact($stream, int $length): string {
		$buffer = '';
		while (strlen($buffer) < $length) {
			$chunk = fread($stream, $length - strlen($buffer));
			if ($chunk === false) {
				throw new ScppBridgeException('Failed reading FastCGI response.');
			}
			if ($chunk === '') {
				$meta = stream_get_meta_data($stream);
				if (($meta['timed_out'] ?? false) === true) {
					throw new ScppBridgeException('Timed out reading FastCGI response.');
				}
				if (feof($stream)) {
					break;
				}
				continue;
			}
			$buffer .= $chunk;
		}

		if (strlen($buffer) !== $length) {
			throw new ScppBridgeException('Unexpected EOF while reading FastCGI response.');
		}

		return $buffer;
	}

	private function parse_http_like_response(string $stdout, string $stderr): ScppResponse {
		$separator = str_contains($stdout, "\r\n\r\n") ? "\r\n\r\n" : "\n\n";
		$parts = explode($separator, $stdout, 2);
		if (count($parts) !== 2) {
			throw new ScppBridgeException('FastCGI response does not contain HTTP-like headers. stderr=' . $stderr);
		}

		[$raw_headers, $body] = $parts;
		$headers = [];
		$status_code = 200;
		foreach (preg_split("/(\r\n|\n|\r)/", $raw_headers) as $line) {
			if ($line === '' || !str_contains($line, ':')) {
				continue;
			}
			[$name, $value] = explode(':', $line, 2);
			$name = trim($name);
			$value = trim($value);
			if (strcasecmp($name, 'Status') === 0) {
				$status_code = (int) strtok($value, ' ');
				continue;
			}
			$headers[$name] = $value;
		}

		return new ScppResponse($status_code, $headers, $body);
	}
}
