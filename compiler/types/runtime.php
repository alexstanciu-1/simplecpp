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
	/** @param type_definition[] $parameters @param string[]|null $parameter_names Published source labels in declaration order. */
	public function __construct(string $name, array $parameters /** list<type_definition> */, type_definition $result, string $symbol, string $header, ?string $adapter_flavor = null, ?int $required_parameters /** uint32 */ = null, int $reference_mask /** uint32 */ = 0, bool $overloaded_first = false, int $const_reference_mask /** uint32 */ = 0, bool $receiver_method = false, bool $retains_receiver = true, bool $type_qualified = false, public ?string $required_module = null, public ?string $full_arity_argument = null, public ?array $parameter_names /** list<string>|null */ = null, public bool $exact_arguments = false)
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
	public function __construct(array $by_arity /** array<uint32,array<class-string<type_definition>,runtime_function>> */, int $argument_index /** uint32 */ = 0, bool $receiver_method = false, public ?type_definition $list_context = null, public ?string $required_module = null)
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
