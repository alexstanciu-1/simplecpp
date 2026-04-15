<?php

declare(strict_types=1);

namespace Prism\Bridge\Php;

/**
 * FastCGI response received from a Simple C++ application.
 */
final class ScppResponse {
	public int $status_code;

	/**
	 * @var array<string, string>
	 */
	public array $headers;

	public string $body;

	/**
	 * @param array<string, string> $headers
	 */
	public function __construct(int $status_code, array $headers, string $body) {
		$this->status_code = $status_code;
		$this->headers = $headers;
		$this->body = $body;
	}

	/**
	 * @return array<mixed>
	 */
	public function json(bool $associative = true): array {
		$decoded = json_decode($this->body, $associative);
		if (!is_array($decoded)) {
			throw new ScppBridgeException('Response body is not a JSON object/array.');
		}

		return $decoded;
	}
}
