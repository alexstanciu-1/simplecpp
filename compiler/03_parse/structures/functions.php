<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Read-only declaration queries shared by source functions and published runtime bindings. */
interface callable_signature
{
	public function name(): string;
	public function return_type_def(): type_definition;
}

/** Statement-position invocation owns the same call expression used in value positions: ping(); */
final class ast_call_statement extends ast_statement
{
	/** Native owning call expression. */
	public readonly ast_call_expression $value;

	/** $start/$end: statement token positions including its semicolon. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, ast_call_expression $value)
	{
		parent::__construct($start, $end);
		$this->value = $value;
		$value->attach($this);
	}

	public function symbol_key(): string
	{
		return $this->value->symbol_key();
	}

	/** Inspection requires completion of the enclosing function, including calls inside nested arms. */
	public function function_def(): callable_signature
	{
		$owner = $this->parent;
		while (!($owner instanceof ast_function_body)) {
			$owner = $owner->parent;
		}
		$owner->require_prepared();
		return $this->value->function_def();
	}
}

/** Direct task consumption, such as return await read_value(). */
final class ast_await_expression extends ast_expression
{
	public readonly ast_call_expression $operand;

	/** Prepared fact: suspend this coroutine or wait at the synchronous boundary; set during each preparation. */
	public private(set) bool $suspends = false;

	public function __construct(int $start /** uint32 */, ast_call_expression $operand)
	{
		parent::__construct($start, $operand->end);
		$this->operand = $operand;
		$operand->attach($this);
	}

	public function settle(bool $suspends): void
	{
		$this->suspends = $suspends;
		$this->settle_value($this->operand->type_def(), null);
	}
}

/** Complete an awaited call and discard its result, as in await flush(); */
final class ast_await_statement extends ast_statement
{
	public readonly ast_await_expression $value;

	public function __construct(ast_await_expression $value, int $end /** uint32 */)
	{
		parent::__construct($value->start, $end);
		$this->value = $value;
		$value->attach($this);
	}
}

/** Await the timer at statement position: await async_sleep_ms(1); */
final class ast_delay_statement extends ast_statement
{
	public readonly ast_expression $value;

	public function __construct(int $start /** uint32 */, int $end /** uint32 */, ast_expression $value)
	{
		parent::__construct($start, $end);
		$this->value = $value;
		$value->attach($this);
	}
}

/** Lexical self and parent calls forward the enclosing static implementation's called type. */
enum call_qualifier: int /** uint8 */
{
	case ordinary = 0;
	case lexical_self = 1;
	case lexical_parent = 2;
	case called_type = 3;
	case local = 4;
	case stored_value = 5;
}

/** A named callable used as an argument. The target is resolved against the expected parameter type. */
final class ast_callable_reference extends ast_expression
{
	/** Native non-owning stable symbol or selected static implementation. */
	private symbol_entry|called_method_instance|null $target = null;

	public function __construct(public readonly string $name, int $start /** uint32 */, int $end /** uint32 */)
	{
		parent::__construct($start, $end);
	}

	public function settle_target(symbol_entry|called_method_instance $target, callback_type_definition $signature): void
	{
		$this->target = $target;
		$this->settle_value($signature, null);
	}

	public function implementation(): ast_function_declaration
	{
		return ($this->target instanceof called_method_instance ? $this->target->implementation : $this->target?->declaration())
			?? throw new \LogicException('Callback target is not settled');
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->target = null;
	}
}

/** The callable whose result contract applies to returns in a statement block. */
interface statement_return_target
{
	/** @var ast_parameter_declaration[] */
	public array $parameters {
		get;
	}
	public bool $returns_reference {
		get;
	}
	public ?int $reference_return_origin {
		get;
	}
	public bool $is_implicit_entry {
		get;
	}
	public bool $is_async {
		get;
	}
	public function return_type_def(): type_definition;
}

/** Arity of a source parameter list with trailing defaults or a typed tail. */
trait ast_parameter_list
{
	public function variadic_slot(): ?int
	{
		$last = count($this->parameters) - 1;
		return ($this->parameters[$last]->type_reference ?? null) instanceof ast_variadic_type_reference ? $last : null;
	}

	/** Defaults form a trailing suffix; only the first omitted parameter needs inspection. */
	public function accepts_argument_count(int $supplied /** uint32 */): bool
	{
		$count = count($this->parameters);
		if ($supplied >= $count) return $supplied === $count || $this->variadic_slot() !== null;
		$omitted = $this->parameters[$supplied];
		return $omitted->default_value !== null || $omitted->type_reference instanceof ast_variadic_type_reference;
	}
}

