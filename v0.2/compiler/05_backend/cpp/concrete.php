<?php

declare(strict_types=1);
namespace scpp\compiler;

require_once __DIR__ . '/../../../tools/api_generator/php/input.php';
require_once __DIR__ . '/../../../tools/api_generator/php/render.php';

/** Transient adapter over a settled application value; the recipe sees no AST or symbol table. */
final readonly class cpp_concrete_type implements \Scpp\Api\ConcreteType
{
	/** PROPERTIES */
	/** Native non-owning prepared type; lifetime is bounded by private lowering. */
	private record_type_definition|shared_record_type_definition $type;
	private string $root;

	/** CONSTRUCTOR */
	public function __construct(record_type_definition|shared_record_type_definition $type, string $root)
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
		return ($namespace === $this->root ? '' : '::' . ($this->root === '' ? '' : $this->root . '::')) . $this->type->cpp_type();
	}

	/** @return string[] Runtime declaration dependencies; application dependencies use the existing declaration planner. */
	public function headers(): array
	{
		return $this->type->cpp_headers();
	}

	public function supports(string $requirement): ?bool
	{
		return in_array($requirement, ['copy_constructible', 'nothrow_move_constructible', 'nothrow_move_assignable', 'nothrow_destructible'], true)
			? true : null;
	}
}

/** Source-private concrete demand, merged and retired through the existing adapter artifact lifecycle. */
final class Cpp_Concrete_Vectors
{
	/** PROPERTIES */
	/** @var application_vector_type[] Native non-owning, only specializations used by this source emission. */
	private array $types /** array<string,application_vector_type> */ = [];
	private readonly string $root;

	/** CONSTRUCTOR */
	public function __construct(string $root)
	{
		$this->root = $root;
	}

	/** METHODS */
	public function request(application_vector_type $type): void
	{
		$this->types[$type->cpp_type()] = $type;
	}

	/** @return \Generator<type_definition> Existing declaration planning supplies complete element types first. */
	public function dependencies(): \Generator
	{
		foreach ($this->types as $type) yield $type->element_type;
	}

	/** Guarded concrete definitions follow complete dependencies, just like existing inline adapters. @return string[] */
	public function files(): array /** array<string,string> */
	{
		$files = [];
		ksort($this->types, \SORT_STRING);
		foreach ($this->types as $name => $type) {
			$key = hash('sha256', $this->root . "\0" . $name);
			$guard = 'SCPP_CONCRETE_' . strtoupper($key);
			$access = 'vector_access_' . $key;
			$element = new cpp_concrete_type($type->element_type, $this->root);
			$parts = \Scpp\Api\concreteTemplate($type::concrete_recipe(), ['e' => $element],
				['n' => $name, 'inline' => 'inline ', 'access' => 'detail::' . $access, 'buffer' => '::scpp::detail']
				+ $type::recipe_arguments(), $this->root);
			$header = "// Concrete application vector; include after complete element declarations.\n#ifndef $guard\n#define $guard\n";
			foreach (array_unique(array_merge($type->cpp_headers(), $element->headers())) as $include) $header .= '#include <' . $include . ">\n";
			// Reuse the same untyped helper source; generated output owns no independent allocation algorithm.
			$helper = \Scpp\Api\readFile(__DIR__ . '/../../../runtime/src/sequence/vector_buffer.hpp');
			$header .= "#ifndef SCPP_CONCRETE_VECTOR_BUFFER\n#define SCPP_CONCRETE_VECTOR_BUFFER\n"
				. str_replace('#pragma once', '', $helper) . "\n#endif\n";
			$declaration = "using ::scpp::bool_t;\nnamespace detail { struct $access; }\nclass $name final {\npublic:\n"
				. $parts['declaration'] . "private:\nstruct storage;\nstorage* storage_ = nullptr;\nfriend struct detail::$access;\n};\n";
			$implementation = $parts['storage'] . "namespace detail { struct $access {\n" . $parts['access'] . "}; }\n" . $parts['definition'];
			$files['units/' . $key . '.hpp'] = $header . Cpp_Emitter::namespace_body($this->root, $declaration . $implementation) . "\n#endif\n";
		}
		return $files;
	}
}
