<?php

declare(strict_types=1);
namespace scpp\compiler;

require_once __DIR__ . '/../../../tools/api_generator/php/input.php';
require_once __DIR__ . '/../../../tools/api_generator/php/render.php';

/** Transient adapter over a settled type; the recipe sees no AST or symbol table. */
final readonly class cpp_concrete_type implements \Scpp\Api\ConcreteType
{
	/** PROPERTIES */
	/** Native non-owning prepared type; lifetime is bounded by private lowering. */
	private type_definition $type;
	private string $root;

	/** CONSTRUCTOR */
	public function __construct(type_definition $type, string $root)
	{
		$this->type = $type;
		$this->root = $root;
	}

	/** METHODS */
	public function identity(): string
	{
		return $this->type::class . ':' . $this->type->name;
	}

	/** Application declarations are emitted inside the same root namespace as the concrete recipe. */
	public function cppType(string $namespace): string
	{
		if (str_starts_with($this->type->cpp_type(), 'scpp::')) return '::' . $this->type->cpp_type();
		return ($namespace === $this->root ? '' : '::' . ($this->root === '' ? '' : $this->root . '::')) . $this->type->cpp_type();
	}

	/** @return string[] Runtime declaration dependencies; application dependencies use the existing declaration planner. */
	public function headers(): array
	{
		return $this->type->cpp_headers();
	}

	public function supports(string $requirement): ?bool
	{
		if (!($this->type instanceof record_type_definition) && !($this->type instanceof record_handle_definition)) return null;
		return in_array($requirement, ['copy_constructible', 'nothrow_move_constructible', 'nothrow_move_assignable', 'nothrow_destructible'], true)
			? true : null;
	}
}

/** Source-private concrete demand, merged and retired through the existing adapter artifact lifecycle. */
final class Cpp_Concrete_Collections
{
	/** PROPERTIES */
	/** @var application_collection_type[] Native non-owning, only specializations used by this source emission. */
	private array $types /** array<string,application_collection_type> */ = [];
	private readonly string $root;

	/** CONSTRUCTOR */
	public function __construct(string $root)
	{
		$this->root = $root;
	}

	/** METHODS */
	public function request(application_collection_type $type): void
	{
		$this->types[$type->cpp_type()] = $type;
	}

	/** Signatures may precede concrete definitions, including methods on their own element type. */
	public function forwards(): string
	{
		$lines = [];
		foreach ($this->types as $name => $_) $lines[$name] = 'class ' . $name . ";\n";
		ksort($lines, \SORT_STRING);
		return implode('', $lines);
	}

	/** @return \Generator<type_definition> Existing declaration planning supplies complete element types first. */
	public function dependencies(): \Generator
	{
		foreach ($this->types as $type) yield from $type->concrete_types();
	}

	/** Guarded concrete definitions follow complete dependencies, just like existing inline adapters. @return string[] */
	public function files(): array /** array<string,string> */
	{
		$files = [];
		ksort($this->types, \SORT_STRING);
		foreach ($this->types as $name => $type) {
			$key = hash('sha256', $this->root . "\0" . $name);
			$guard = 'SCPP_CONCRETE_' . strtoupper($key);
			$arguments = [];
			$includes = $type->cpp_headers();
			foreach ($type->concrete_types() as $parameter => $argument) {
				$arguments[$parameter] = new cpp_concrete_type($argument, $this->root);
				$includes = array_merge($includes, $argument->cpp_headers());
			}
			$values = ['n' => $name, 'inline' => 'inline '] + $type->recipe_arguments();
			$body = \Scpp\Api\concreteApplication($type::concrete_recipe(), $arguments, $values, $this->root);
			$header = "// Concrete application collection; include after complete element declarations.\n#ifndef $guard\n#define $guard\n";
			foreach (array_unique($includes) as $include) $header .= '#include <' . $include . ">\n";
			$header .= \Scpp\Api\concreteSupport($type::concrete_recipe());
			$files['units/' . $key . '.hpp'] = $header . Cpp_Emitter::namespace_body($this->root, $body) . "\n#endif\n";
		}
		return $files;
	}
}
