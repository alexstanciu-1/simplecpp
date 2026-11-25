<?php

declare(strict_types=1);

namespace Prism\Bridge\Php;

/**
 * Web-shaped request forwarded to a Simple C++ FastCGI application.
 */
final class ScppRequest {
	public string $method = 'GET';
	public string $path = '/';
	public string $query_string = '';
	public string $body = '';

	/**
	 * @var array<string, string>
	 */
	public array $headers = [];

	/**
	 * @var array<string, string>
	 */
	public array $cookies = [];

	/**
	 * @var array<string, string>
	 */
	public array $server = [];

	/**
	 * @param array<string, string>|string $query
	 * @param array<string, string> $headers
	 * @param array<string, string> $cookies
	 * @param array<string, string> $server
	 */
	public static function create(
		string $path,
		string $method = 'GET',
		array|string $query = '',
		string $body = '',
		array $headers = [],
		array $cookies = [],
		array $server = []
	): self {
		$request = new self();
		$request->path = $path;
		$request->method = strtoupper($method);
		$request->query_string = is_array($query)
			? http_build_query($query)
			: ltrim($query, '?');
		$request->body = $body;
		$request->headers = $headers;
		$request->cookies = $cookies;
		$request->server = $server;

		return $request;
	}
}