/** A lexical capture bound to its enclosing storage at creation. */
final class ast_closure_capture extends ast_local_declaration
{
	public function __construct(string $name, int $start /** uint32 */, ?int $string_offset /** uint32|null */ = null,
		public readonly bool $by_reference = false)
	{
		$read = $string_offset === null ? new ast_name_expression($name, $start, $start + 1)
			: new ast_interpolation_name($name, $start, $string_offset);
		parent::__construct($start, $start + 1, $name, null, $read);
	}
}

/** Owns the signature and body while its enclosing function owns incremental preparation. */
final class ast_lambda_expression extends ast_expression implements statement_return_target
{
	use ast_parameter_list;

	/** PROPERTIES */
	/** Prepared result conversion. */
	public private(set) int $conversion /** uint16 */ = value_conversion::identity;

	/** @var int[] Prepared enclosing storage IDs that invocation may write. */
	public private(set) array $borrowed_writes /** list<uint32> */ = [];

	public ?int $reference_return_origin {
		get => $this->type_def?->signature->reference_origin;
	}
	public bool $is_implicit_entry {
		get => false;
	}
	public bool $is_async {
		get => false;
	}

	/** CONSTRUCTOR */
	/** @param ast_parameter_declaration[] $parameters @param ast_closure_capture[] $captures */
	public function __construct(int $start /** uint32 */, public readonly array $parameters /** list<ast_parameter_declaration> */,
		public readonly ?ast_type_reference $result, public readonly ast_expression|ast_block $body, public readonly array $captures /** list<ast_closure_capture> */ = [],
		public readonly bool $explicit_receiver = false, public readonly bool $returns_reference = false)
	{
		parent::__construct($start, $body->end);
		foreach ($parameters as $parameter) $parameter->attach($this);
		foreach ($captures as $capture) $capture->attach($this);
		$result?->attach($this);
		$body->attach($this);
	}

	/** METHODS */
	public function return_type_def(): type_definition
	{
		return $this->type_def()->signature->result;
	}

	/** Publish the signature, result conversion and effects together. @param int[] $borrowed_writes */
	public function settle_lambda(lambda_type_definition $type, int $conversion /** uint16 */, array $borrowed_writes /** list<uint32> */): void
	{
		$this->conversion = $conversion;
		$this->borrowed_writes = $borrowed_writes;
		$this->settle_value($type, null);
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->borrowed_writes = [];
		$this->conversion = value_conversion::identity;
	}
}

/** A named invocation owns arguments and their conversion facts; example: identity("hello"). */
class ast_call_expression extends ast_expression
{
	/** PROPERTIES */
	public readonly string $name;
	/** Written member separator; JS dotted names may resolve to namespace functions. */
	public readonly ?token_kind $member_separator;
	/** Native owning receiver; a relative JS dotted path may instead resolve as a namespace call. */
	public readonly ?ast_expression $receiver;
	/** Written lexical qualifier, retained independently of the name used in lookup. */
	public readonly call_qualifier $qualifier;
	/** @var ast_expression[] Native owning arguments in source order. */
	public readonly array $arguments /** list<ast_expression> */;

	/** PREP FACTS */
	/** Prepared fact: fixed ancestor implementation on the current instance; false before preparation/reset. */
	public private(set) bool $ancestor_dispatch = false;
	/** Prepared fact: receiver selects an interface slot although the original implementation is nonvirtual. */
	public bool $interface_dispatch = false;
	/** Prepared fact: builtin operation when the watched name resolves to a collection query. */
	public private(set) ?collection_cardinality $cardinality = null;
	/** Prepared fact: evaluating the receiver or an argument can suspend this coroutine. */
	public bool $suspending_operands = false;
	/** Prepared fact: joined-batch intrinsic, or null for ordinary calls. */
	public private(set) ?string $mt_operation = null;

	/** Prepared fact: native non-owning target binding or direct producer operand; null before preparation or after reset. */
	private symbol_entry|runtime_function|called_method_instance|ast_local_binding|ast_call_expression|null $function_symbol = null;
	/** @var int[] Prepared fact: approved positional conversions; empty before preparation or after reset. */
	private array $conversions /** list<uint16> */ = [];

