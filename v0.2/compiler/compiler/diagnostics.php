<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * A source-language failure carrying the original source snapshot and byte offset, formatted as path:line:column.
 */
final class diagnostic extends \RuntimeException
{
	public readonly source_file $source;
	public readonly int $offset /** uint32; byte position including EOF */;

	public function __construct(source_file $source, int $offset /** uint32; byte position including EOF */, string $message)
	{
		$this->source = $source;
		$this->offset = $offset;

		$location = $source->location($offset);
		parent::__construct($source->path . ':' . $location['line'] . ':' . $location['column'] . ': ' . $message);
	}
}

/**
 * Minimal batch failure: all duplicate diagnostics, formatted one per line for the CLI.
 * Example: three declarations of ping produce two diagnostics, both naming the original location.
 */
final class diagnostic_group extends \RuntimeException
{
	public readonly array $diagnostics /** non-empty-list<diagnostic> */;

	public function __construct(array $diagnostics /** non-empty-list<diagnostic> */)
	{
		if ($diagnostics === []) { throw new \InvalidArgumentException('Empty diagnostic group'); }
		$this->diagnostics = $diagnostics;
		parent::__construct(implode("\n", array_map(static fn(diagnostic $error): string => $error->getMessage(), $diagnostics)));
	}
}
