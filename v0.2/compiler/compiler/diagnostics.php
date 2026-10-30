<?php

declare(strict_types=1);
namespace scpp\compiler;

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