	/** CONSTRUCTOR */
	/**
	 * $start/$end: token positions.
	 * @param ast_expression[] $arguments
	 */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, string $name,
		?token_kind $member_separator, array $arguments /** list<ast_expression> */, ?ast_expression $receiver = null, call_qualifier $qualifier = call_qualifier::ordinary)
	{
		parent::__construct($start, $end);
		$this->name = $name;
		$this->member_separator = $member_separator;
		$this->receiver = $receiver;
		$this->qualifier = $qualifier;
		$receiver?->attach($this);
		$this->arguments = $arguments;
		foreach ($arguments as $argument) {
			$argument->attach($this);
		}
	}

	/** METHODS */
	/** A value followed by :: selects a static method through its declared type. */
	public function expression_qualified(): bool
	{
		return $this->member_separator === token_kind::scope && $this->receiver !== null;
	}

	public function symbol_key(): string
	{
		return $this->lexical_root()->function_key($this->name);
	}

	public function settle_mt(string $operation, type_definition $type): void
	{
		$this->mt_operation = $operation;
		$this->settle_value($type, null);
	}

	public function settle_cardinality(collection_cardinality $operation, counted_collection_type $receiver): void
	{
		$this->cardinality = $operation;
		$this->settle_value($receiver->cardinality_result($operation), null);
	}

	/** The explicit bridge reuses its existing operand and its checked argument conversions. */
	public function settle_blocking_wait(ast_call_expression $producer): void
	{
		$this->function_symbol = $producer;
		$this->settle_value($producer->type_def(), null);
	}

	public function blocking_producer(): ?ast_call_expression
	{
		return $this->function_symbol instanceof ast_call_expression ? $this->function_symbol : null;
	}

	public function has_direct_signature(): bool
	{
		return $this->mt_operation === null && $this->qualifier !== call_qualifier::stored_value && $this->cardinality === null && $this->callable_binding() === null && $this->blocking_producer() === null && $this->constructed_record() === null;
	}

	public function target_name(): string
	{
		if ($this->qualifier === call_qualifier::stored_value) return $this->name;
		if ($this->blocking_producer() !== null) return 'async_wait';
		return $this->mt_operation ?? $this->cardinality?->name ?? $this->callable_binding()?->name()
			?? $this->constructed_record()?->name() ?? $this->function_def()->name();
	}

	/** A call-shaped construction watches the same stable class entry as type resolution. */
	public function constructed_record(): ?ast_record_declaration
	{
		$target = $this->function_symbol instanceof symbol_entry ? $this->function_symbol->declaration() : null;
		return $target instanceof ast_record_declaration ? $target : null;
	}

	/** @param int[] $conversions */
	public function settle_construction(symbol_entry $target, shared_record_type_definition $type, array $conversions /** list<uint16> */): void
	{
		$this->function_symbol = $target;
		$this->conversions = $conversions;
		$this->settle_value($type, null);
	}

	public function runtime_binding(): ?runtime_function
	{
		return $this->function_symbol instanceof runtime_function ? $this->function_symbol : null;
	}

	/** @param int[] $conversions */
	public function settle_function(symbol_entry $symbol, array $conversions /** list<uint16> */, bool $ancestor_dispatch = false, ?called_method_instance $instance = null): void
	{
		$function = $symbol->declaration();
		if (!($function instanceof ast_function_declaration) || count($conversions) !== count($this->arguments)) {
			throw new \LogicException('Invalid settled call');
		}
		$result = $function->return_type_def();
		$this->settle_value($result instanceof class_union_type_definition ? ($result->receiver_view($this) ?? $result) : $result, null);
		$this->function_symbol = $instance ?? $symbol;
		$this->ancestor_dispatch = $ancestor_dispatch;
		$this->conversions = $conversions;
	}

	/** @return ast_expression[] Runtime operands in evaluation order; a type qualifier does not evaluate. */
	public function runtime_operands(?runtime_function $binding = null): array /** list<ast_expression> */
	{
		$binding ??= $this->function_symbol instanceof runtime_function ? $this->function_symbol : null;
		if (($this->arguments[0] ?? null) instanceof ast_type_marker
			&& ($binding?->name() === 'enum_from_value' || $binding?->name() === 'enum_try_from_value')) {
			return array_slice($this->arguments, 1);
		}
		return $this->receiver === null || $binding?->type_qualified ? $this->arguments : [$this->receiver, ...$this->arguments];
	}

	/** @param int[] $conversions Approved runtime operand conversions; empty denotes exact arguments. */
	public function settle_runtime(runtime_function $function, array $conversions /** list<uint16> */ = []): void
	{
		$this->settle_value($function->return_type_def(), null);
		$this->function_symbol = $function;
		$this->conversions = $conversions;
	}

	/** @param int[] $conversions */
	public function settle_callable_call(?ast_local_binding $parameter, callback_type_definition $signature, array $conversions /** list<uint16> */): void
	{
		$this->settle_value($signature->result, null);
		$this->function_symbol = $parameter;
		$this->conversions = $conversions;
	}

	public function callable_binding(): ?ast_local_binding
	{
		return $this->function_symbol instanceof ast_local_binding ? $this->function_symbol : null;
	}

	public function function_def(): ast_function_declaration|runtime_function
	{
		if ($this->function_symbol instanceof runtime_function) {
			return $this->function_symbol;
		}
		if ($this->function_symbol instanceof called_method_instance) return $this->function_symbol->method->declaration();
		return ($this->function_symbol instanceof symbol_entry ? $this->function_symbol->declaration() : null) ?? throw new \LogicException('Call target is not settled or was removed');
	}

	/** Return the caller's bound slot when this call yields a reference to one argument. */
	public function reference_origin(): ?ast_local_binding
	{
		$function = $this->function_symbol instanceof symbol_entry ? $this->function_symbol->declaration() : null;
		$slot = $function instanceof ast_function_declaration ? $function->reference_return_origin : null;
		if ($this->function_symbol instanceof ast_local_binding) {
			$type = $this->function_symbol->type_def();
			$signature = $type instanceof callback_type_definition ? $type : $type->signature;
			$slot = $signature->reference_origin;
		}
		if ($this->qualifier === call_qualifier::stored_value) $slot = $this->receiver->type_def()->signature->reference_origin;
		if ($slot === null) return null;
		$index = $this instanceof ast_named_call ? array_search($slot, $this->named->slots, true) : $slot;
		$argument = $index === false ? null : ($this->arguments[$index] ?? null);
		return $argument instanceof ast_name_expression ? $argument->local_binding() : null;
	}

	/** Lower only after the coordinator has settled all reachable implementations. */
	public function implementation(): ast_function_declaration|runtime_function
	{
		return $this->function_symbol instanceof called_method_instance ? $this->function_symbol->implementation : $this->function_def();
	}

	/** $index: argument ordinal. */
	public function conversion(int $index /** uint32 */): int /** uint16 */
	{
		if ($this->function_symbol instanceof runtime_function && isset($this->function_symbol->parameters[$index])) {
			return $this->conversions[$index] ?? value_conversion::identity;
		}
		return $this->conversions[$index] ?? throw new \LogicException('Argument conversion is not settled');
	}

	/** Clear invocation binding, dispatch and conversions together after invalidation. */
	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->function_symbol = null;
		$this->ancestor_dispatch = false;
		$this->interface_dispatch = false;
		$this->mt_operation = null;
		$this->cardinality = null;
		$this->suspending_operands = false;
		$this->conversions = [];
	}
}

