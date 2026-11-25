<?php
declare(strict_types=1);

namespace Scpp\S2S\Jss;

final class JssFrontendRequestFactory
{
	private int $nextRequestId = 1;
	private ?string $namespace = null;
	private string $path = '';

	public function reset(): void
	{
		$this->nextRequestId = 1;
		$this->namespace = null;
		$this->path = '';
	}

	public function setNamespace(?string $namespace): void
	{
		$this->namespace = $namespace !== '' ? $namespace : null;
	}

	public function setPath(string $path): void
	{
		$this->path = $path;
	}

	/** @param array<string,mixed> $fields @return array<string,mixed> */
	public function make(string $kind, array $fields): array
	{
		$request = array_merge([
			'id' => 'jss_req_' . $this->nextRequestId++,
			'kind' => $kind,
			'frontend' => 'jss',
			'version' => 1,
			'path' => $this->path,
		], $fields);
		if ($this->namespace !== null) {
			$request['namespace'] = $this->namespace;
		}
		return $request;
	}
}
