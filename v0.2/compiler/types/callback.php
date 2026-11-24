<?php

declare(strict_types=1);
namespace scpp\compiler;

/** A fixed-arity signature owned by its declaring parameter, with canonical component types. */
final class callback_type_definition extends type_definition
{
	/**
	 * @param type_definition[] $inputs
	 * @param passing_mode[] $modes Mutable reference positions in ascending input order.
	 */
	public function __construct(public readonly type_definition $result, public readonly array $inputs /** list<type_definition> */,
		private readonly array $modes /** array<uint32,passing_mode> */ = [])
	{
		$name = $result->name;
		foreach ($inputs as $i => $input) $name .= ',' . $input->name . $this->input_suffix($i);
		parent::__construct('callback<' . $name . '>');
	}

	public function input_mode(int $index /** uint32 */): passing_mode
	{
		return $this->modes[$index] ?? passing_mode::value;
	}

	public function input_suffix(int $index /** uint32 */): string
	{
		return isset($this->modes[$index]) ? '&' : '';
	}

	public function cpp_declarator(string $name = ''): string
	{
		$result = static function (type_definition $type): string {
			$native = $type->cpp_type();
			return str_starts_with($native, 'scpp::') ? '::' . $native : $native;
		};
		$inputs = [];
		foreach ($this->inputs as $i => $input) $inputs[] = $result($input) . $this->input_suffix($i);
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
		return $other instanceof self && $other->result === $this->result && $other->inputs === $this->inputs && $other->modes === $this->modes;
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
				if ($parameter->mode !== $this->input_mode($index) || $parameter->default_value !== null || $parameter->type_def() !== $this->inputs[$index]) {
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

/** Intern collection element contracts by ordered component identity, without retaining retired declarations. */
final class callable_signature_index
{
	/** @var \WeakMap<type_definition,callable_signature_index>|null */
	private ?\WeakMap $children = null;
	private ?self $reference_inputs = null;
	/** @var \WeakReference<owned_callable_type>|null */
	private ?\WeakReference $element = null;

	/** Follow canonical component objects in signature order and reuse the live leaf. */
	public static function element(callback_type_definition $signature): owned_callable_type
	{
		static $root = new self();
		$node = $root;
		foreach ([$signature->result, ...$signature->inputs] as $i => $component) {
			if ($i > 0 && $signature->input_mode($i - 1) === passing_mode::reference) $node = $node->reference_inputs ??= new self();
			$node->children ??= new \WeakMap();
			$node = $node->children[$component] ??= new self();
		}
		$element = $node->element?->get();
		if ($element !== null) return $element;
		$element = new owned_callable_type($signature);
		$node->element = \WeakReference::create($element);
		return $element;
	}
}

/** Explicit callable storage owns a copy of its target and has an absent default state. */
final class owned_callable_type extends type_definition implements presence_test_type
{
	use collection_specializations;
	use concrete_value_storage;

	public function __construct(public readonly callback_type_definition $signature)
	{
		$inputs = [];
		foreach ($signature->inputs as $i => $input) $inputs[] = $input->name . $signature->input_suffix($i);
		parent::__construct('function<' . $signature->result->name . '(' . implode(',', $inputs) . ')>');
	}

	public function matches(type_definition $other): bool
	{
		return $other instanceof self && $this->signature->matches($other->signature);
	}

	public function cpp_type(): string
	{
		$inputs = [];
		foreach ($this->signature->inputs as $i => $input) $inputs[] = $input->cpp_type() . $this->signature->input_suffix($i);
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
