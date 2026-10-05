<?php

/* Backend call spelling is not part of the semantic callable identity or signature. */
namespace scpp\compiler;

final class cpp_callable_binding
{
	private registered_static_factory $semantic_target;
	private string $member_name;
	private string $required_header;

	public function __construct(registered_static_factory $target, string $member, string $header)
	{
		if ($member === '') {
			throw new \InvalidArgumentException('C++ static factory member must not be empty');
		}
		$this->semantic_target = $target;
		$this->member_name = $member;
		$this->required_header = $header;
	}

	public function target(): registered_static_factory { return $this->semantic_target; }
	public function member(): string { return $this->member_name; }
	public function header(): string { return $this->required_header; }
}

final class CPP_Callable_Bindings
{
	private Keyed_Storage $definitions /** Keyed_Storage<cpp_callable_binding> */;

	public function __construct()
	{
		$this->definitions = new Keyed_Storage /** Keyed_Storage<cpp_callable_binding> */();
	}

	public function bind_static(registered_static_factory $target, string $member, string $header): void
	{
		$definitions /** Keyed_Storage<cpp_callable_binding> */ = $this->definitions;
		$definitions->add($target->key(), new cpp_callable_binding($target, $member, $header));
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
	/** Backend-owned dispatch keeps callable identities independent of any output target. */
	public static function reference(callable_target_i $target, cpp_generation_context $context): string
	{
		if ($target instanceof collected_function) {
			return self::source_reference(object_cast($target, collected_function::class));
		}
		if ($target instanceof registered_static_factory) {
			return self::runtime_reference(object_cast($target, registered_static_factory::class), $context);
		}
		throw new \LogicException('C++ callable target is not supported');
	}

	public static function source_reference(collected_function $target): string
	{
		return CPP_Generator::source_name('function', $target->name);
	}

	public static function runtime_reference(registered_static_factory $target,
		cpp_generation_context $context): string
	{
		$binding = Model::$cpp_callable_bindings->definition($target);
		$header = $binding->header();
		if ($header !== '') { $context->headers[$header] = true; }
		return CPP_Declarations::type($target->owner(), $context) . '::' . $binding->member();
	}
}
