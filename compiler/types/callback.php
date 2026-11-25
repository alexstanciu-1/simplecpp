<?php

declare(strict_types=1);
namespace scpp\compiler;

/** A callable signature owned by its declaring parameter, with canonical component types. */
final class callback_type_definition extends type_definition
{
	/**
	 * @param type_definition[] $inputs
	 * @param passing_mode[] $modes Reference positions in ascending input order.
	 */
	public function __construct(public readonly type_definition $result, public readonly array $inputs /** list<type_definition> */,
		private readonly array $modes /** array<uint32,passing_mode> */ = [], public readonly ?int $reference_origin /** uint32|null */ = null,
		public readonly ?int $variadic_slot = null)
	{
		$name = $result->name . ($reference_origin === null ? '' : '&');
		foreach ($inputs as $i => $input) $name .= ',' . $this->input_spelling($i);
		parent::__construct('callback<' . $name . '>');
	}

	public function input_spelling(int $index): string
	{
		$type = $this->inputs[$index];
		return $index === $this->variadic_slot ? $type->element_type->name . '...' : $this->input_mode($index)->qualify($type->name);
	}

	public function result_suffix(): string
	{
		return $this->reference_origin === null ? '' : '&';
	}

	public function input_mode(int $index /** uint32 */): passing_mode
	{
		return $this->modes[$index] ?? passing_mode::value;
	}

	/** Spell the concrete native function pointer and its parameter types. */
	public function cpp_declarator(string $name = ''): string
	{
		$result = static function (type_definition $type): string {
			$native = $type->cpp_type();
			return str_starts_with($native, 'scpp::') ? '::' . $native : $native;
		};
		$inputs = [];
		foreach ($this->inputs as $i => $input) $inputs[] = $this->input_mode($i)->qualify($result($input));
		return $result($this->result) . $this->result_suffix() . ' (*' . $name . ')(' . implode(', ', $inputs) . ')';
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
		return $other instanceof self && $other->result === $this->result && $other->inputs === $this->inputs && $other->variadic_slot === $this->variadic_slot && $other->modes === $this->modes && $other->reference_origin === $this->reference_origin;
	}

	/** Only capture-free closures can cross the native function-pointer boundary. */
	public function prepare_scalar_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		$source = $value->type_def();
		if ($source instanceof lambda_type_definition && $source->has_capture) {
			throw new diagnostic($value->source(), $value->byte_start(), 'Typed callbacks require a capture-free callable');
		}
		if ($source instanceof lambda_type_definition && !$source->admits_pointer()) {
			throw new diagnostic($value->source(), $value->byte_start(), 'Reference-returning callbacks require a target without parameter defaults');
		}
		if ($this->matches($source instanceof lambda_type_definition ? $source->signature : $source)) {
			return value_conversion::identity;
		}
		throw new diagnostic($value->source(), $value->byte_start(), 'Expected exact callback signature ' . $this->name);
	}

	/** Validate the callable target against its prepared signature. */
	public function require_target(ast_callable_reference $site, ast_function_declaration $target): void
	{
		$matches = $target->variadic_slot() === $this->variadic_slot && $target->reference_return_origin === $this->reference_origin && $target->return_type_def() === $this->result && count($target->parameters) === count($this->inputs);
		if ($matches) {
			foreach ($target->parameters as $index => $parameter) {
				if ($parameter->mode !== $this->input_mode($index) || $parameter->type_def() !== $this->inputs[$index]) {
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
	private ?self $readonly_inputs = null;
	private ?self $variadic = null;
	/** @var \WeakReference<owned_callable_type>|null */
	private ?\WeakReference $element = null;

	/** Follow canonical component objects in signature order and reuse the live leaf. */
	public static function element(callback_type_definition $signature): owned_callable_type
	{
		static $root = new self();
		$node = $signature->reference_origin === null ? $root : ($root->reference_inputs ??= new self());
		if ($signature->variadic_slot !== null) $node = $node->variadic ??= new self();
		foreach ([$signature->result, ...$signature->inputs] as $i => $component) {
			if ($i > 0) {
				$node = match ($signature->input_mode($i - 1)) {
					passing_mode::value => $node,
					passing_mode::reference => $node->reference_inputs ??= new self(),
					passing_mode::readonly_reference => $node->readonly_inputs ??= new self(),
				};
			}
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
		foreach ($signature->inputs as $i => $input) $inputs[] = $signature->input_spelling($i);
		parent::__construct('function<' . $signature->result->name . $signature->result_suffix() . '(' . implode(',', $inputs) . ')>');
	}

	public function matches(type_definition $other): bool
	{
		return $other instanceof self && $this->signature->matches($other->signature);
	}

	public function cpp_type(): string
	{
		$inputs = [];
		foreach ($this->signature->inputs as $i => $input) $inputs[] = $this->signature->input_mode($i)->qualify($input->cpp_type());
		return 'std::function<' . $this->signature->result->cpp_type() . $this->signature->result_suffix() . '(' . implode(', ', $inputs) . ')>';
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
	/** PROPERTIES */
	public readonly bool $has_capture;

	/** CONSTRUCTOR */
	public function __construct(public readonly callback_type_definition $signature, bool $has_local_captures, public readonly bool $borrows_receiver = false,
		public readonly bool $has_borrow = false, public readonly bool $has_defaults = false)
	{
		$this->has_capture = $has_local_captures || $borrows_receiver;
		parent::__construct('lambda ' . $signature->name);
	}

	/** METHODS */
	/** Native function pointers carry neither a capture environment nor defaults on a reference-result contract. */
	public function admits_pointer(): bool
	{
		return !$this->has_capture && (!$this->has_defaults || $this->signature->reference_origin === null);
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
