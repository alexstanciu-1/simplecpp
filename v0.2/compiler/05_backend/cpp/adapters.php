<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Native adapter parameter contract, independent of source-language borrowing admission. Native uint8. */
enum cpp_adapter_mode: int
{
	case value = 0;
	case const_reference = 1;
	case reference = 2;
	case move = 3;
}

/** One concrete native parameter; no source semantic decisions are retained here. */
final readonly class cpp_adapter_parameter
{
	public type_definition $type;
	public string $name;
	public cpp_adapter_mode $mode;

	public function __construct(type_definition $type, string $name, cpp_adapter_mode $mode)
	{
		$this->type = $type;
		$this->name = $name;
		$this->mode = $mode;
	}

	/** Spell the concrete native parameter mode without admitting a source borrow feature. */
	public function declaration(): string
	{
		$type = $this->type->cpp_type();
		return match ($this->mode) {
			cpp_adapter_mode::value => $type,
			cpp_adapter_mode::const_reference => 'const ' . $type . '&',
			cpp_adapter_mode::reference => $type . '&',
			cpp_adapter_mode::move => $type . '&&',
		} . ' ' . $this->name;
	}
}

/** Private lowering request supplied by a pattern owner; native signature determines specialization identity. */
final readonly class cpp_inline_adapter
{
	/** PROPERTIES */
	public string $pattern;
	/** @var cpp_adapter_parameter[] */
	public array $parameters /** list<cpp_adapter_parameter> */;
	public type_definition $result;
	public string $body;
	public bool $noexcept;
	/** @var string[] Explicit runtime provider headers needed by the pattern body. */
	public array $headers /** list<string> */;
	/** Occurrence-specific remaining prepared conversion; excluded from native adapter identity. */
	public int $continuation /** uint16 */;

	/** CONSTRUCTOR */
	/** Retain a concrete pattern expansion; this is not a general C++ template parser. @param cpp_adapter_parameter[] $parameters
	 * @param string[] $headers
	 */
	public function __construct(string $pattern, array $parameters /** list<cpp_adapter_parameter> */, type_definition $result,
		string $body, bool $noexcept, int $continuation /** uint16 */ = value_conversion::identity, array $headers /** list<string> */ = [])
	{
		$this->pattern = $pattern;
		$this->parameters = $parameters;
		$this->result = $result;
		$this->body = $body;
		$this->noexcept = $noexcept;
		$this->continuation = $continuation;
		$this->headers = $headers;
	}

	/** METHODS */
	/** Native spelling intentionally merges required/optional identities with identical storage. */
	public function key(): string
	{
		$parts = [$this->pattern, $this->result->cpp_type(), $this->noexcept ? 'noexcept' : 'throwing'];
		foreach ($this->parameters as $parameter) {
			$parts[] = $parameter->type->cpp_type();
			$parts[] = $parameter->mode->name;
		}
		return implode("\0", $parts);
	}
}

/** Shared-view pattern consumes an already-admitted conversion; ownership transfer is explicit in concrete C++. */
final class Cpp_Shared_View
{
	public static function request(type_definition $source, type_definition $target, bool $required): cpp_inline_adapter
	{
		$body = "\t" . $target->cpp_type() . " result;\n\tresult.view = source.view;\n\tresult.ptr = source.ptr;\n"
			. "\tsource.ptr = nullptr;\n\tsource.view = nullptr;\n\treturn result;\n";
		return new cpp_inline_adapter('shared_view', [new cpp_adapter_parameter($source, 'source', cpp_adapter_mode::value)],
			$target, $body, true, $required ? value_conversion::require_owner : value_conversion::identity, ['scpp/owner_access.hpp']);
	}
}

/** Source-private adapter demand. Bundle assembly merges text; canonical types and prepared ASTs remain immutable. */
final class Cpp_Adapters
{
	/** PROPERTIES */
	/** @var cpp_inline_adapter[] */
	private array $requests /** array<string,cpp_inline_adapter> */ = [];
	private readonly string $root;

	/** CONSTRUCTOR */
	public function __construct(string $root)
	{
		$this->root = $root;
	}

	/** METHODS */
	/** Request only when lowering an actual use; deterministic keys deduplicate across contexts and units. @param string[] $arguments */
	public function call(cpp_inline_adapter $request, array $arguments /** list<string> */): string
	{
		if (count($arguments) !== count($request->parameters)) throw new \LogicException('Adapter argument count mismatch');
		$key = hash('sha256', $this->root . "\0" . $request->key());
		if (isset($this->requests[$key]) && ($this->definition($key, $this->requests[$key]) !== $this->definition($key, $request)
			|| $this->requests[$key]->headers !== $request->headers)) {
			throw new \LogicException('Conflicting concrete adapter pattern expansion');
		}
		$this->requests[$key] ??= $request;
		return 'scpp_adapter_' . $key . '(' . implode(', ', $arguments) . ')';
	}

	/** Complete dependency identities, consumed by the existing unit declaration planner. @return \Generator<type_definition> */
	public function dependencies(): \Generator
	{
		foreach ($this->requests as $request) {
			yield $request->result;
			foreach ($request->parameters as $parameter) yield $parameter->type;
		}
	}

	/** Emit only this unit's demand; guarded fragments follow complete dependencies in the including unit. @return string[] */
	public function files(): array /** array<string,string> */
	{
		$files = [];
		ksort($this->requests, \SORT_STRING);
		foreach ($this->requests as $key => $request) {
			$guard = 'SCPP_INLINE_ADAPTER_' . strtoupper($key);
			$includes = '';
			foreach ($request->headers as $header) $includes .= '#include <' . $header . ">\n";
			$files['units/' . $key . '.hpp'] = "// Concrete inline adapter; include after its complete type dependencies.\n#ifndef $guard\n#define $guard\n"
				. $includes . Cpp_Emitter::namespace_body($this->root, $this->definition($key, $request)) . "#endif\n";
		}
		return $files;
	}

	/** Render a concrete signature and its owner-supplied body without type inference. */
	private function definition(string $key, cpp_inline_adapter $request): string
	{
		$parameters = array_map(static fn(cpp_adapter_parameter $parameter): string => $parameter->declaration(), $request->parameters);
		return 'inline ' . $request->result->cpp_type() . ' scpp_adapter_' . $key . '(' . implode(', ', $parameters) . ')'
			. ($request->noexcept ? ' noexcept' : '') . " {\n" . $request->body . "}\n";
	}
}
