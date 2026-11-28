<?php

declare(strict_types=1);
namespace scpp\compiler;

require_once __DIR__ . '/../../../tools/api_generator/php/input.php';
require_once __DIR__ . '/../../../tools/api_generator/php/render.php';

/** Transient adapter over a settled type; the recipe sees no AST or symbol table. */
final readonly class cpp_concrete_type implements \Scpp\Api\ConcreteType, \Scpp\Api\ConcreteHashKey
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
		return $this->type->concrete_identity();
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

	public function hashExpression(string $key): ?string
	{
		return $this->type->lower_cpp_hash_key($key);
	}

	public function equalityExpression(string $left, string $right): ?string
	{
		return $this->type->lower_cpp_key_equal($left, $right);
	}

	public function supports(string $requirement): ?bool
	{
		return $this->type->concrete_storage_support($requirement);
	}
}

/** Source-private concrete demand, merged and retired through the existing adapter artifact lifecycle. */
final class Cpp_Concrete_Collections
{
	/** PROPERTIES */
	/** @var application_collection_type[] Native non-owning, only specializations used by this source emission. */
	private array $types /** array<string,application_collection_type> */ = [];
	/** @var string[] Private helper headers requested by this source. */
	private array $operation_headers = [];
	private readonly string $root;

	/** CONSTRUCTOR */
	public function __construct(string $root)
	{
		$this->root = $root;
	}

	/** METHODS */
	public function request(application_collection_type $type): void
	{
		if (isset($this->types[$type->cpp_type()])) return;
		foreach ($type->concrete_types() as $argument) {
			if ($argument instanceof application_collection_type) $this->request($argument);
		}
		$this->types[$type->cpp_type()] = $type;
	}

	/** Request the provider-owned definition once per concrete type in this unit.
	 * @param ast_field_assignment[] $writes @param string[] $inputs
	 */
	public function destination_append(destination_append_type $type, array $writes, string $receiver, array $inputs): string
	{
		$this->request($type);
		$name = $type->cpp_type();
		$helper = 'destination_' . $name;
		$key = hash('sha256', $this->root . "\0" . $name);
		$path = 'units/' . hash('sha256', 'destination:' . $key) . '.hpp';
		if (!isset($this->operation_headers[$path])) {
			$params = [];
			$names = [];
			foreach ($writes as $i => $write) {
				$names[] = 'f' . $i;
				$params[] = $write->target->type_def()->cpp_type() . ' f' . $i;
			}
			$text = $type->destination_append_definition(['@helper@' => $helper, '@vector@' => $name,
				'@element@' => $type->write_type()->cpp_type(), '@parameters@' => implode(', ', $params), '@inputs@' => implode(', ', $names)]);
			$this->operation_headers[$path] = "#pragma once\n#include \"" . $key . ".hpp\"\n"
				. Cpp_Emitter::namespace_body($this->root, $text);
		}
		return $helper . '::append(' . implode(', ', [$receiver, ...$inputs]) . ')';
	}

	/** Publish the concrete PHP probe only for a caller that uses this application collection. */
	public function request_indexed_isset(application_indexed_presence_type $provider): void
	{
		$this->request($provider);
		$key = hash('sha256', $this->root . "\0" . $provider->cpp_type());
		$path = 'units/' . hash('sha256', 'php-isset:' . $key) . '.hpp';
		if (isset($this->operation_headers[$path])) return;
		$body = \Scpp\Api\template('collections/php_hash_isset', [
			'hash' => (new cpp_concrete_type($provider, $this->root))->cppType('scpp::php'),
			'key' => (new cpp_concrete_type($provider->entry_probe_key_type(), $this->root))->cppType('scpp::php'),
			'method' => $provider->entry_presence_method(),
		]);
		$this->operation_headers[$path] = "#pragma once\n#include \"" . $key . ".hpp\"\n" . $body;
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
		foreach ($this->types as $type) foreach ($type->concrete_types() as $argument) yield $argument;
	}

	/** Guarded concrete definitions follow complete dependencies, just like existing inline adapters. @return string[] */
	public function files(): array /** array<string,string> */
	{
		$files = [];
		foreach ($this->types as $name => $type) {
			$key = hash('sha256', $this->root . "\0" . $name);
			$guard = 'SCPP_CONCRETE_' . strtoupper($key);
			$arguments = [];
			$includes = $type->cpp_headers();
			$forwards = '';
			foreach ($type->concrete_types() as $parameter => $argument) {
				if ($argument instanceof owned_callable_type) $forwards .= Cpp_Emitter::callable_forwards($argument);
				$arguments[$parameter] = new cpp_concrete_type($argument, $this->root);
				$includes = array_merge($includes, $argument->cpp_headers());
			}
			$values = ['n' => $name, 'inline' => 'inline '] + $type->recipe_arguments();
			$body = \Scpp\Api\concreteApplication($type::concrete_recipe(), $arguments, $values, $this->root);
			$header = "// Concrete application collection; include after complete element declarations.\n#ifndef $guard\n#define $guard\n";
			foreach (array_unique($includes) as $include) $header .= '#include <' . $include . ">\n";
			if (($values['mt_segments'] ?? '') === '1') $header .= "namespace scpp::mt { struct job; }\n";
			$header .= \Scpp\Api\concreteSupport($type::concrete_recipe());
			foreach ($type->concrete_types() as $argument) {
				if ($argument instanceof application_collection_type) {
					$dependency = hash('sha256', $this->root . "\0" . $argument->cpp_type());
					$header .= '#include "' . $dependency . '.hpp"' . "\n";
				}
			}
			$files['units/' . $key . '.hpp'] = $header . Cpp_Emitter::namespace_body($this->root, $forwards . $body) . "\n#endif\n";
		}
		return $files + $this->operation_headers;
	}
}