/** The final positional operand supplies the whole variadic vector. */
final class ast_unpacked_call extends ast_call_expression
{
	public bool $unpacked {
		get => true;
	}
}

/** Caller-side repetition settings share ordinary operand traversal and reset. */
final class ast_job_repetition extends ast_call_expression
{
	public function __construct(ast_expression $bound, ast_expression $workers)
	{
		parent::__construct($bound->start, $workers->end, 'each', null, [$bound, $workers]);
	}
}

/** An inline launch owns its capture-free worker as receiver and caller-side binding expressions as operands. */
final class ast_inline_job extends ast_call_expression
{
	/** @param ast_expression[] $inputs @param list<string> $binding_kinds Written access mode for each operand. */
	public function __construct(ast_lambda_expression $worker, array $inputs, public readonly array $binding_kinds, public readonly ?ast_produced_binding $iteration)
	{
		parent::__construct($worker->start, $worker->end, 'job', null, $inputs, $worker);
		$iteration?->attach($this);
	}
}

/** A joined group owns its written worker calls, without resolving a function named parallel. */
final class ast_parallel_expression extends ast_call_expression
{
	/** @param ast_expression[] $jobs */
	public function __construct(int $start, int $end, array $jobs, ?ast_expression $workers)
	{
		parent::__construct($start, $end, 'parallel', null, $jobs, $workers);
	}
}

/** Written labels and the recomputable parameter permutation of a labeled call or construction. */
final class Named_Arguments
{
	/** PROPERTIES */
	/** @var int[] Prepared parameter slots in written order, then omitted defaults in declaration order. */
	public private(set) array $slots /** list<uint32> */ = [];

	/** CONSTRUCTOR */
	/** @param string[] $labels Written labels indexed by source argument position. */
	public function __construct(public readonly array $labels /** array<uint32,string> */)
	{
	}

