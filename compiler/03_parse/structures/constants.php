<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Published integer literal from runtime metadata, e.g. PHP STR_PAD_LEFT. */
final readonly class runtime_integer_constant implements name_binding
{
	private string $key;
	public int $value /** int64 */;

	public function __construct(string $key, int $value /** int64 */, public ?string $required_module = null)
	{
		$this->key = $key;
		$this->value = $value;
	}

	public function symbol_key(): string
	{
		return $this->key;
	}
}

/** Immutable namespace or struct constant with AST-owned literal facts, e.g. Point::LIMIT. */
class ast_constant_declaration extends ast_literal_declaration
{
	public function name(): string
	{
		return ($this->parent ?? null) instanceof ast_record_declaration
			? $this->parent->name() . '::' . $this->short_name : parent::name();
	}

	public function duplicate_category(): declaration_duplicate_category
	{
		return declaration_duplicate_category::constant_declaration;
	}

	public function symbol_key(): string
	{
		return 'const:' . $this->name();
	}

	/** Return: declared-name token position for the flavor's required typed spelling. */
	public function name_position(): int /** uint32 */
	{
		return $this->source()->flavor === flavor::js ? $this->start + 1 : $this->type_reference->end;
	}

	/** Scalar payload is derived from its initializer and approved conversion, never stored a second time. */
	public function constant(): int|float|bool|null /** int64|float64|bool|null */
	{
		$value = $this->type_def()->evaluate_constant($this->initialization(), $this->initializer);
		return $value === constant_evaluation::unavailable ? null : $value;
	}

	public function constant_known(): bool
	{
		return $this->type_def()->evaluate_constant($this->initialization(), $this->initializer) !== constant_evaluation::unavailable;
	}
}

/** A folded integer watches its inputs and propagates invalidation through its own published symbol. */
final class ast_computed_integer_constant extends ast_constant_declaration implements dependency_watcher
{
	private ?Dependency_Watches $dependencies = null;
	private ?symbol_entry $entry = null;

	public function dependency_source_key(): string
	{
		return $this->source()->path;
	}

	/** Replace previous subscriptions only when re-preparing this declaration. */
	public function begin_fold(scope $symbols, Dependency_Watches $dependencies): bool
	{
		$retry = $this->entry !== null;
		if ($retry) $this->dependencies->forget($this);
		$this->dependencies = $dependencies;
		$this->entry = $symbols->entry($this->symbol_key());
		return $retry;
	}

	public function invalidate_dependencies(): void
	{
		$this->reset_preparation();
		$this->dependencies?->changed($this->entry);
	}
}
