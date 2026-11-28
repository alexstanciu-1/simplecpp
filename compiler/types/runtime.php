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
	protected int $const_reference_mask /** uint32 */;

	/** CONSTRUCTOR */
	/**
	 * @param type_definition[] $parameters
	 * @param string[]|null $parameter_names Published named-call labels in declaration order.
	 * @param array<class-string,bool> $retained_slot_types Eligible exact types in a variadic tail retained by the receiver.
	 * @param string[] $php_argument_labels Diagnostic labels for slots with a published PHP admission policy.
	 */
	public function __construct(string $name, array $parameters /** list<type_definition> */, type_definition $result, string $symbol, string $header, ?string $adapter_flavor = null, ?int $required_parameters /** uint32 */ = null, int $reference_mask /** uint32 */ = 0, bool $overloaded_first = false, int $const_reference_mask /** uint32 */ = 0, bool $receiver_method = false, bool $retains_receiver = true, bool $type_qualified = false, public ?string $required_module = null, public ?string $full_arity_argument = null, public ?array $parameter_names /** list<string>|null */ = null, public bool $exact_arguments = false, public bool $free_receiver = false, public bool $fresh_owner = false, public array $retained_slot_types /** array<class-string,bool> */ = [], public array $php_argument_labels /** list<string> */ = [], public int $php_exact_inputs /** uint32 */ = 0)
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

	/** A PHP value parameter is typed unless its publication requires an exact operand or writable slot. */
	public function php_value_input(int $slot): bool
	{
		return !$this->is_reference($slot) && ($this->php_exact_inputs & (1 << $slot)) === 0;
	}

	public function name(): string
	{
		return $this->source_name;
	}

	/** Preserve the native ownership and writable-slot policy. @param type_definition[] $parameters */
	public function with_inputs(array $parameters /** list<type_definition> */, string $header): self
	{
		return clone($this, ['parameters' => $parameters, 'header' => $header]);
	}

	public function return_type_def(): type_definition
	{
		return $this->result;
	}
}

/** Published runtime signatures selected by arity and one exact operand type: number_format or boxed has/remove. */
readonly class runtime_overloads
{
	/** @var array<int,runtime_function|array<class-string<type_definition>,runtime_function>> */
	public array $by_arity /** array<uint32,runtime_function|array<class-string<type_definition>,runtime_function>> */;
	/** Operand selecting the overload; receiver methods count the receiver as operand zero. */
	public int $argument_index /** uint32 */;
	public bool $receiver_method;

	/** @param array<int,runtime_function|array<class-string<type_definition>,runtime_function>> $by_arity */
	public function __construct(array $by_arity /** array<uint32,runtime_function|array<class-string<type_definition>,runtime_function>> */, int $argument_index /** uint32 */ = 0, bool $receiver_method = false, public ?type_definition $list_context = null, public ?string $required_module = null, public bool $project_selector = false)
	{
		$this->by_arity = $by_arity;
		$this->argument_index = $argument_index;
		$this->receiver_method = $receiver_method;
	}
}

/** A subject overload can also take ordered pattern and replacement collections. */
final readonly class rewrite_rule_overloads extends runtime_overloads
{
	/** @param array<int,array<class-string<type_definition>,runtime_function>> $by_arity
	 * @param array<class-string<type_definition>,type_definition> $rule_types
	 */
	public function __construct(array $by_arity, int $argument_index, type_definition $list_context, bool $project_selector,
		string $required_module, public array $rule_types, public string $rule_header)
	{
		parent::__construct($by_arity, $argument_index, list_context: $list_context, required_module: $required_module, project_selector: $project_selector);
	}
}

/** A PHP observation resolved through the operand's structural encoding capability. */
final readonly class dump_contract extends runtime_overloads
{
}

/** One synchronous observation of the prepared operand. */
final readonly class dump_function extends runtime_function
{
	public function __construct(type_definition $input)
	{
		parent::__construct('var_dump', [$input], type_void::instance(), '::scpp::dump_value', 'scpp/encoder.hpp', const_reference_mask: 1);
	}
}

/** Published leaf encoders also define the admissible fields of a data record. */
final readonly class json_encoder_overloads extends runtime_overloads
{
	/** @param array<class-string<type_definition>,runtime_function> $encoders */
	public function __construct(array $encoders, public string $record_header)
	{
		parent::__construct([1 => $encoders], required_module: 'runtime-json');
	}
}

/** Keep the borrowed argument and its concrete record declaration available to the calling unit. */
final readonly class record_encode_function extends runtime_function
{
	public function __construct(public record_type_definition $target, type_definition $input, runtime_function $published, json_encoder_overloads $encoders)
	{
		parent::__construct($published->name(), [$input], $published->return_type_def(), $input->cpp_type() . '::scpp_escape_json_encode',
			$encoders->record_header, const_reference_mask: 1, required_module: $encoders->required_module);
	}
}

/** Published scalar and sequence readers for a typed record destination. */
final readonly class json_decoder_contract extends runtime_overloads
{
	/** @param array<class-string<type_definition>,string> $sequences */
	public function __construct(public string $header, public array $sequences = [])
	{
		parent::__construct([], required_module: 'runtime-json');
	}
}

