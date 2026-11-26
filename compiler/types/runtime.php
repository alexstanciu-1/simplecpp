<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Immutable published function signature and C++ binding, e.g. strlen(string): int. */
readonly class runtime_function implements callable_signature
{
	/** PROPERTIES */
	private string $source_name;
	/** @var type_definition[] Non-owning canonical parameter types in call order. */
	public array $parameters /** list<type_definition> */;
	private type_definition $result;
	public string $symbol;
	public string $header;
	public ?string $adapter_flavor;
	/** Emit the exact native first argument type when it selects a C++ overload. */
	public bool $overloaded_first;
	/** Native member binding; operand zero is the receiver, with mutability in the reference masks. */
	public bool $receiver_method;
	/** A written type qualifier selects the binding and is absent from its operands. */
	public bool $type_qualified;
	/** Unknown bindings may retain receiver storage beyond the call. */
	public bool $retains_receiver;
	public int $required_parameters /** uint32 */;
	/** One bit per mutable reference argument in the generated signature. */
	private int $reference_mask /** uint32 */;
	private int $const_reference_mask /** uint32 */;

	/** CONSTRUCTOR */
	/**
	 * @param type_definition[] $parameters
	 * @param string[]|null $parameter_names Published named-call labels in declaration order.
	 * @param string[] $php_argument_labels Diagnostic labels for slots with a published PHP admission policy.
	 */
	public function __construct(string $name, array $parameters /** list<type_definition> */, type_definition $result, string $symbol, string $header, ?string $adapter_flavor = null, ?int $required_parameters /** uint32 */ = null, int $reference_mask /** uint32 */ = 0, bool $overloaded_first = false, int $const_reference_mask /** uint32 */ = 0, bool $receiver_method = false, bool $retains_receiver = true, bool $type_qualified = false, public ?string $required_module = null, public ?string $full_arity_argument = null, public ?array $parameter_names /** list<string>|null */ = null, public bool $exact_arguments = false, public bool $free_receiver = false, public bool $borrows_slots = false, public array $php_argument_labels /** list<string> */ = [], public int $php_typed_inputs /** uint32 */ = 0)
	{
		$this->retains_receiver = $retains_receiver;
		$this->type_qualified = $type_qualified;
		$this->receiver_method = $receiver_method;
		$this->const_reference_mask = $const_reference_mask;
		$this->reference_mask = $reference_mask;
		$this->overloaded_first = $overloaded_first;
		$this->source_name = $name;
		$this->required_parameters = $required_parameters ?? count($parameters);
		$this->parameters = $parameters;
		$this->result = $result;
		$this->symbol = $symbol;
		$this->header = $header;
		$this->adapter_flavor = $adapter_flavor;
	}

	/** METHODS */
	public function is_reference(int $index /** uint32 */): bool
	{
		return ($this->reference_mask & (1 << $index)) !== 0;
	}

	public function is_const_reference(int $index /** uint32 */): bool
	{
		return ($this->const_reference_mask & (1 << $index)) !== 0;
	}

	public function name(): string
	{
		return $this->source_name;
	}

	public function return_type_def(): type_definition
	{
		return $this->result;
	}
}

/** Published runtime signatures selected by arity and one exact operand type: number_format or boxed has/remove. */
final readonly class runtime_overloads
{
	/** @var array<int,array<class-string<type_definition>,runtime_function>> */
	public array $by_arity /** array<uint32,array<class-string<type_definition>,runtime_function>> */;
	/** Operand selecting the overload; receiver methods count the receiver as operand zero. */
	public int $argument_index /** uint32 */;
	public bool $receiver_method;

	/** @param array<int,array<class-string<type_definition>,runtime_function>> $by_arity */
	public function __construct(array $by_arity /** array<uint32,array<class-string<type_definition>,runtime_function>> */, int $argument_index /** uint32 */ = 0, bool $receiver_method = false, public ?type_definition $list_context = null, public ?string $required_module = null, public bool $project_selector = false)
	{
		$this->by_arity = $by_arity;
		$this->argument_index = $argument_index;
		$this->receiver_method = $receiver_method;
	}
}

