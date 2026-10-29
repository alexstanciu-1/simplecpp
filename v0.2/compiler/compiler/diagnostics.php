<?php

declare(strict_types=1);
namespace scpp\compiler;

final class diagnostic extends \RuntimeException
{
	public function __construct(public readonly source_file $source, public readonly int $offset, string $message)
	{
		$location = $source->location($offset);
		parent::__construct($source->path . ':' . $location['line'] . ':' . $location['column'] . ': ' . $message);
	}
}
