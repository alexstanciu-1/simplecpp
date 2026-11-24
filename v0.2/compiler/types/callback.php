<?php

declare(strict_types=1);
namespace scpp\compiler;

/** A fixed by-value signature owned by its declaring parameter, with canonical component types. */
final class callback_type_definition extends type_definition
{
	/** @param type_definition[] $inputs */
	public function __construct(public readonly type_definition $result, public readonly array $inputs /** list<type_definition> */)
	{
		$name = $result->name;
		foreach ($inputs as $input) $name .= ',' . $input->name;
		parent::__construct('callback<' . $name . '>');
	}

	public function cpp_declarator(string $name = ''): string
	{
		$result = static function (type_definition $type): string {
			$native = $type->cpp_type();
			return str_starts_with($native, 'scpp::') ? '::' . $native : $native;
		};
		$inputs = [];
		foreach ($this->inputs as $input) $inputs[] = $result($input);
		return $result($this->result) . ' (*' . $name . ')(' . implode(', ', $inputs) . ')';
	}

	public function cpp_type(): string
	{
		return $this->cpp_declarator();
	}

	public function cpp_trivial_value(): bool
	{
		return true;
	}

	/** @return string[] */
	public function cpp_headers(): array /** list<string> */
	{
		$headers = $this->result->cpp_headers();
		foreach ($this->inputs as $input) array_push($headers, ...$input->cpp_headers());
		return $headers;
	}

	public function matches(type_definition $other): bool
	{
		return $other instanceof self && $other->result === $this->result && $other->inputs === $this->inputs;
	}

	/** Only capture-free closures can cross the native function-pointer boundary. */
	public function prepare_scalar_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		$source = $value->type_def();
		if ($source instanceof lambda_type_definition && $source->has_capture) {
			throw new diagnostic($value->source(), $value->byte_start(), 'Typed callbacks require a capture-free callable');
		}
		if ($this->matches($source instanceof lambda_type_definition ? $source->signature : $source)) {
			return value_conversion::identity;
		}
		throw new diagnostic($value->source(), $value->byte_start(), 'Expected exact callback signature ' . $this->name);
	}

	public function require_target(ast_callable_reference $site, ast_function_declaration $target): void
	{
		$matches = !$target->returns_reference && $target->return_type_def() === $this->result && count($target->parameters) === count($this->inputs);
		if ($matches) {
			foreach ($target->parameters as $index => $parameter) {
				if ($parameter->mode !== passing_mode::value || $parameter->default_value !== null || $parameter->type_def() !== $this->inputs[$index]) {
					$matches = false;
					break;
				}
			}
		}
		if (!$matches) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Callback target signature mismatch: ' . $site->name . ' requires ' . $this->name);
		}
	}
}

/** Explicit callable storage owns a copy of its target and has an absent default state. */
final class owned_callable_type extends type_definition implements presence_test_type
{
	public function __construct(public readonly callback_type_definition $signature)
	{
		$inputs = [];
		foreach ($signature->inputs as $input) $inputs[] = $input->name;
		parent::__construct('function<' . $signature->result->name . '(' . implode(',', $inputs) . ')>');
	}

	public function matches(type_definition $other): bool
	{
		return $other instanceof self && $this->signature->matches($other->signature);
	}

	public function cpp_type(): string
	{
		$inputs = [];
		foreach ($this->signature->inputs as $input) $inputs[] = $input->cpp_type();
		return 'std::function<' . $this->signature->result->cpp_type() . '(' . implode(', ', $inputs) . ')>';
	}

	/** @return string[] */
	public function cpp_headers(): array /** list<string> */
	{
		return ['functional', ...$this->signature->cpp_headers()];
	}

	public function supports_instance_field(): bool
	{
		return true;
	}

	public function supports_inferred_local(): bool
	{
		return true;
	}

	/** Empty and present storage both have a defined presence test. */
	public function prepare_condition(ast_expression $value): void
	{
	}

	public function lower_cpp_condition(string $value, bool $negated = false): string
	{
		return $this->lower_cpp_presence($value);
	}

	public function lower_cpp_presence(string $value): string
	{
		return 'static_cast<bool>(' . $value . ')';
	}

	/** Erasure preserves the exact signature and admits only independently copyable environments. */
	public function prepare_scalar_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		$source = $value->type_def();
		if ($source instanceof lambda_type_definition) {
			if ($source->has_borrow) throw new diagnostic($value->source(), $value->byte_start(), 'Owned callable cannot erase a borrowed environment');
			if ($source->has_defaults) throw new diagnostic($value->source(), $value->byte_start(), 'Owned callable cannot erase parameter defaults');
		}
		$signature = $source instanceof self || $source instanceof lambda_type_definition ? $source->signature : $source;
		if ($this->signature->matches($signature)) return value_conversion::identity;
		throw new diagnostic($value->source(), $value->byte_start(), 'Expected exact callable signature ' . $this->name);
	}
}

/** Each prepared lambda expression owns its distinct concrete type. Its signature is structural. */
final class lambda_type_definition extends type_definition
{
	public readonly bool $has_capture;

	public function __construct(public readonly callback_type_definition $signature, bool $has_local_captures, public readonly bool $borrows_receiver = false,
		public readonly bool $has_borrow = false, public readonly bool $has_defaults = false)
	{
		$this->has_capture = $has_local_captures || $borrows_receiver;
		parent::__construct('lambda ' . $signature->name);
	}

	/** The native callable type has no standalone spelling. */
	public function cpp_type(): string
	{
		throw new \LogicException('Lambda types are deduced at their concrete declarations');
	}

	public function cpp_trivial_value(): bool
	{
		return true;
	}

	public function supports_inferred_local(): bool
	{
		return true;
	}
}