/** The selected traversal and callback signature for a typed collection transform. */
final readonly class collection_transform_function extends runtime_function
{
	public function __construct(runtime_factory $operation, type_definition $input, type_definition $callable,
		type_definition $output, public bool $filter)
	{
		parent::__construct($operation->name, [$input, $callable], $output, $operation->name,
			$output->cpp_headers()[0], const_reference_mask: 1);
	}
}

/** Published free functions whose signatures depend on source operand types. */
enum runtime_factory
{
	case vector_filled;
	case collection_map;
	case collection_filter;
	case sequence_map;
	case sequence_filter;
	case keyed_map;
	case keyed_filter;
}

/** Regex consumes owned-callable values synchronously. The concrete adapter borrows each callable for one native call. */
final readonly class regex_callback_function extends runtime_function
{
	private owned_callable_type $callback;

	/** Resolve the canonical capture callback and the ordered hash used by the array form. */
	public function __construct(string $name, type_definition $input, private bool $table, string $header)
	{
		$string = type_string::instance();
		$this->callback = callable_signature_index::element(new callback_type_definition($string, [$input]));
		$parameters = $table
			? [Type_Resolution::collection('hash', $this->callback, $string), $string]
			: [$string, $this->callback, $string];
		$required = count($parameters);
		$parameters[] = type_int64::instance();
		parent::__construct($name, $parameters, type_result_or_false_string::instance(), '::scpp::regex::replace_callback', $header,
			required_parameters: $required, const_reference_mask: (1 << $required) - 1, required_module: 'runtime-regex');
	}

	/** Borrow a callable for the duration of replacement, walking table entries in insertion order. */
	public function adapter(int $arity): cpp_inline_adapter
	{
		$names = $this->table ? ['table', 'subject', 'limit'] : ['pattern', 'callback', 'subject', 'limit'];
		$params = [];
		for ($i = 0; $i < $arity; ++$i) {
			$params[] = new cpp_adapter_parameter($this->parameters[$i], $names[$i],
				$this->is_const_reference($i) ? cpp_adapter_mode::const_reference : cpp_adapter_mode::value);
		}
		$limit = $arity === $this->required_parameters ? '-1' : 'limit';
		$input = $this->callback->signature->inputs[0]->cpp_type();
		$body = "\tauto invoke = [](const void* ctx, $input input) -> scpp::string {\n"
			. "\t\treturn (*static_cast<const " . $this->callback->cpp_type() . "*>(ctx))(static_cast<$input&&>(input));\n\t};\n";
		$headers = [$this->header];
		if ($this->table) {
			$headers[] = 'scpp/value_error.hpp';
			$body .= ($limit === 'limit' ? "\tif (limit < -1) throw scpp::ValueError(\"regex limit must be -1 or nonnegative\");\n" : '')
				. "\tauto current = subject;\n\tscpp::int64 pos = 0;\n\tauto stop = table.iteration_limit();\n\tscpp::string pattern;\n"
				. "\t" . $this->parameters[0]->cpp_type() . "::iteration_output callback;\n"
				. "\twhile (table.iteration_next(pos, stop, pattern, callback)) {\n"
				. "\t\tauto replaced = {$this->symbol}(pattern, &callback.value, invoke, current, $limit);\n"
				. "\t\tif (!replaced.has_value()) return replaced;\n\t\tcurrent = replaced.value();\n\t}\n"
				. "\treturn scpp::result_or_false_string{static_cast<scpp::string&&>(current)};\n";
		} else {
			$body .= "\treturn {$this->symbol}(pattern, &callback, invoke, subject, $limit);\n";
		}
		return new cpp_inline_adapter($this->name(), $params, $this->return_type_def(), $body, false,
			headers: $headers, body_types: [$this->callback]);
	}
}


/** The variadic tail consists of exact caller slots whose lifetime preparation has proven. */
final readonly class borrowed_slot_function extends runtime_function
{
	public function is_reference(int $index /** uint32 */): bool
	{
		return $index >= 2;
	}
}