	/** METHODS */
	/** Resolve against the current published signature. Only explicit operands can consume a slot. */
	public function bind_slots(ast_call_expression|ast_construction_expression $site, ast_function_declaration|runtime_function|ast_lambda_expression $function,
		?int $variadic = null): void
	{
		$names = [];
		if ($function instanceof runtime_function) $names = array_flip($function->parameter_names);
		else foreach ($function->parameters as $slot => $parameter) {
			$names[$parameter->name()] = $slot;
		}
		$slots = [];
		$used = [];
		foreach ($site->arguments as $position => $argument) {
			$label = $this->labels[$position] ?? null;
			$slot = $label === null ? $position : ($names[$label] ?? null);
			if ($slot === null) {
				$target = $function instanceof ast_lambda_expression ? $site->name : $function->name();
				throw new diagnostic($site->source(), $argument->byte_start(), 'Unknown named argument: ' . $label . ' on ' . $target);
			}
			if ($slot === $variadic && ($label !== null || !($site->unpacked ?? false))) throw new diagnostic($site->source(), $argument->byte_start(), 'Named arguments may only bind fixed parameters');
			if (!isset($function->parameters[$slot])) throw new diagnostic($site->source(), $argument->byte_start(), 'Too many positional arguments');
			if (isset($used[$slot])) throw new diagnostic($site->source(), $argument->byte_start(), 'Parameter supplied twice: ' . $label);
			$used[$slot] = true;
			$slots[] = $slot;
		}
		foreach ($names as $name => $slot) {
			if ($slot === $variadic || isset($used[$slot])) continue;
			if ($function instanceof runtime_function || $function->parameters[$slot]->default_value === null) {
				throw new diagnostic($site->source(), $site->byte_start(), 'Missing required argument: ' . $name);
			}
			$slots[] = $slot;
		}
		$this->slots = $slots;
	}

	/**
	 * @template T of ast_parameter_declaration|type_definition
	 * @param T[] $parameters
	 * @return T[]
	 */
	public function evaluation_parameters(array $parameters): array
	{
		return array_map(static fn(int $slot) => $parameters[$slot], $this->slots);
	}

	/** True when native parameter order differs from the required evaluation order. */
	public function reordered(): bool
	{
		foreach ($this->slots as $index => $slot) {
			if ($index !== $slot) return true;
		}
		return false;
	}

	/**
	 * @param string[] $operands
	 * @return string[]
	 */
	public function parameter_order(array $operands): array
	{
		$ordered = $operands;
		foreach ($this->slots as $index => $slot) $ordered[$slot] = $operands[$index];
		return $ordered;
	}

	public function reset_preparation(): void
	{
		$this->slots = [];
	}
}

/** Source function or method call with at least one written argument label. */
final class ast_named_call extends ast_call_expression
{
	public const string UNSUPPORTED_TARGET = 'Named arguments require a direct source function, method, constructor or local lambda, or a runtime free function with published parameter names';

	public readonly Named_Arguments $named;

	/** @param ast_expression[] $arguments @param string[] $labels */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, string $name, ?token_kind $separator,
		array $arguments /** list<ast_expression> */, ?ast_expression $receiver, array $labels /** array<uint32,string> */, call_qualifier $qualifier,
		public readonly bool $unpacked = false)
	{
		parent::__construct($start, $end, $name, $separator, $arguments, $receiver, $qualifier);
		$this->named = new Named_Arguments($labels);
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->named->reset_preparation();
	}
}

/**
 * Source-owned body syntax and local collection are published together after its closing brace.
 *
 * PHP-flavor body:
 * ```text
 * function ping(): void { echo 8; return; }
 * ```
 * The body owns the echo and return statements, local collection and completed body facts.
 */
final class ast_function_body extends ast_statement_block implements dependency_watcher
{
	/** PROPERTIES */
	/** Native non-owning queue reference; null outside incremental ownership or after retirement. */
	private ?preparation_work $preparation_work = null;
	/** Non-owning length initializer anchoring a proven scoped byte view. */
	public ?ast_local_declaration $byte_view_length = null;

	/** PREP FACTS */
	/** Preparation state: true only after successful whole-body preparation. */
	private bool $prepared = false;
	/** Prepared cache: entry-target context; null for ordinary function bodies. */
	private ?int $exit_digits /** uint8 */ = null;
	/** Preparation statistics: statements visited by the last successful body preparation; zero before completion. */
	public int $statement_visits /** uint32 */ = 0;
	/** Preparation statistics: expressions visited by the last successful body preparation; zero before completion. */
	public int $expression_visits /** uint32 */ = 0;
	/** Preparation statistics: name lookups performed by the last successful body preparation; zero before completion. */
	public int $name_lookups /** uint32 */ = 0;

	/** METHODS */
	/** Attach only after successful frontend publication; null retires replaced or removed syntax. */
	public function attach_preparation_work(?preparation_work $work, string $source_key = ''): void
	{
		if (($work !== null) && ($source_key === '')) {
			throw new \LogicException('Preparation work requires a source key');
		}
		$source_key = $work === null ? '' : $source_key;
		if (($this->preparation_work === $work) && ($this->dependency_source_key() === $source_key)) {
			return;
		}
		if (!$this->parent->is_static) $this->preparation_work?->retire($this->dependency_source_key(), $this);
		$this->preparation_work = $work;
		$this->parent->bind_source_key($source_key);
		if (!$this->parent->is_static) $work?->register($source_key, $this);
	}

	/** Full-program preparation binds its logical source identity before registering any watches. */
	public function bind_source_key(string $source_key): void
	{
		if ($this->preparation_work !== null) {
			throw new \LogicException('Attached source identity belongs to the incremental coordinator');
		}
		$this->parent->bind_source_key($source_key);
	}

