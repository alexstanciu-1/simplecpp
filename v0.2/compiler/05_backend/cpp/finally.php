<?php

declare(strict_types=1);
namespace scpp\compiler;

/** A protected callable returns one exit and owns a saved value only when returning a value. */
final class Cpp_Finally_Exit
{
	/** PROPERTIES */
	/** Native non-owning loop target, absent for a region with returns alone. */
	public readonly ?ast_repeating_statement $loop_target;
	private readonly string $result;
	private readonly ?string $type;
	private readonly string $outcome;

	/** CONSTRUCTOR */
	public function __construct(int $position /** uint32 */, ?string $type, ?ast_repeating_statement $loop_target)
	{
		$this->loop_target = $loop_target;
		$this->type = $type;
		$this->result = 'scpp_pending_exit_' . $position;
		$this->outcome = 'scpp_exit_outcome_' . $position;
	}

	/** METHODS */
	/** Only a saved value activates the named union; all other exits have empty storage. */
	public function declaration(): string
	{
		$tag = 'enum Kind : unsigned char { completed, exit_return, exit_break, exit_continue }; ';
		$value = $this->type === null ? '' : 'using Value = ' . $this->type . '; '
			. 'union Storage { unsigned char empty = 0; Value value; ~Storage() {} } slot{}; ';
		$cleanup = $this->type === null ? '' : ' ~' . $this->outcome . '() { if (kind == exit_return) slot.value.~Value(); }';
		return 'struct ' . $this->outcome . ' { ' . $tag . $value . 'Kind kind = completed;' . $cleanup . ' };';
	}

	public function start(): string
	{
		return '[[maybe_unused]] auto ' . $this->result . ' = [&]() -> ' . $this->outcome . ' {';
	}

	public function save(int $exit /** uint8 */, ?string $value = null): string
	{
		$storage = $value === null ? '' : '.slot = {.value = ' . $value . '}, ';
		return 'return {' . $storage . '.kind = ' . $this->outcome . '::exit_' . $this->keyword($exit) . '};';
	}

	/** Replay a loop transfer through another finalizer only if it protects the same target. */
	public function replay(int $exit /** uint8 */, ?self $outer): string
	{
		$value = $exit === statement_completion::returns && $this->type !== null
			? 'static_cast<' . $this->type . '&&>(' . $this->result . '.slot.value)' : null;
		$keyword = $this->keyword($exit);
		$resume = $keyword . ($value === null ? '' : ' ' . $value) . ';';
		if ($outer !== null && ($exit === statement_completion::returns || $outer->loop_target === $this->loop_target)) {
			$resume = $outer->save($exit, $value);
		}
		return 'if (' . $this->result . '.kind == ' . $this->outcome . '::exit_' . $keyword . ') { ' . $resume . ' }';
	}

	/** Spell either the direct statement or its namespaced pending-exit tag. */
	private function keyword(int $exit /** uint8 */): string
	{
		return match ($exit) {
			statement_completion::returns => 'return',
			statement_completion::breaks => 'break',
			statement_completion::continues => 'continue',
			default => throw new \LogicException('Unexpected finally exit'),
		};
	}
}
