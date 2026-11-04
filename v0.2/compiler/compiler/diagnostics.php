<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * A source-language failure carrying the original source snapshot and byte offset, formatted as path:line:column.
 */
final class diagnostic extends \RuntimeException
{
	/** Native shared-owning; diagnostic retains source bytes. */
	public readonly source_file $source;
	/** Byte position including EOF. */
	public readonly int $offset /** uint32 */;

	/** $offset: byte position including EOF. */
	public function __construct(source_file $source, int $offset /** uint32 */,
		string $message)
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
	/** Native owning diagnostic values. */
	public readonly array $diagnostics /** non-empty-list<diagnostic> */;

	/** Retain all diagnostics and expose their messages through one exception boundary. */
	public function __construct(array $diagnostics /** non-empty-list<diagnostic> */)
	{
		if ($diagnostics === []) {
			throw new \InvalidArgumentException('Empty diagnostic group');
		}
		$this->diagnostics = $diagnostics;
		parent::__construct(implode("\n", array_map(static fn(diagnostic $error): string => $error->getMessage(),
					$diagnostics)));
	}
}
