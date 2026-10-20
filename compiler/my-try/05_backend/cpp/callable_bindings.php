<?php

/* Backend call spelling is not part of the semantic callable identity or signature. */
namespace scpp\compiler;

/** Free symbols have no fabricated receiver or construction owner. */
final class cpp_function_binding
{
	private runtime_function_definition $semantic_target;
	private string $qualified_symbol;
	private string $required_header;

	/** Publish backend spelling separately from the installed semantic identity. */
	public function __construct(runtime_function_definition $target, string $symbol, string $header)
	{
		if ($symbol === '') {
			throw new \InvalidArgumentException('C++ function symbol must not be empty');
		}
		$this->semantic_target = $target;
		$this->qualified_symbol = $symbol;
		$this->required_header = $header;
	}

	public function target(): runtime_function_definition
	{
		return $this->semantic_target;
	}

	public function symbol(): string
	{
		return $this->qualified_symbol;
	}

	public function header(): string
	{
		return $this->required_header;
	}
}

final class cpp_callable_binding
{
	private registered_static_factory $semantic_target;
	private string $member_name;
	private string $required_header;
	private string $adapter_template;

	public function __construct(registered_static_factory $target, string $member, string $header, string $adapter)
	{
		if ($member === '') {
			throw new \InvalidArgumentException('C++ static factory member must not be empty');
		}
		$this->semantic_target = $target;
		$this->member_name = $member;
		$this->required_header = $header;
		$this->adapter_template = $adapter;
	}

	public function target(): registered_static_factory { return $this->semantic_target; }
	public function member(): string { return $this->member_name; }
	public function header(): string { return $this->required_header; }
	public function adapter(): string { return $this->adapter_template; }
}

final class CPP_Callable_Bindings
{
	private Keyed_Storage $definitions /** Keyed_Storage<cpp_callable_binding> */;
	private Keyed_Storage $functions /** Keyed_Storage<cpp_function_binding> */;
	private array $initializers /** hash<cpp_initialization_adapter, int> */ = [];

	public function __construct()
	{
		$this->definitions = new Keyed_Storage /** Keyed_Storage<cpp_callable_binding> */();
		$this->functions = new Keyed_Storage /** Keyed_Storage<cpp_function_binding> */();
	}

	public function bind_function(runtime_function_definition $target, string $symbol, string $header): void
	{
		$functions /** Keyed_Storage<cpp_function_binding> */ = $this->functions;
		$functions->add($target->key(), new cpp_function_binding($target, $symbol, $header));
	}

	/** Reject stale identities even when their provider keys have been reinstalled. */
	public function function_binding(runtime_function_definition $target): cpp_function_binding
	{
		$functions /** Keyed_Storage<cpp_function_binding> */ = $this->functions;
		$key = $target->key();
		if (!isset($functions[$key])) {
			throw new \LogicException('Runtime function has no C++ binding');
		}
		$binding = $functions[$key];
		if ($binding->target() !== $target) {
			throw new \LogicException('C++ function binding belongs to a different semantic identity');
		}
		return $binding;
	}

	public function bind_static(registered_static_factory $target, string $member, string $header): void
	{
		$definitions /** Keyed_Storage<cpp_callable_binding> */ = $this->definitions;
		$definitions->add($target->key(), new cpp_callable_binding($target, $member, $header, ''));
	}

	/** The adapter receives the exact owner's C++ type; semantic callable identity is unchanged. */
	public function bind_adapter(registered_static_factory $target, string $adapter, string $member, string $header): void
	{
		if ($adapter === '') { throw new \InvalidArgumentException('C++ factory adapter must not be empty'); }
		$definitions /** Keyed_Storage<cpp_callable_binding> */ = $this->definitions;
		$definitions->add($target->key(), new cpp_callable_binding($target, $member, $header, $adapter));
	}

	public function contains(registered_static_factory $target): bool
	{
		$definitions /** Keyed_Storage<cpp_callable_binding> */ = $this->definitions;
		$key = $target->key();
		return isset($definitions[$key]);
	}

	/** Explicit provider opt-in; no adapter is guessed from a source type name. */
	public function bind_initialization_definition(type_definition_i $definition, string $adapter, string $header): void
	{
		$id = $definition->definition_id();
		if (isset($this->initializers[$id])) { throw new \LogicException('Duplicate C++ initialization adapter'); }
		if ($adapter === '') { throw new \InvalidArgumentException('C++ initialization adapter must not be empty'); }
		$this->initializers[$id] = new cpp_initialization_adapter($definition, $adapter, $header);
	}

	public function initialization_adapter(type_definition_i $definition): cpp_initialization_adapter
	{
		$id = $definition->definition_id();
		if (!isset($this->initializers[$id])) { throw new \LogicException('Type has no C++ initialization adapter'); }
		$adapter = $this->initializers[$id];
		if ($adapter->definition() !== $definition) { throw new \LogicException('C++ initialization adapter belongs to another identity'); }
		return $adapter;
	}

	public function definition(registered_static_factory $target): cpp_callable_binding
	{
		$definitions /** Keyed_Storage<cpp_callable_binding> */ = $this->definitions;
		$key = $target->key();
		if (!isset($definitions[$key])) {
			throw new \LogicException('Runtime callable has no C++ binding');
		}
		$binding = $definitions[$key];
		if ($binding->target() !== $target) {
			throw new \LogicException('C++ callable binding belongs to a different semantic identity');
		}
		return $binding;
	}
}

