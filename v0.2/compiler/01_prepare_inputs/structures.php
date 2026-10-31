<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Source identity and selection, before loading its bytes. */
final readonly class source_input
{
	public string $path;
	public flavor $flavor;

	public function __construct(string $path, flavor $flavor)
	{
		$this->path = $path;
		$this->flavor = $flavor;
	}
}

/** Immutable input snapshot; every downstream span refers to these exact bytes. */
final readonly class source_file
{
	/** @var list<int> */
	private array $line_starts /** list<uint32>; element offsets, count can reach 0x100000000 */;

	public string $path;
	public string $content;
	public flavor $flavor;

	public function __construct(string $path, string $content, flavor $flavor)
	{
		$this->path = $path;
		$this->content = $content;
		$this->flavor = $flavor;

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
	public function location(int $offset /** uint32; byte position including EOF */): array /** array{line:uint64,column:uint64}; each in 1..0x100000000 */
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

/** Explicit source membership; map keys are stable root-relative identities, not input positions. */
final readonly class program_sources
{
	public string $entry_path;
	public array $sources /** array<string,source_file>; input order retained */;

	public function __construct(string $entry_path, array $sources /** array<string,source_file> */)
	{
		$this->entry_path = $entry_path;
		$this->sources = $sources;
	}
}