/** Nominal target used both for source typing and its concrete C++ declaration. */
final readonly class record_decode_function extends runtime_function
{
	public function __construct(public record_type_definition $target, json_decoder_contract $contract, bool $cursor)
	{
		$result = $target->record_result();
		parent::__construct($cursor ? 'decode' : 'json_decode_as', [$cursor ? type_json_pull_reader::instance() : type_string::instance()],
			$cursor ? $target->default_type() : $result, $result->cpp_type() . ($cursor ? '::from_cursor' : '::from_text'),
			$contract->header, const_reference_mask: 1, required_module: $contract->required_module);
	}
}

/** The selected traversal and callback signature for a typed collection transform. */
final readonly class collection_transform_function extends runtime_function
{
	public function __construct(runtime_factory $operation, type_definition $input, ?type_definition $callable,
		type_definition $output, public bool $filter)
	{
		$parameters = $callable === null ? [$input] : ($operation === runtime_factory::array_map ? [$callable, $input] : [$input, $callable]);
		parent::__construct($operation->name, $parameters, $output, $operation->name,
			$output->cpp_headers()[0], const_reference_mask: $operation === runtime_factory::array_map ? 2 : 1);
	}
}

/** PHP kind observations inspect a box at runtime or use the prepared source type. */
final readonly class php_kind_predicate extends runtime_overloads
{
	public function __construct(public value_kind $kind)
	{
		parent::__construct([]);
	}
}

/** Membership walks native collection values and stops at the first equal element. */
final readonly class collection_membership_function extends runtime_function
{
}

/** The argument still evaluates once when its kind is statically known. */
final readonly class prepared_kind_predicate extends runtime_function
{
	public function __construct(string $name, type_definition $type, public value_kind $kind, public ?bool $answer)
	{
		parent::__construct($name, [$type], type_bool::instance(), $name, '', const_reference_mask: 1);
	}
}

/** Free factory names are visible before their operand-dependent signatures are known. */
interface inferred_runtime_factory
{
}

/** Publication selects the result family. The resolved provider supplies its count and fill signature. */
final readonly class collection_fill_factory implements inferred_runtime_factory
{
	public function __construct(public string $name, private string $family)
	{
	}

	public function bind(type_definition $element): ?runtime_function
	{
		$collection = Type_Resolution::collection($this->family, $element, null);
		return $collection instanceof filled_collection_type ? $collection->filled_function() : null;
	}
}

/** Published free functions whose signatures depend on source operand types. */
enum runtime_factory implements inferred_runtime_factory
{
	case array_map;
	case array_filter;
	case in_array;
	case collection_map;
	case collection_filter;
	case sequence_map;
	case sequence_filter;
	case keyed_map;
	case keyed_filter;
}

/** Published PHP callback row types, keyed by canonical source type name. */
final readonly class regex_callback_overloads extends runtime_overloads
{
	/** @param array<string,array{type_definition,array<class-string,string>,int}> $rows
	 * @param array<class-string,type_definition> $subjects Subject class to falseable result.
	 * @param array<class-string,type_definition> $pattern_inputs
	 */
	public function __construct(private string $name, public bool $table, private int $allowed_flags, private array $rows, private array $subjects, public array $pattern_inputs)
	{
		parent::__construct([], argument_index: $table ? 0 : 1,
			list_context: isset($subjects[type_vector_string::class]) ? type_vector_string::instance() : null, required_module: 'runtime-regex');
	}

	/** Resolve a concrete adapter from the row type of its single string-producing callback. */
	public function binding(?type_definition $row, ast_expression $site, ?type_definition $subject = null, ?type_definition $pattern = null): regex_callback_function
	{
		$variant = $this->rows[$row?->name ?? ''] ?? null;
		if ($variant === null) throw new diagnostic($site->source(), $site->byte_start(), 'Unsupported callback row for ' . $this->name);
		$subject ??= type_string::instance();
		$result = $this->subjects[$subject::class] ?? throw new diagnostic($site->source(), $site->byte_start(), 'Unsupported subject type for ' . $this->name);
		return new regex_callback_function($this->name, $variant[0], $this->table, $variant[1][$subject::class], true, $variant[2], $this->allowed_flags, $subject, $result, $pattern);
	}
}

