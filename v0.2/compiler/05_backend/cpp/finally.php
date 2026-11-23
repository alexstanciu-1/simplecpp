<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Store the exit from a protected region until its finalizer completes. */
final class Cpp_Finally_Exit
{
	/** PROPERTIES */
	private readonly ast_try_statement $region;
	private readonly string $result;
	private readonly ?string $type;
	private readonly string $outcome;
	/** Nonlocal destinations crossing this protected region, collected during private lowering. @var ast_loop_transfer[] */
	public array $routes = [];

	/** CONSTRUCTOR */
	public function __construct(ast_try_statement $region, ?string $type, private readonly bool $coroutine)
	{
		$this->region = $region;
		$this->type = $type;
		$this->result = 'scpp_pending_exit_' . $region->start;
		$this->outcome = 'scpp_exit_outcome_' . $region->start;
	}

	/** METHODS */
	public function transfer_target(int $exit): ast_repeating_statement|ast_switch_statement|null
	{
		return $exit === statement_completion::breaks ? $this->region->break_target : $this->region->continue_target;
	}

	/** An outward transfer leaves this protected region if its destination encloses the region. */
	public function crosses(ast_loop_transfer $transfer): bool
	{
		return $transfer->target->start < $this->region->start;
	}

	public function save_route(string $label, ast_loop_transfer $transfer): string
	{
		$this->routes[$label] = $transfer;
		return $this->record_exit($label, null);
	}

	public function selected(string $label): string
	{
		return 'if (' . $this->result . '.kind == ' . $this->outcome . '::' . $label . ') ';
	}

	/** Only a saved value activates the named union; all other exits have empty storage. */
	public function declaration(): string
	{
		$base = $this->routes === [] ? 'unsigned char' : 'unsigned';
		$names = implode(', ', ['completed', 'exit_return', 'exit_break', 'exit_continue', ...array_keys($this->routes)]);
		$tag = "enum Kind : $base { $names }; ";
		$value = $this->type === null ? '' : 'using Value = ' . $this->type . '; '
			. 'union Storage { unsigned char empty = 0; Value value; ~Storage() {} } slot{}; ';
		$cleanup = $this->type === null ? '' : ' ~' . $this->outcome . '() { if (kind == exit_return) slot.value.~Value(); }';
		return 'struct ' . $this->outcome . ' { ' . $tag . $value . 'Kind kind = completed;' . $cleanup . ' };';
	}

	public function start(): string
	{
		if ($this->coroutine) return $this->outcome . ' ' . $this->result . '{};';
		return '[[maybe_unused]] auto ' . $this->result . ' = [&]() -> ' . $this->outcome . ' {';
	}

	public function save(int $exit /** uint8 */, ?string $value = null): string
	{
		return $this->record_exit('exit_' . $this->keyword($exit), $value);
	}

	public function cleanup_label(): string
	{
		return 'scpp_cleanup_' . $this->region->start;
	}

	/** Construct the saved value before publishing the exit tag and leaving the protected scope. */
	private function record_exit(string $tag, ?string $value): string
	{
		if ($this->coroutine) {
			$store = $value === null ? '' : '::new (&' . $this->result . '.slot.value) ' . $this->outcome . '::Value(' . $value . '); ';
			return '{ ' . $store . $this->result . '.kind = ' . $this->outcome . '::' . $tag . '; goto ' . $this->cleanup_label() . '; }';
		}
		$storage = $value === null ? '' : '.slot = {.value = ' . $value . '}, ';
		return 'return {' . $storage . '.kind = ' . $this->outcome . '::' . $tag . '};';
	}

	/** Replay a loop transfer through another finalizer only if it protects the same target. */
	public function replay(int $exit /** uint8 */, ?self $outer): string
	{
		$value = $exit === statement_completion::returns && $this->type !== null
			? 'static_cast<' . $this->type . '&&>(' . $this->result . '.slot.value)' : null;
		$keyword = $this->keyword($exit);
		$resume = ($this->coroutine && $exit === statement_completion::returns ? 'co_return' : $keyword) . ($value === null ? '' : ' ' . $value) . ';';
		if ($outer !== null && ($exit === statement_completion::returns || $outer->transfer_target($exit) === $this->transfer_target($exit))) {
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
