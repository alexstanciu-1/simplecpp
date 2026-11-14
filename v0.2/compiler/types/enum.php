<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Nominal enum identity; storage and backing facts are shared by every occurrence of this declaration. */
final class enum_type_definition extends type_definition
{
	/** PROPERTIES */
	/** Native non-owning defining AST. */
	public readonly ast_enum_declaration $declaration;

	/** PREP FACTS */
	/** Prepared fact: null until all cases validate. The source backing can differ from compact native storage. */
	private ?type_definition $backing = null;
	/** Prepared fact: canonical native storage; null until validation succeeds. */
	private ?type_definition $storage = null;
	/** Prepared fact: immutable enum_value signature, published at the declaration gate before body readers. */
	private ?runtime_function $value_function = null;
	/** Prepared fact: immutable checked-construction binding; shared across call sites. */
	private ?runtime_function $from_value_function = null;
	/** Prepared fact: immutable case-name binding; shared across call sites. */
	private ?runtime_function $name_function = null;

	/** CONSTRUCTOR */
	public function __construct(ast_enum_declaration $declaration)
	{
		parent::__construct($declaration->name());
		$this->declaration = $declaration;
	}

	/** METHODS */
	public function backing(): type_definition
	{
		return $this->backing ?? throw new \LogicException('Enum declaration is not prepared');
	}

	public function storage(): type_definition
	{
		return $this->storage ?? throw new \LogicException('Enum storage is not prepared');
	}

	/** Publish canonical references and immutable helper signatures after every case succeeds. */
	public function settle(type_definition $backing, type_definition $storage): void
	{
		$this->backing = $backing;
		$this->value_function = new runtime_function('enum_value', [$this], $backing,
			$backing === type_string::instance() ? 'scpp_enum_value_' . Cpp_Emitter::qualified_path($this->name)
				: 'static_cast<' . $backing->cpp_type() . '>', $backing->cpp_headers()[0]);
		$input = $backing === type_string::instance() ? $backing : type_int64::instance();
		$this->from_value_function = new runtime_function('enum_from_value', [$input], $this,
			'scpp_enum_from_value_' . Cpp_Emitter::qualified_path($this->name), $input->cpp_headers()[0]);
		$this->name_function = new runtime_function('enum_name', [$this], type_string::instance(),
			'scpp_enum_name_' . Cpp_Emitter::qualified_path($this->name), type_string::instance()->cpp_headers()[0]);
		$this->storage = $storage;
	}

	public function prepared(): bool
	{
		return $this->storage !== null;
	}

	public function supports_inferred_local(): bool
	{
		return true;
	}

	public function supports_instance_field(): bool
	{
		return true;
	}

	/** Emit the already-resolved case without backing-value lookup or conversion. */
	public function cpp_case(ast_enum_case $case): string
	{
		return $this->cpp_type() . '::' . Cpp_Identifier::source($case->short_name);
	}

	public function cpp_trivial_value(): bool
	{
		return true;
	}

	public function binary_result(binary_operator $operator, type_definition $right): ?type_definition
	{
		return $right === $this && in_array($operator,
			[binary_operator::equal, binary_operator::not_equal, binary_operator::identical, binary_operator::not_identical], true)
			? type_bool::instance() : null;
	}

	/** Emit only the equality operators admitted for this nominal identity. */
	public function lower_cpp_binary(binary_operator $operator, string $left, string $right, type_definition $right_type, bool $typed_initializer = false): string
	{
		$sign = match ($operator) {
			binary_operator::equal, binary_operator::identical => '==',
			binary_operator::not_equal, binary_operator::not_identical => '!=',
			default => throw new \LogicException('Unprepared enum operator'),
		};
		return '(' . $left . ' ' . $sign . ' ' . $right . ')';
	}

	public function cpp_binary_reorderable(binary_operator $operator, bool $same_spelling = false): bool
	{
		return $this->binary_result($operator, $this) !== null;
	}

	public function cpp_namespace(): string
	{
		return $this->declaration->namespace === null ? 'scpp_records'
			: 'scpp_records_ns_' . Cpp_Emitter::qualified_path($this->declaration->namespace);
	}

	public function cpp_type(): string
	{
		return $this->cpp_namespace() . '::' . Cpp_Identifier::source($this->declaration->short_name);
	}

	/** @return string[] */
	public function cpp_headers(): array /** list<string> */
	{
		return $this->storage()->cpp_headers();
	}

	/** Reuse the concrete callable path; static_cast is a native expression, not a template instantiation. */
	public function value_function(): runtime_function
	{
		return $this->value_function ?? throw new \LogicException('Enum value signature is not prepared');
	}

	public function from_value_function(): runtime_function
	{
		return $this->from_value_function ?? throw new \LogicException('Enum construction signature is not prepared');
	}
	/** Select a concrete helper without allocating a call-site signature. */
	public function helper_function(dependency_use $use): runtime_function
	{
		return match ($use) {
			dependency_use::enum_from_value => $this->from_value_function(),
			dependency_use::enum_value => $this->value_function(),
			dependency_use::enum_name => $this->name_function ?? throw new \LogicException('Enum name signature is not prepared'),
		};
	}
}
