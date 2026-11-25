<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * One source import binding, not a declaration or a namespace alias.
 * PHP example: use function Lib\ping as run; target is Lib\ping and local_name is run.
 * Grouped imports retain one node per member; the file owns all occurrences, including repeats.
 */
final class ast_import extends ast_node
{
	/** Absolute canonical target without a leading root marker. */
	public readonly string $target;
	public readonly string $local_name;
	public readonly import_kind $kind;

	/** $start and $end are token boundaries, with exclusive end. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */,
		string $target, string $local_name, import_kind $kind)
	{
		parent::__construct($start, $end);
		$this->target = $target;
		$this->local_name = $local_name;
		$this->kind = $kind;
	}
}
