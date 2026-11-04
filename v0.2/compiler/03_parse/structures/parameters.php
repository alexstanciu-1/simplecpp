<?php

declare(strict_types=1);
namespace scpp\compiler;

/** A function-owned typed value parameter, indexed as body storage: function f(int $value): int. */
final class ast_parameter_declaration extends ast_node implements ast_local_binding
{
	use ast_declaration;

	/** PROPERTIES */
	public readonly string $type_name;
	public readonly int $id /** uint32 */;

	/** PREP FACTS */
	/** Prepared fact: canonical signature type; null until declaration preparation or after signature reset. */
	private ?type_definition $type_def = null;

	/** CONSTRUCTOR */
	/** $start/$end: token positions; $id: body storage ordinal. */
	public function __construct(string $name, string $type_name, int $id /** uint32 */,
		int $start /** uint32 */, int $end /** uint32 */)
	{
		$this->short_name = $name;
		$this->namespace = null;
		$this->type_name = $type_name;
		$this->id = $id;
		parent::__construct($start, $end);
	}

	/** METHODS */
	public function symbol_key(): string
	{
		return 'var:' . $this->declaration_key();
	}

	public function storage_id(): int /** uint32 */
	{
		return $this->id;
	}

	public function type_def(): type_definition
	{
		return $this->type_def ?? throw new \LogicException('Parameter type is not settled');
	}

	public function settle_type(type_definition $type): void
	{
		if ($this->type_def !== null) {
			throw new \LogicException('Parameter type already settled');
		}
		$this->type_def = $type;
	}

	public function reset_preparation(): void
	{
		$this->type_def = null;
	}

	/** Caller values are unknown while preparing a function body. */
	public function constant(): int|float|bool|null /** int64|float64|bool|null */
	{
		$this->type_def();
		return null;
	}

	public function constant_known(): bool
	{
		$this->type_def();
		return false;
	}

	/** Return: token position of the parameter name. */
	public function name_position(): int /** uint32 */
	{
		return $this->start + ($this->source()->flavor === flavor::js ? 0 : 1);
	}
}