	/** Bound logical source key; standalone analysis uses its source path as the unit identity. */
	public function dependency_source_key(): string
	{
		return $this->parent->dependency_source_key();
	}

	/** Invalidate completion only; observers never rebuild or traverse cached statements here. */
	public function invalidate_dependencies(): void
	{
		$this->prepared = false;
		$this->queue_change();
		$this->root()->begin_preparation();
	}

	/** Authored static bodies signal defining-unit edits. Only reachable private instances run their executable work. */
	private function queue_change(): void
	{
		if ($this->preparation_work === null) return;
		if ($this->parent->is_static) $this->preparation_work->change_source($this->dependency_source_key());
		else $this->preparation_work->enqueue($this->dependency_source_key(), $this);
	}

	public function is_prepared(?entry_target $target = null): bool
	{
		return ($this->prepared) && ($this->exit_digits === $target?->digits);
	}

	/** Release the bindings and cached results of the previous preparation. */
	public function reset_preparation(): void
	{
		$this->prepared = false;
		$this->queue_change();
		$this->byte_view_length = null;
		$this->name_lookups = 0;
		$this->expression_visits = 0;
		$this->statement_visits = 0;
	}

	/** Publish body completion and the target context only after all statement facts settle. */
	public function settle_preparation(int $statements /** uint32 */, int $expressions /** uint32 */, int $lookups /** uint32 */,
		?entry_target $target = null): void
	{
		if ($this->prepared) {
			throw new \LogicException('Function body is already prepared');
		}
		$this->statement_visits = $statements;
		$this->expression_visits = $expressions;
		$this->name_lookups = $lookups;
		$this->exit_digits = $target?->digits;
		$this->prepared = true;
		$this->preparation_work?->complete($this->dependency_source_key(), $this);
	}

	public function require_prepared(): void
	{
		if (!$this->prepared) {
			throw new \LogicException('Function preparation is not complete');
		}
	}

}

/**
 * Stable discovery identity: header fields never change, and only its source owner fills later slots.
 *
 * PHP-flavor function:
 * ```text
 * function ping(): void { return; }
 * ```
 * short_name is ping, return_type_reference names void, and return_type_def() is unavailable until declaration preparation.
 */
final class ast_function_declaration extends ast_node implements ast_declaration_interface, ast_declaration_site, dependency_watcher, callable_signature, statement_return_target
{
	use ast_declaration { name as private namespace_name; }
	use ast_parameter_list;

	/** PROPERTIES */
	/** Native non-owning authored trait; null for an ordinary member. */
	public ?ast_trait_declaration $trait_origin = null;
	public readonly bool $is_private;
	public readonly bool $is_protected;
	/** Written free-function modifier; the return type names the eventual value. */
	public bool $is_async = false;
	public readonly bool $is_implicit_entry;
	public readonly bool $is_static;
	/** Written method intent, populated by Record_Syntax before publication. */
	public bool $is_virtual = false;
	public bool $is_abstract = false;
	/** Stable logical source identity belongs to the callable, including declarations without a body. */
	private string $source_key = '';
	public bool $is_override = false;
	public bool $is_final = false;
	/** First callable modifier token, null for unmarked declarations. */
	public ?int $modifier_start /** uint32|null */ = null;
	/** Native owning return type syntax; absence is distinct from an unresolved return type. */
	public readonly ?ast_type_reference $return_type_reference;
	/** @var ast_parameter_declaration[] Native owning signature syntax in positional order. */
	public readonly array $parameters /** list<ast_parameter_declaration> */;
	/** Exclusive header token boundary. */
	public readonly int $header_end /** uint32 */;
	/** Native owning; function syntax child. */
	private ?ast_function_body $body = null;
	/** Native non-owning coordinator for signatures that depend on application payload identities. */
	private ?Dependency_Watches $signature_dependencies = null;

	/** PREP FACTS */
	/** Prepared fact: non-owning called-type identity of a private implementation. */
	public private(set) ?called_method_instance $instance = null;
	/** Prepared fact: inherited callable contract validated after signature settlement. */
	public bool $method_contract_ready = false;
	/** Prepared fact: nearest overridden declaration needing a shared-value bridge; native non-owning. */
	public ?self $adapted_base = null;
	/** Prepared fact: this override introduces its own signature alongside the inherited slots. */
	public bool $introduces_slot = false;
	/** Prepared fact: canonical return type; settled before any body consumes this declaration. */
	protected ?type_definition $return_type_def = null;
	/** Prepared fact: ordinal of the sole writable parameter supplying returned storage. */
	public private(set) ?int $reference_return_origin /** uint32|null */ = null;

