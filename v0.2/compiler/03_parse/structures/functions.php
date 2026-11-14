<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Immutable published function signature and C++ binding, e.g. strlen(string): int. */
final readonly class runtime_function
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
	public int $required_parameters /** uint32 */;
	/** One bit per mutable reference argument in the generated signature. */
	private int $reference_mask /** uint32 */;
	private int $const_reference_mask /** uint32 */;

	/** CONSTRUCTOR */
	/** @param type_definition[] $parameters */
	public function __construct(string $name, array $parameters /** list<type_definition> */, type_definition $result, string $symbol, string $header, ?string $adapter_flavor = null, ?int $required_parameters /** uint32 */ = null, int $reference_mask /** uint32 */ = 0, bool $overloaded_first = false, int $const_reference_mask /** uint32 */ = 0)
	{
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

/** Published runtime signatures selected by supplied arity and exact first argument type, e.g. number_format. */
final readonly class runtime_overloads
{
	/** @var array<int,array<class-string<type_definition>,runtime_function>> */
	public array $by_arity /** array<uint32,array<class-string<type_definition>,runtime_function>> */;

	/** @param array<int,array<class-string<type_definition>,runtime_function>> $by_arity */
	public function __construct(array $by_arity /** array<uint32,array<class-string<type_definition>,runtime_function>> */)
	{
		$this->by_arity = $by_arity;
	}
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
	public function function_def(): ast_function_declaration|runtime_function
	{
		$owner = $this->parent;
		while (!($owner instanceof ast_function_body)) {
			$owner = $owner->parent;
		}
		$owner->require_prepared();
		return $this->value->function_def();
	}
}

/** A named invocation owns arguments and their conversion facts; example: identity("hello"). */
final class ast_call_expression extends ast_expression
{
	/** PROPERTIES */
	public readonly string $name;
	/** Written member separator; JS dotted names may resolve to namespace functions. */
	public readonly ?token_kind $member_separator;
	/** Native owning receiver; a relative JS dotted path may instead resolve as a namespace call. */
	public readonly ?ast_expression $receiver;
	/** @var ast_expression[] Native owning arguments in source order. */
	public readonly array $arguments /** list<ast_expression> */;

	/** PREP FACTS */
	/** Prepared fact: builtin operation when the watched name resolves to a collection query. */
	public private(set) ?collection_cardinality $cardinality = null;
	/** Prepared fact: native non-owning stable symbol or immutable runtime binding; null before preparation or after reset. */
	private symbol_entry|runtime_function|null $function_symbol = null;
	/** @var int[] Prepared fact: approved positional conversions; empty before preparation or after reset. */
	private array $conversions /** list<uint16> */ = [];

	/** CONSTRUCTOR */
	/**
	 * $start/$end: token positions.
	 * @param ast_expression[] $arguments
	 */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, string $name,
		?token_kind $member_separator, array $arguments /** list<ast_expression> */, ?ast_expression $receiver = null)
	{
		parent::__construct($start, $end);
		$this->name = $name;
		$this->member_separator = $member_separator;
		$this->receiver = $receiver;
		$receiver?->attach($this);
		$this->arguments = $arguments;
		foreach ($arguments as $argument) {
			$argument->attach($this);
		}
	}

	/** METHODS */
	public function symbol_key(): string
	{
		return $this->root()->function_key($this->name);
	}

	public function settle_cardinality(collection_cardinality $operation, counted_collection_type $receiver): void
	{
		$this->cardinality = $operation;
		$this->settle_value($receiver->cardinality_result($operation), null);
	}

	public function target_name(): string
	{
		return $this->cardinality?->name ?? $this->function_def()->name();
	}

	/** @param int[] $conversions */
	public function settle_function(symbol_entry $symbol, array $conversions /** list<uint16> */): void
	{
		$function = $symbol->declaration();
		if (!($function instanceof ast_function_declaration) || count($conversions) !== count($this->arguments)) {
			throw new \LogicException('Invalid settled call');
		}
		$this->settle_value($function->return_type_def(), null);
		$this->function_symbol = $symbol;
		$this->conversions = $conversions;
	}

	/** @return ast_expression[] Runtime operands in evaluation order, including a method receiver. */
	public function runtime_operands(): array /** list<ast_expression> */
	{
		if ($this->function_symbol instanceof runtime_function && $this->function_symbol->name() === 'enum_from_value'
			&& ($this->arguments[0] ?? null) instanceof ast_type_marker) return array_slice($this->arguments, 1);
		return $this->receiver === null ? $this->arguments : [$this->receiver, ...$this->arguments];
	}

	/** @param int[] $conversions Approved runtime operand conversions; empty denotes exact arguments. */
	public function settle_runtime(runtime_function $function, array $conversions /** list<uint16> */ = []): void
	{
		$this->settle_value($function->return_type_def(), null);
		$this->function_symbol = $function;
		$this->conversions = $conversions;
	}

	public function function_def(): ast_function_declaration|runtime_function
	{
		if ($this->function_symbol instanceof runtime_function) {
			return $this->function_symbol;
		}
		return $this->function_symbol?->declaration() ?? throw new \LogicException('Call target is not settled or was removed');
	}

	/** $index: argument ordinal. */
	public function conversion(int $index /** uint32 */): int /** uint16 */
	{
		if ($this->function_symbol instanceof runtime_function && isset($this->function_symbol->parameters[$index])) {
			return $this->conversions[$index] ?? value_conversion::identity;
		}
		return $this->conversions[$index] ?? throw new \LogicException('Argument conversion is not settled');
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->function_symbol = null;
		$this->cardinality = null;
		$this->conversions = [];
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
	/** Stable logical source key bound before watches; cleared on retirement, never a source-unit object identity. */
	private string $source_key = '';

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
		if (($this->preparation_work === $work) && ($this->source_key === $source_key)) {
			return;
		}
		$this->preparation_work?->retire($this->source_key, $this);
		$this->preparation_work = $work;
		$this->source_key = $source_key;
		$work?->register($source_key, $this);
	}

	/** Full-program preparation binds its logical source identity before registering any watches. */
	public function bind_source_key(string $source_key): void
	{
		if ($this->preparation_work !== null) {
			throw new \LogicException('Attached source identity belongs to the incremental coordinator');
		}
		$this->source_key = $source_key;
	}

	/** Bound logical source key; standalone analysis uses its source path as the unit identity. */
	public function dependency_source_key(): string
	{
		return $this->source_key !== '' ? $this->source_key : $this->root()->source()->path;
	}

	/** Invalidate completion only; observers never rebuild or traverse cached statements here. */
	public function invalidate_dependencies(): void
	{
		$this->prepared = false;
		$this->preparation_work?->enqueue($this->source_key, $this);
		$this->root()->begin_preparation();
	}

	public function is_prepared(?entry_target $target = null): bool
	{
		return ($this->prepared) && ($this->exit_digits === $target?->digits);
	}

	public function reset_preparation(): void
	{
		$this->prepared = false;
		$this->preparation_work?->enqueue($this->source_key, $this);
		$this->statement_visits = $this->expression_visits = $this->name_lookups = 0;
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
		$this->preparation_work?->complete($this->source_key, $this);
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
final class ast_function_declaration extends ast_node implements ast_declaration_interface, dependency_watcher
{
	use ast_declaration { name as private namespace_name; }

	/** PROPERTIES */
	public readonly bool $is_private;
	public readonly bool $is_protected;
	public readonly bool $is_implicit_entry;
	public readonly bool $is_static;
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
	/** Prepared fact: canonical return type; settled before any body consumes this declaration. */
	protected ?type_definition $return_type_def = null;

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
		bool $is_implicit_entry = false, ?string $namespace = null, array $parameters /** list<ast_parameter_declaration> */ = [], bool $static = false, bool $private = false, bool $protected = false)
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

	/** Canonical global handle; the local declaration key remains usable by source-scope indexes. */
	public function symbol_key(): string
	{
		if (($this->parent ?? null) instanceof ast_method_owner) return 'method:' . $this->name();
		return 'func:' . ($this->is_implicit_entry ? 'main' : $this->name());
	}

	public function has_receiver(): bool
	{
		return $this->parent instanceof ast_method_owner && !$this->is_static;
	}

	/** Enforce lexical access for a call or an implicit inline-field initialization. */
	public function require_access(ast_node $context, ast_node $site): void
	{
		if ($this->is_protected && (!($context instanceof ast_record_declaration)
			|| !$context->definition->derives_from($this->parent->definition))) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Cannot call protected constructor ' . $this->name());
		}
		if ($this->is_private && $context !== $this->parent) {
			$kind = $this->is_constructor() ? 'constructor' : 'method';
			throw new diagnostic($site->source(), $site->byte_start(), 'Cannot call private ' . $kind . ' ' . $this->name());
		}
	}

	/** Defaults form a trailing suffix; only the first omitted parameter needs inspection. */
	public function accepts_argument_count(int $supplied /** uint32 */): bool
	{
		return $supplied <= count($this->parameters)
			&& (!isset($this->parameters[$supplied]) || $this->parameters[$supplied]->default_value !== null);
	}

	public function is_constructor(): bool
	{
		return $this->short_name === '__construct' && ($this->parent ?? null) instanceof ast_record_declaration;
	}

	/** Attach the completed body once and settle the function token boundary. */
	public function complete_body(ast_function_body $body): void
	{
		if (($this->body !== null) || ($body->start < $this->start)) {
			throw new \LogicException('Invalid function body publication');
		}
		$body->attach($this);
		$this->end = $body->end;
		$this->body = $body;
	}

	public function body(): ast_function_body
	{
		return $this->body ?? throw new \LogicException('Function body is not complete');
	}

	/** Start a new sequential analysis run; incremental reader quiescence is not implemented here. */
	public function reset_preparation(): void
	{
		$this->return_type_def = null;
		foreach ($this->parameters as $parameter) {
			$parameter->reset_preparation();
		}
		$this->body()->reset_preparation();
	}

	public function settle_return_type(type_definition $type): void
	{
		if ($this->return_type_def !== null) {
			throw new \LogicException('Function return type is already settled');
		}
		$this->return_type_def = $type;
	}

	/** Forget outgoing watches and notify callers before replacing a nominal contract in the same batch as its payload. */
	public function retire_signature(): void
	{
		if ($this->signature_dependencies === null) return;
		$this->signature_dependencies->forget($this);
		$this->signature_dependencies->changed($this->root()->scope->parent->entry($this->symbol_key()));
		$this->signature_dependencies = null;
	}

	public function watch_signature(?Dependency_Watches $dependencies): void
	{
		$this->signature_dependencies = $dependencies;
	}

	public function dependency_source_key(): string
	{
		return $this->body()->dependency_source_key();
	}

	/** A replaced payload changes the prepared function contract even when its written name stays the same. */
	public function invalidate_dependencies(): void
	{
		$this->return_type_def = null;
		$this->body()->invalidate_dependencies();
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
		if ($this->is_constructor()) return $this->return_type_reference->start;
		if ($this->source()->flavor === flavor::js && ($this->parent instanceof ast_method_owner)
			&& $this->root()->tokens->text_at($this->start) !== 'function') return $this->start;
		return $this->is_implicit_entry ? $this->start
			: ($this->source()->flavor === flavor::cpp ? $this->return_type_reference->end : $this->start + 1);
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
	/** Prepared fact: entry or function return conversion; null before preparation or for a void return. */
	protected exit_conversion|int|null $conversion /** exit_conversion|uint16|null */ = null;

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
	public function settle_conversion(exit_conversion|int $conversion /** exit_conversion|uint16 */): void
	{
		if ($this->conversion !== null) {
			throw new \LogicException('Return conversion already settled');
		}
		$this->conversion = $conversion;
	}

	public function conversion(): exit_conversion|int /** exit_conversion|uint16 */
	{
		return $this->conversion ?? throw new \LogicException('Entry conversion is not settled');
	}

	public function reset_preparation(): void
	{
		$this->conversion = null;
	}
}
