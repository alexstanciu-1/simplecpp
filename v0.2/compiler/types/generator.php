<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Function-owned concrete cursor identity; Iterator<T,K> describes its outputs, not erased storage. */
final class generator_type_definition extends type_definition implements iterable_type
{
	/** PROPERTIES */
	/** Native non-owning identity/lifetime owner. */
	public readonly ast_function_declaration $function;
	/** Native shared canonical output identity. */
	private readonly type_definition $element;
	/** Native shared canonical optional key identity. */
	private readonly ?type_definition $key;

	/** CONSTRUCTOR */
	public function __construct(ast_function_declaration $function, type_definition $element, ?type_definition $key)
	{
		parent::__construct('cursor:' . $function->symbol_key());
		$this->function = $function;
		$this->element = $element;
		$this->key = $key;
	}

	/** METHODS */
	public function cpp_type(): string
	{
		return Cpp_Emitter::function_name($this->function) . 'cursor';
	}

	public function supports_inferred_local(): bool
	{
		return true;
	}

	/** Only a fresh producer result may initialize storage; named cursor copies/moves are deferred. */
	public function prepare_scalar_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		if ($value->type_def() === $this && $value instanceof ast_call_expression
			&& $context === conversion_context::inference) return value_conversion::identity;
		throw new diagnostic($value->source(), $value->byte_start(), 'Generator cursors are move-only; initialize from a producer call');
	}

	public function iteration_method(): ?ast_function_declaration
	{
		return null;
	}

	public function iteration_value_type(): type_definition
	{
		return $this->element;
	}

	public function iteration_key_type(): ?type_definition
	{
		return $this->key;
	}

	public function lower_cpp_iteration_storage(bool $keyed): string
	{
		return $this->key !== null && !$keyed ? $this->key->cpp_type() . ' iteration_key{};' : '';
	}

	public function lower_cpp_iteration_initialize(string $receiver, string $prefix = ''): string
	{
		return '';
	}

	public function lower_cpp_iteration_next(string $receiver, string $value, ?string $key, string $prefix = ''): string
	{
		return '(' . $receiver . ').next(' . $value . ($this->key === null ? '' : ', ' . ($key ?? $prefix . 'iteration_key')) . ')';
	}

	/** @return string[] Output storage also covers a discarded key's required native headers. */
	public function cpp_headers(): array /** list<string> */
	{
		return array_merge($this->element->cpp_headers(), $this->key?->cpp_headers() ?? []);
	}
}