	/** CONSTRUCTOR */
	/**
	 * Initialize syntax identity and attach supplied children to their owning node.
	 *
	 * $start: token position.
	 * $header_end: exclusive token position.
	 * @param ast_parameter_declaration[] $parameters
	 */
	public function __construct(string $name, ?ast_type_reference $return_type_reference, int $start /** uint32 */,
		int $header_end /** uint32 */,
		bool $is_implicit_entry = false, ?string $namespace = null, array $parameters /** list<ast_parameter_declaration> */ = [], bool $static = false, bool $private = false, bool $protected = false,
		public readonly bool $returns_reference = false)
	{
		$this->is_static = $static;
		$this->is_protected = $protected;
		$this->short_name = $name;
		$this->namespace = $namespace;
		$this->is_private = $is_implicit_entry || $private;
		$this->is_implicit_entry = $is_implicit_entry;
		$this->return_type_reference = $return_type_reference;
		$return_type_reference?->attach($this);
		$this->header_end = $header_end;
		$this->parameters = $parameters;
		foreach ($parameters as $parameter) {
			$parameter->attach($this);
		}
		parent::__construct($start, $header_end, true);
	}

	/** METHODS */
	/** Qualified callable identity for diagnostics and indexes; member names belong to their declaring type. */
	public function name(): string
	{
		return ($this->parent ?? null) instanceof ast_method_owner
			? $this->parent->name() . '::' . $this->short_name : $this->namespace_name();
	}

	public function duplicate_category(): declaration_duplicate_category
	{
		return declaration_duplicate_category::function_declaration;
	}

	/** Canonical global handle; the local declaration key remains usable by source-scope indexes. */
	public function symbol_key(): string
	{
		if (($this->parent ?? null) instanceof ast_method_owner) return 'method:' . $this->name();
		return 'func:' . ($this->is_implicit_entry ? 'main' : $this->name());
	}

	/** Written virtual intent is validated before emission; inherited overrides stay virtual. */
	public function has_virtual_dispatch(): bool
	{
		return $this->is_virtual || $this->is_override || $this->is_abstract;
	}

	/** An explicitly final instance method with a single concrete implementation and no virtual slot. */
	public function is_direct_final_method(): bool
	{
		return $this->is_final && !$this->is_static && !$this->has_virtual_dispatch()
			&& $this->parent instanceof ast_record_declaration && $this->parent->kind !== record_kind::interface_type;
	}

	public function has_receiver(): bool
	{
		return $this->parent instanceof ast_method_owner && !$this->is_static;
	}

	/** Enforce lexical access for a call or an implicit inline-field initialization. */
	public function require_access(ast_node $context, ast_node $site, ?record_type_definition $receiver = null,
		?dependency_watcher $watcher = null): void
	{
		if ($this->is_protected && !$this->parent->definition->permits_protected_access($context,
			($this->is_constructor() || $this->is_static) ? null : $receiver, $watcher)) {
			$kind = $this->is_constructor() ? 'constructor' : 'method';
			throw new diagnostic($site->source(), $site->byte_start(), 'Cannot call protected ' . $kind . ' ' . $this->name());
		}
		if ($this->is_private && $context !== $this->parent) {
			$kind = $this->is_constructor() ? 'constructor' : 'method';
			throw new diagnostic($site->source(), $site->byte_start(), 'Cannot call private ' . $kind . ' ' . $this->name());
		}
	}

	public function is_constructor(): bool
	{
		return $this->short_name === '__construct' && ($this->parent ?? null) instanceof ast_record_declaration;
	}

	public function is_destructor(): bool
	{
		return $this->short_name === '__destruct' && ($this->parent ?? null) instanceof ast_record_declaration;
	}

	/** Borrow the settled signature and lexical parent. Only the new executable subtree owns mutable body facts. */
	public function instantiate(called_method_instance $instance): self
	{
		$method = clone $this;
		$method->instance = $instance;
		$method->body = Parser::instance_body($this);
		$method->body->attach($method);
		return $method;
	}

	/** Attach the completed body once and settle the function token boundary. */
	public function complete_body(ast_function_body $body): void
	{
		if ($this->is_abstract || isset($this->end) || $body->start < $this->header_end) {
			throw new \LogicException('Invalid function body publication');
		}
		$body->attach($this);
		$this->end = $body->end;
		$this->body = $body;
	}

	/** Complete a bodyless declaration without inventing executable syntax. */
	public function complete_abstract(int $end /** uint32 */): void
	{
		if (!$this->is_abstract || isset($this->end) || $end < $this->header_end) {
			throw new \LogicException('Invalid abstract method completion');
		}
		$this->end = $end;
	}

	/** Null means no executable body; body() remains the checked concrete-body accessor. */
	public function optional_body(): ?ast_function_body
	{
		return $this->body;
	}

