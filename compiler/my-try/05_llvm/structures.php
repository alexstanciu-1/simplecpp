<?php

/*
 * Role: prepared storage, concrete instances and LLVM output records.
 * Used by: LLVM_Preparation, LLVM_Generator and LLVM_Writer.
 */
namespace scpp\compiler;

/** Explicit defaults for this experiment, not the full Simple C++ type/ABI contract. */
final class llvm_policy {
	/** Source type spelling to LLVM integer type. */
	public array $types /** hash<string> */ = [];
	public string $integer_type = 'i32';
	public string $integer_max = '2147483647';
	public string $entry_name = 'main';
	public string $entry_return_type = 'i32';

	public function __construct()
	{
		$this->types['int'] = 'i32';
		$this->types['void'] = 'void';
	}
}

/** One textual LLVM output file; unused module sections stay empty. */
final class llvm_module
{
	public string $file_name;
	/**
	 * Numeric storage of llvm_function records.
	 * @storage.owner
	 */
	public Storage $functions /** Storage<llvm_function> */;
	/** LLVM external function declarations. */
	public array $external_functions /** vector<string> */ = [];
	/** LLVM global variable and constant definitions. */
	public array $globals /** vector<string> */ = [];
	/** LLVM named type definitions. */
	public array $types /** vector<string> */ = [];
	/** LLVM metadata definitions. */
	public array $metadata /** vector<string> */ = [];
	public string $text = '';

	public function __construct()
	{
		$this->functions = new Storage /** Storage<llvm_function> */();
	}
}

final class llvm_function
{
	public string $name;
	public string $return_type;
	/**
	 * Incoming values in signature order. Numeric storage of llvm_operand records.
	 * @storage.owner
	 */
	public Storage $parameters /** Storage<llvm_operand> */;
	public bool $is_entry = false;
	/**
	 * Numeric storage of llvm_block records.
	 * @storage.owner
	 */
	public Storage $blocks /** Storage<llvm_block> */;

	public function __construct()
	{
		$this->parameters = new Storage /** Storage<llvm_operand> */();
		$this->blocks = new Storage /** Storage<llvm_block> */();
	}
}

final class llvm_block {
	public string $label;
	public array $instructions /** vector<string> */ = [];
	public bool $terminated = false;
}

final class llvm_operand {
	public string $type;
	public string $text;
}

/** Storage belongs to a declaration entry, not its name. */
final class llvm_local
{
	/** @storage.reference collected_file.entries */
	public collected_name $declaration;
	public string $type;
	public string $address;
	public bool $borrowed = false;
	/** @ownership owner */
	public ?llvm_array_type $array_type = null;
	/** @reference.source llvm_prepared_file.struct_types */
	public ?llvm_struct_type $struct_type = null;
}

/** Signature passing contract plus the declaration-owned storage it supplies. */
final class llvm_parameter {
	public passing_mode $mode;
	/** @reference.source llvm_prepared_function.locals */
	public llvm_local $local;
	/** Preparation-owned value; emission copies it into llvm_function.parameters.
	 * @ownership owner
	 */
	public llvm_operand $incoming;
}

final class llvm_prepared_file {
	/** @storage.reference model.collected_files */
	public collected_file $source;
	/** @ownership owner */
	public prepared_names $names;
	/** Types required by this module, shared across the preparation result.
	 * Definitions and imported uses refer to the same type records.
	 * @reference.source LLVM_Preparation.structs
	 */
	public Keyed_Storage $struct_types /** Keyed_Storage<llvm_struct_type> */;
	/** @ownership owner */
	public Storage $functions /** Storage<llvm_prepared_function> */;
	/** Unique external targets keyed by emitted name, in first-use order.
	 * @reference.source llvm_prepared_file.functions
	 * @reference.weak
	 */
	public Keyed_Storage $external_functions /** Keyed_Storage<llvm_prepared_function> */;

	public function __construct()
	{
		$this->struct_types = new Keyed_Storage /** Keyed_Storage<llvm_struct_type> */();
		$this->functions = new Storage /** Storage<llvm_prepared_function> */();
		$this->external_functions = new Keyed_Storage /** Keyed_Storage<llvm_prepared_function> */();
	}
}

final class llvm_prepared_function
{
	/** Null only for the synthesized top-level entry (is_entry=true).
	 * @storage.reference collected_file.entries
	 */
	public ?collected_name $declaration = null;
	/** @reference.source llvm_prepared_file
	 * @reference.weak
	 */
	public llvm_prepared_file $file;
	/** Concrete arguments in formal order. */
	public array $arguments /** vector<string> */ = [];
	/** Owned locals keyed by sparse source-local declaration index.
	 * This is an integer-keyed map, not append positions or string keys.
	 * @ownership owner
	 */
	public array $locals /** hash<llvm_local, int> */ = [];
	/** Call token indexes inside this instance.
	 * @reference.source llvm_prepared_file.functions
	 * @reference.weak
	 */
	public array $calls /** hash<llvm_prepared_function, int> */ = [];
	/** Field use token indexes inside this instance.
	 * @reference.source llvm_struct_type.fields
	 */
	public array $fields /** hash<llvm_field, int> */ = [];
	public string $name;
	public string $return_type;
	/** Parameters in source order.
	 * @ownership owner
	 */
	public Storage $parameters /** Storage<llvm_parameter> */;
	public bool $is_entry = false;
	/** @reference.source parsed_file.root (syntax graph) */
	public ast_node $body;

	public function __construct()
	{
		$this->parameters = new Storage /** Storage<llvm_parameter> */();
	}
}

/** Array shape is explicit metadata, never recovered by parsing LLVM text. */
final class llvm_array_type {
	public string $element_type;
	public int $count;
}

/** Addressable storage may be a whole local or a projection into one. */
final class llvm_place {
	public string $type;
	public string $address;
	/** @reference.source llvm_local.array_type */
	public ?llvm_array_type $array_type = null;
	/** @reference.source llvm_prepared_file.struct_types */
	public ?llvm_struct_type $struct_type = null;
}

/** Ordered field shape and exact-name lookup belong to the prepared source type. */
final class llvm_struct_type {
	/** @storage.reference collected_file.entries */
	public collected_name $declaration;
	public string $name;
	/** @ownership owner */
	public Keyed_Storage $fields /** Keyed_Storage<llvm_field> */;

	public function __construct()
	{
		$this->fields = new Keyed_Storage /** Keyed_Storage<llvm_field> */();
	}
}

final class llvm_field {
	public int $index;
	public string $type;
}
