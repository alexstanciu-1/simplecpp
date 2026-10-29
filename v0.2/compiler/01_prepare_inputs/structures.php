<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Immutable input snapshot; every downstream span refers to these exact bytes. */
final readonly class source_file
{
	/** @var list<int> */
	private array $line_starts;

	public function __construct(public string $path, public string $content, public flavor $flavor)
	{
		if (strlen($content) > 4294967295) {
			throw new \LengthException('Source exceeds 32-bit byte capacity');
		}
		$starts = [0];
		$offset = 0;
		while (($newline = strpos($content, "\n", $offset)) !== false) {
			$starts[] = $newline + 1;
			$offset = $newline + 1;
		}
		$this->line_starts = $starts;
	}

	/** Return one-based line and byte column, including the EOF position. */
	public function location(int $offset): array
	{
		if (($offset < 0) || ($offset > strlen($this->content))) {
			throw new \OutOfRangeException('Source offset is outside its snapshot');
		}
		$low = 0;
		$high = count($this->line_starts);
		while (($low + 1) < $high) {
			$middle = intdiv($low + $high, 2);
			if ($this->line_starts[$middle] <= $offset) {
				$low = $middle;
			} else {
				$high = $middle;
			}
		}
		return ['line' => $low + 1, 'column' => $offset - $this->line_starts[$low] + 1];
	}
}