	public function bind_source_key(string $source_key): void
	{
		$this->source_key = $source_key;
	}

	/** Queue only executable bodies; signatures retain their own source identity. */
	public function attach_preparation_work(?preparation_work $work, string $source_key = ''): void
	{
		$this->body?->attach_preparation_work($work, $source_key);
		$this->source_key = $source_key;
	}

	public function body(): ast_function_body
	{
		return $this->body ?? throw new \LogicException('Function body is not complete');
	}

	/** Start a new sequential analysis run; incremental reader quiescence is not implemented here. */
	public function reset_preparation(): void
	{
		$this->return_type_def = null;
		$this->reference_return_origin = null;
		$this->method_contract_ready = false;
		$this->adapted_base = null;
		$this->introduces_slot = false;
		foreach ($this->parameters as $parameter) {
			$parameter->reset_preparation();
		}
		$this->body?->reset_preparation();
	}

	public function settle_return_type(type_definition $type, ?int $reference_origin /** uint32|null */ = null): void
	{
		if ($this->return_type_def !== null) {
			throw new \LogicException('Function return type is already settled');
		}
		$this->reference_return_origin = $reference_origin;
		$this->return_type_def = $type;
	}

	/** Preserve nominal dependency edges across replacement until the new signature settles. */
	public function retire_signature(?ast_function_declaration $replacement): void
	{
		$dependencies = $this->signature_dependencies;
		if ($dependencies === null) return;
		if ($replacement !== null) {
			$replacement->bind_source_key($this->dependency_source_key());
			$replacement->watch_signature($dependencies);
			$dependencies->replace_signature($this, $replacement);
		} else {
			$dependencies->forget($this);
			$dependencies->changed($this->root()->scope->parent->entry($this->symbol_key()));
		}
		$this->signature_dependencies = null;
	}

	public function watch_signature(?Dependency_Watches $dependencies): void
	{
		$this->signature_dependencies = $dependencies;
	}

	public function dependency_source_key(): string
	{
		return $this->source_key !== '' ? $this->source_key : $this->root()->source()->path;
	}

	/** A replaced payload changes the prepared function contract even when its written name stays the same. */
	public function invalidate_dependencies(): void
	{
		$this->return_type_def = null;
		$this->reference_return_origin = null;
		$this->method_contract_ready = false;
		$this->adapted_base = null;
		$this->introduces_slot = false;
		$this->body?->invalidate_dependencies();
		$this->root()->begin_preparation();
		$this->signature_dependencies?->changed($this->root()->scope->parent->entry($this->symbol_key()));
	}

	public function return_type_ready(): bool
	{
		return $this->return_type_def !== null;
	}

	public function return_type_def(): type_definition
	{
		return $this->return_type_def ?? throw new \LogicException('Function return type is not settled');
	}

	/** Return: token position. */
	public function name_position(): int /** uint32 */
	{
		if ($this->is_constructor() || $this->is_destructor()) return $this->return_type_reference->start;
		if ($this->source()->flavor === flavor::js && ($this->parent instanceof ast_method_owner)
			&& $this->lexical_root()->tokens->text_at($this->start) !== 'function') return $this->start;
		return $this->is_implicit_entry ? $this->start
			: ($this->source()->flavor === flavor::cpp ? $this->return_type_reference->end : $this->start + ($this->returns_reference ? 2 : 1));
	}

	/** Return: token position. */
	public function return_type_position(): int /** uint32 */
	{
		return $this->return_type_reference?->start ?? $this->start;
	}
}

/**
 * Return syntax retains an optional value; analysis selects the enclosing body's return contract.
 *
 * Return syntax:
 * ```text
 * return 8;
 * return;
 * ```
 * Preparation checks the owning function return type; the entry retains its separate process-exit conversion.
 */
final class ast_return_statement extends ast_statement
{
	/** PROPERTIES */
	/** Native owning; optional return expression child. */
	public readonly ?ast_expression $value;

	/** PREP FACTS */
	/** Prepared fact: entry or function return conversion; null before preparation or for a bare return. */
	protected ?int $conversion /** uint16|null */ = null;

	/** CONSTRUCTOR */
	/**
	 * $start: token position.
	 * $end: exclusive token position.
	 */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */,
		?ast_expression $value)
	{
		$this->value = $value;
		$value?->attach($this);
		parent::__construct($start, $end);
	}

	/** METHODS */
	public function settle_conversion(int $conversion /** uint16 */): void
	{
		if ($this->conversion !== null) {
			throw new \LogicException('Return conversion already settled');
		}
		$this->conversion = $conversion;
	}

	public function conversion(): int /** uint16 */
	{
		return $this->conversion ?? throw new \LogicException('Entry conversion is not settled');
	}

	public function reset_preparation(): void
	{
		$this->conversion = null;
	}
}
