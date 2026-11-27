<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Canonical core Exception handle; source descendants reach it through settled nominal ancestry. */
final class type_exception extends type_definition
{
	/** PROPERTIES */
	public readonly runtime_function $factory;
	private readonly runtime_function $message;

	private readonly string $header;
	private readonly string $handle;

	/** CONSTRUCTOR */
	private function __construct(string $name, string $header, string $handle, string $factory, string $message)
	{
		parent::__construct($name);
		$this->header = $header;
		$this->handle = $handle;
		$this->factory = new runtime_function('make', [type_string::instance()], $this, $factory, $header, type_qualified: true);
		$this->message = new runtime_function('get_message', [$this], type_string::instance(), $message, $header, receiver_method: true);
	}

	/** METHODS */
	public function cpp_type(): string
	{
		return $this->handle;
	}

	/** @return string[] */
	public function cpp_headers(): array /** list<string> */
	{
		return [$this->header];
	}

	public function supports_instance_field(): bool
	{
		return true;
	}

	/** Root diagnostic accessors also apply to source descendants. */
	public function runtime_method(string $name): ?runtime_function
	{
		if ($name === $this->message->name()) return $this->message;
		$result = match ($name) {
			'get_line' => type_registry::integer(),
			'get_file' => type_registry::string(),
			default => null,
		};
		return $result === null ? null : new runtime_function($name, [$this], $result, $name, $this->header, receiver_method: true);
	}

	public function supports_inferred_local(): bool
	{
		return true;
	}

	public function prepare_condition(ast_expression $value): void
	{
	}

	public function lower_cpp_condition(string $value, bool $negated = false): string
	{
		return '(' . $value . ').has_error()';
	}

	/** Source shared owners widen by transferring a retained reference, with an adjusted payload view. */
	public function prepare_scalar_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		$source = $value->type_def();
		if ($source instanceof shared_record_type_definition && $source->payload->exception_descendant()) {
			return value_conversion::owner_view;
		}
		return parent::prepare_scalar_conversion($value, $context);
	}

	public function cpp_conversion_adapter(int $conversion /** uint16 */, ?type_definition $source): ?cpp_inline_adapter
	{
		return $conversion === value_conversion::owner_view ? Cpp_Shared_View::request($source, $this, false) : null;
	}

	/** STATIC METHODS */
	public static function instance(string $name, string $header, string $handle, string $factory, string $message): self
	{
		static $type = null;
		return $type ??= new self($name, $header, $handle, $factory, $message);
	}
}