/** Regex consumes owned-callable values synchronously. The concrete adapter borrows each callable for one native call. */
final readonly class regex_callback_function extends runtime_function
{
	public owned_callable_type $callback;

	/** Resolve the canonical capture callback and the ordered hash used by the array form. */
	public function __construct(string $name, type_definition $input, private bool $table, string $header, bool $count_output = false, private ?int $capture_flags = null, private int $allowed_flags = 0, ?type_definition $subject = null, ?type_definition $result = null, ?type_definition $pattern = null)
	{
		$string = type_string::instance();
		$this->callback = callable_signature_index::element(new callback_type_definition($string, [$input]));
		$subject ??= $string;
		$parameters = $table
			? [Type_Resolution::collection('hash', $this->callback, $string), $subject]
			: [$pattern ?? $string, $this->callback, $subject];
		$required = count($parameters);
		$parameters[] = type_int64::instance();
		$count_mask = $count_output ? 1 << count($parameters) : 0;
		if ($count_output) $parameters[] = type_int64::instance();
		if ($capture_flags !== null) $parameters[] = type_int64::instance();
		parent::__construct($name, $parameters, $result ?? type_result_or_false_string::instance(), ($capture_flags === null ? '::scpp::regex::replace_callback' : '::scpp::regex::replace_capture_callback'), $header,
			required_parameters: $required, reference_mask: $count_mask, const_reference_mask: (1 << $required) - 1, required_module: 'runtime-regex');
	}

	/** Borrow a callable for the duration of replacement, walking table entries in insertion order. */
	public function adapter(int $arity): cpp_inline_adapter
	{
		$names = $this->table ? ['table', 'subject', 'limit', 'count', 'flags'] : ['pattern', 'callback', 'subject', 'limit', 'count', 'flags'];
		$params = [];
		for ($i = 0; $i < $arity; ++$i) {
			$params[] = new cpp_adapter_parameter($this->parameters[$i], $names[$i],
				match (true) {
					$this->is_reference($i) => cpp_adapter_mode::reference,
					$this->is_const_reference($i) => cpp_adapter_mode::const_reference,
					default => cpp_adapter_mode::value,
				});
		}
		$limit = $arity === $this->required_parameters ? '-1' : 'limit';
		$counted = $arity >= $this->required_parameters + 2;
		$count_argument = $counted ? ($this->table ? ', pattern_count' : ', count') : '';
		$flags = $arity === $this->required_parameters + 3 ? 'flags' : '0';
		if ($this->capture_flags !== null) {
			$count_argument = ', ' . ($counted ? ($this->table ? '&pattern_count' : '&count') : 'nullptr') . ', ' . $flags;
		}
		$input = $this->callback->signature->inputs[0]->cpp_type();
		$body = "\tauto invoke = [](const void* ctx, $input input) -> scpp::string {\n"
			. "\t\treturn (*static_cast<const " . $this->callback->cpp_type() . "*>(ctx))(static_cast<$input&&>(input));\n\t};\n";
		$headers = [$this->header];
		if ($this->table) {
			$headers[] = 'scpp/value_error.hpp';
			if ($this->capture_flags !== null) {
				$invalid = $this->capture_flags < 0 ? "($flags & ~{$this->allowed_flags}LL) != 0" : "$flags != $this->capture_flags";
				$body .= "\tif ($invalid) throw scpp::ValueError(\"capture flags do not match callback row\");\n";
			}
			$result = $this->return_type_def()->cpp_type();
			$subject = $this->parameters[1]->cpp_type();
			$body .= ($limit === 'limit' ? "\tif (limit < -1) throw scpp::ValueError(\"regex limit must be -1 or nonnegative\");\n" : '')
				. "\tauto current = subject;\n\tscpp::int64 pos = 0;\n\tauto stop = table.iteration_limit();\n\tscpp::string pattern;\n"
				. "\t" . $this->parameters[0]->cpp_type() . "::iteration_output callback;\n"
				. ($counted ? "\tscpp::int64 total = 0, pattern_count;\n" : '')
				. "\twhile (table.iteration_next(pos, stop, pattern, callback)) {\n"
				. "\t\tauto replaced = {$this->symbol}(pattern, &callback.value, invoke, current, $limit$count_argument);\n"
				. "\t\tif (!replaced.has_value()) return replaced;\n"
				. ($counted ? "\t\ttotal += pattern_count;\n" : '')
				. "\t\tcurrent = replaced.value();\n\t}\n"
				. ($counted ? "\tauto result = {$result}{static_cast<{$subject}&&>(current)};\n\tcount = total;\n\treturn result;\n"
					: "\treturn {$result}{static_cast<{$subject}&&>(current)};\n");
		} else {
			$body .= "\treturn {$this->symbol}(pattern, &callback, invoke, subject, $limit$count_argument);\n";
		}
		return new cpp_inline_adapter($this->name(), $params, $this->return_type_def(), $body, false,
			headers: $headers, body_types: [$this->callback]);
	}
}


/** The variadic tail consists of exact caller slots whose lifetime preparation has proven. */
final readonly class borrowed_slot_function extends runtime_function
{
	private int $tail_start /** uint32 */;

	/** @param type_definition[] $parameters Fixed published operands followed by the prepared slots. */
	public function __construct(runtime_function $signature, array $parameters /** list<type_definition> */)
	{
		$this->tail_start = count($signature->parameters);
		parent::__construct($signature->name(), $parameters, $signature->return_type_def(), $signature->symbol, $signature->header,
			const_reference_mask: $signature->const_reference_mask, free_receiver: true, retains_receiver: $signature->retains_receiver);
	}

	public function is_reference(int $index /** uint32 */): bool
	{
		return $index >= $this->tail_start;
	}
}