final class CPP_Callables
{
	/** Physical call identity includes prepared parameter modes and argument value categories. */
	public static function argument_identity(prepared_call $call): string
	{
		$key = CPP_Opaque_Representation::identity($call->type);
		foreach ($call->signature->parameters as $index => $parameter) {
			$key .= ':' . CPP_Opaque_Representation::identity($parameter->type)
				. ':' . enum_name($parameter->mode) . ':' . enum_name($parameter->access)
				. ':' . (self::argument_consumed($call, $index) ? 'rvalue' : 'lvalue');
		}
		return $key;
	}

	public static function argument_consumed(prepared_call $call, int $index): bool
	{
		$arguments /** Storage<prepared_call_argument> */ = $call->arguments;
		if ($index >= q_count($arguments)) { return true; }
		return $arguments[$index]->boundary === value_boundary_operation::consume_value;
	}

	/** Emission view only: authored inventories keep source methods, not specialization helpers. */
	public static function implementations(collected_member_owner $record): Storage /** Storage<method_node> */
	{
		$result /** Storage<method_node> */ = new Storage();
		$contracts = $record->contract_work->require_facts();
		$decisions /** Keyed_Storage<prepared_method_contract> */ = $contracts->methods;
		foreach ($decisions as $decision) {
			$selected = weakref_get($decision->method);
			if (Member_Inventory::is_requirement($selected)) {
				$result->append(object_cast($selected->callable_syntax(), method_node::class));
			}
		}
		foreach (Member_Inventory::methods($record) as $syntax) {
			$method = object_cast($syntax->occurrence(), collected_method::class);
			if (!Callable_Inventory::deferred_body($method)) { $result->append($syntax); continue; }
			$instances /** Storage<callable_instance> */ = $method->called_instances;
			foreach ($instances as $instance) {
				$result->append(object_cast($instance->working->callable_syntax(), method_node::class));
			}
		}
		return $result;
	}
	/** Stable backend-only helper identity; never published in source member lookup. */
	public static function method_name(collected_method $method): string
	{
		$instance = weakref_get($method->instance_context);
		if ($instance !== null) {
			return CPP_Generator::source_name('implementation', $method->name)
				. '_for_' . CPP_Generator::source_name('type', $instance->called_type()->qualified_name());
		}
		if (!$method->callable_syntax()->require_preparation()->static_member) {
			return CPP_Generator::source_name('implementation', $method->declaring_type()->qualified_name())
				. '__' . CPP_Generator::source_name('method', $method->name);
		}
		return CPP_Generator::source_name('method', $method->name);
	}
	/** Backend-owned dispatch keeps callable identities independent of any output target. */
	public static function call_reference(prepared_call $facts, cpp_generation_context $context): string
	{
		$target = weakref_get($facts->target);
		if (($context->specialization_engine !== null) && ($target instanceof runtime_function_definition)) {
			throw new \RuntimeException('prelower_templates: runtime call requires prepared concrete transport');
		}
		$reference = self::reference($target, $context);
		if ($facts->runtime_owner !== null) {
			$owner /** canonical_type_use */ = $facts->runtime_owner;
			$member = Model::$runtime_callables->member_for(object_cast($target, runtime_function_definition::class));
			if ($member !== null) {
				if ($member->static_member && ($member->owner instanceof abstract_template_type_definition)) {
					$mapping = CPP_Types::payload($owner, $context);
					CPP_Types::record_dependencies($mapping, $context);
					return $mapping->spelling . '::' . $reference;
				}
			}
		}
		return $reference;
	}

	public static function reference(callable_definition_i $target, cpp_generation_context $context): string
	{
		if ($target instanceof collected_method) {
			$method = object_cast($target, collected_method::class);
			$record = $method->declaring_type();
			$context->headers[CPP_Artifacts::type_header($record->qualified_name())] = true;
			return CPP_Generator::source_name('record', $record->qualified_name()) . '::' . self::method_name($method);
		}
		if ($target instanceof collected_function) {
			$function = object_cast($target, collected_function::class);
			$context->headers[CPP_Artifacts::callable_header($function->qualified_name())] = true;
			return self::source_reference($function);
		}
		if ($target instanceof runtime_function_definition) {
			$binding = Model::$cpp_callable_bindings->function_binding(object_cast($target, runtime_function_definition::class));
			if ($context->specialization_engine !== null) {
				throw new \RuntimeException('prelower_templates: runtime calls require concrete ABI adapters');
			}
			$header = $binding->header();
			if ($header !== '') {
				$context->headers[$header] = true;
			}
			return $binding->symbol();
		}
		if ($target instanceof registered_static_factory) {
			return self::runtime_reference(object_cast($target, registered_static_factory::class), $context);
		}
		throw new \LogicException('C++ callable target is not supported');
	}

	public static function source_reference(collected_function $target): string
	{
		return CPP_Generator::source_name('function', $target->qualified_name());
	}

	public static function runtime_reference(registered_static_factory $target,
		cpp_generation_context $context, ?CPP_Specializations $implementation_engine = null): string
	{
		$binding = Model::$cpp_callable_bindings->definition($target);
		$header = $binding->header();
		if ($header !== '') { $context->headers[$header] = true; }
		$mapping = $implementation_engine !== null
			? CPP_Types::implementation_representation($target->owner(), $implementation_engine)
			: CPP_Types::representation($target->owner(), $context);
		CPP_Types::record_dependencies($mapping, $context);
		$receiver = $mapping->spelling;
		if ($binding->adapter() !== '') { $receiver = $binding->adapter() . '<' . $receiver . '>'; }
		return $receiver . '::' . $binding->member();
	}
}
