<?php

/*
 * Role: prepare demanded function instances without changing source.
 * Call map: Compiler::llvm -> LLVM_Preparation::prepare_program -> register -> prepare_instance.
 */
namespace scpp\compiler;

/** One rebuild registry; ordinary and template functions share concrete preparation. */
final class LLVM_Preparation {
	/** A fresh registry isolates every rebuild, including after a failed run. */
	public function prepare_program(Storage $sources /** Storage<collected_file> */, llvm_policy $policy): Storage /** Storage<llvm_prepared_file> */
	{
		return (new LLVM_Preparation_Run($sources, $policy))->prepare_program($sources);
	}
}

final class LLVM_Preparation_Run
{
	private llvm_policy $policy;
	private \SplObjectStorage $structs /** hash<llvm_struct_type, shared<collected_name>> */;
	private \SplObjectStorage $owners /** hash<llvm_prepared_file, shared<collected_name>> */;
	private \SplObjectStorage $file_indexes /** hash<int, shared<llvm_prepared_file>> */;
	/** Exact definition/argument keys; references to file.functions. */
	private Keyed_Storage $instances /** Keyed_Storage<llvm_prepared_function> */;
	/** Append-only work queue referencing file.functions; indexed traversal may grow it. */
	private Storage $pending /** Storage<llvm_prepared_function> */;

	/** Create invocation-local instance queues and identity indexes before preparing functions. */
	public function __construct(Storage $sources /** Storage<collected_file> */, llvm_policy $policy)
	{
		$this->policy = $policy;
		$this->instances = new Keyed_Storage /** Keyed_Storage<llvm_prepared_function> */();
		$this->pending = new Storage /** Storage<llvm_prepared_function> */();
		$owners /** hash<llvm_prepared_file, shared<collected_name>> */ = new \SplObjectStorage /** hash<llvm_prepared_file, shared<collected_name>> */();
		$this->owners = $owners;
		$file_indexes /** hash<int, shared<llvm_prepared_file>> */ = new \SplObjectStorage /** hash<int, shared<llvm_prepared_file>> */();
		$this->file_indexes = $file_indexes;
		$structs /** hash<llvm_struct_type, shared<collected_name>> */ = (new LLVM_Struct_Preparation())->prepare($sources, $policy);
		$this->structs = $structs;
	}

	/** Bind files first, register roots, then process demanded instances. */
	public function prepare_program(Storage $sources /** Storage<collected_file> */): Storage /** Storage<llvm_prepared_file> */
	{
		$files /** Storage<llvm_prepared_file> */ = new Storage();
		foreach ($sources as $index => $source)
		{
			$file = new llvm_prepared_file();
			$file->source = $source;
			$file->names = (new Name_Preparation())->prepare($source);
			$entries /** Storage<collected_name> */ = $source->entries;
			$struct_types /** Keyed_Storage<llvm_struct_type> */ = $file->struct_types;
			$this->file_indexes[$file] = $index;
			$files[] = $file;
			foreach ($source->defined_elements as $entry_index)
			{
				$entry = $entries[$entry_index];
				$this->owners[$entry] = $file;
				if ($entry->kind === collected_name_kind::struct_declaration) {
					$type = $this->structs[$entry];
					$struct_types[$type->name] = $type;
				}
			}
		}
		(new Template_Checker())->check($files, $this->policy);
		foreach ($files as $file)
		{
			$entries /** Storage<collected_name> */ = $file->source->entries;
			foreach (Syntax_Nodes::block_data($file->source->root)->children as $statement) {
				if (!(($statement->kind === node_kind::function_declaration) || ($statement->kind === node_kind::struct_declaration))) {
					$this->register($file, null, []);
					break;
				}
			}
			foreach ($file->source->defined_elements as $index)
			{
				$entry = $entries[$index];
				if ($entry->kind === collected_name_kind::function_declaration) {
					if (q_count(Syntax_Nodes::function_data($entry->node)->template_parameters) === 0) {
						$this->register($file, $entry, []);
					}
				}
			}
		}
		for ($index = 0; $index < q_count($this->pending); $index++) {
			$this->prepare_instance($this->pending[$index]);
		}
		return $files;
	}

	/** Register before visiting the body so recursive calls reuse the same instance. */
	private function register(llvm_prepared_file $file, ?collected_name $definition, array $arguments /** vector<string> */): llvm_prepared_function
	{
		$formals /** hash<int> */ = [];
		if ($definition !== null) {
			$formals = Syntax_Nodes::function_data($definition->node)->template_parameters;
		}
		if (q_count($formals) !== q_count($arguments)) {
			throw new \RuntimeException('Explicit template argument count mismatch');
		}
		foreach ($arguments as $argument)
		{
			if (!isset($this->policy->types[$argument])) {
				throw new \RuntimeException('Template proof currently supports int type arguments only');
			}
			if ($this->policy->types[$argument] !== $this->policy->integer_type) {
				throw new \RuntimeException('Template proof currently supports int type arguments only');
			}
		}
		// Length-prefixed type arguments keep the internal identity unambiguous.
		$key = 'f' . $this->file_indexes[$file] . ($definition === null ? ':entry' : ':d' . $definition->local_index);
		foreach ($arguments as $argument) {
			$key .= ':' . string_byte_len($argument) . ':' . $argument;
		}
		if (isset($this->instances[$key])) {
			return $this->instances[$key];
		}
		$function = new llvm_prepared_function();
		$function->file = $file;
		$function->declaration = $definition;
		$function->arguments = $arguments;
		$function->is_entry = $definition === null;
		$function->body = $definition === null ? $file->source->root : Syntax_Nodes::function_data($definition->node)->body;
		if ($definition === null) {
			$function->name = $this->policy->entry_name;
			$function->return_type = $this->policy->entry_return_type;
		}
		else
		{
			if ($definition->name === $this->policy->entry_name) {
				throw new \RuntimeException('The entry function name is reserved');
			}
			$name = LLVM_Names::declaration($definition, $this->file_indexes[$file]);
			$function->name = q_count($arguments) === 0 ? $name : $name . LLVM_Names::encode('<' . LLVM_Text::join($arguments, ',') . '>');
			$type = $this->type_name($function, Syntax_Nodes::function_data($definition->node)->return_type);
			if (!isset($this->policy->types[$type])) {
				throw new \RuntimeException('Unsupported function return type');
			}
			$function->return_type = $this->policy->types[$type];
		}
		$this->instances[$key] = $function;
		$this->pending[] = $function;
		$functions /** Storage<llvm_prepared_function> */ = $file->functions;
		$functions[] = $function;
		return $function;
	}

	/** Interpret bound parameter slots without changing retained syntax or name bindings. */
	private function type_name(llvm_prepared_function $function, ast_node $syntax): string
	{
		if (!isset($function->file->names->template_slots[$syntax->token_index])) {
			$tokens /** Storage<token> */ = $function->file->source->source->tokens;
			return $tokens[$syntax->token_index]->text();
		}
		$slot /** int */ = $function->file->names->template_slots[$syntax->token_index];
		if (!isset($function->arguments[$slot])) {
			throw new \RuntimeException('Missing concrete template binding');
		}
		return $function->arguments[$slot];
	}

	/** A required declaration type is checked before the native nullable boundary. */
	private function declaration_type(collected_name $declaration): ast_node
	{
		if ($declaration->node->kind === node_kind::parameter_declaration) {
			return Syntax_Nodes::parameter_data($declaration->node)->type_syntax;
		}
		$binding = Syntax_Nodes::binding_data($declaration->node);
		if ($binding->type_syntax === null) {
			throw new \RuntimeException('LLVM preparation requires an explicitly typed variable');
		}
		return object_cast($binding->type_syntax, ast_node::class);
	}

	/** Prepare storage and calls in one concrete context; local source indexes remain unchanged. */
	private function prepare_instance(llvm_prepared_function $function): void
	{
		$file = $function->file;
		$entries /** Storage<collected_name> */ = $file->source->entries;
		$tokens /** Storage<token> */ = $file->source->source->tokens;
		$struct_types /** Keyed_Storage<llvm_struct_type> */ = $file->struct_types;
		$parameters /** Storage<llvm_parameter> */ = $function->parameters;
		$external_functions /** Keyed_Storage<llvm_prepared_function> */ = $file->external_functions;
		$scope /** scope */ = object_cast(weakref_get(Syntax_Nodes::block_data($function->body)->scope), scope::class);
		foreach ($file->source->defined_elements as $index)
		{
			$declaration = $entries[$index];
			if (($declaration->kind !== collected_name_kind::variable_declaration) || (object_cast(weakref_get($declaration->scope), scope::class) !== $scope)) {
				continue;
			}
			$is_parameter = $declaration->node->kind === node_kind::parameter_declaration;
			$type_syntax = $this->declaration_type($declaration);
			$is_array = $type_syntax->kind === node_kind::array_type;
			$name = $this->type_name($function, $type_syntax);
			$local = new llvm_local();
			$local->declaration = $declaration;
			$type = '';
			if (isset($file->names->types[$type_syntax->token_index]))
			{
				$record_declaration /** collected_name */ = $file->names->types[$type_syntax->token_index];
				$record /** llvm_struct_type */ = $this->structs[$record_declaration];
				if (($is_array) || ($is_parameter)) {
					throw new \RuntimeException('Struct arrays and whole-struct parameters are not supported yet');
				}
				$type = $record->name;
				$local->struct_type = $record;
				$struct_types[$record->name] = $record;
			}
			else
			{
				if (!isset($this->policy->types[$name])) {
					throw new \RuntimeException('LLVM experiment has no type mapping for ' . $name);
				}
				$type = $this->policy->types[$name];
				if ($type === 'void') {
					throw new \RuntimeException('LLVM experiment has no type mapping for ' . $name);
				}
			}
			$local->type = $type;
			if ($is_array)
			{
				$array_syntax = Syntax_Nodes::array_type_data($type_syntax);
				if ($type !== $this->policy->integer_type) {
					throw new \RuntimeException('Fixed arrays currently require int elements');
				}
				$text = LLVM_Text::decimal($tokens[$array_syntax->count->token_index]->text());
				$maximum = '' . \PHP_INT_MAX;
				if (LLVM_Text::decimal_exceeds($text, $maximum)) {
					throw new \RuntimeException('Fixed array size exceeds compiler capacity');
				}
				$local->array_type = new llvm_array_type();
				$local->array_type->element_type = $type;
				$local->array_type->count = (int) $text;
				$local->type = '[' . $local->array_type->count . ' x ' . $type . ']';
			}
			$local->address = '%' . LLVM_Names::declaration($declaration, $this->file_indexes[$file]);
			$function->locals[$index] = $local;
		}
		if ($function->declaration !== null)
		{
			foreach (Syntax_Nodes::function_data($function->declaration->node)->parameters as $index => $syntax)
			{
				$entry = $file->names->declarations[Syntax_Nodes::parameter_data($syntax)->name_token_index];
				$parameter = new llvm_parameter();
				$parameter->mode = Syntax_Nodes::parameter_data($syntax)->mode;
				$parameter->local = $function->locals[$entry->local_index];
				$parameter->local->borrowed = $parameter->mode === passing_mode::reference;
				$parameter->incoming = new llvm_operand();
				$parameter->incoming->type = $parameter->local->borrowed ? 'ptr' : $parameter->local->type;
				$parameter->incoming->text = $parameter->local->borrowed ? $parameter->local->address : '%_Garg' . $index;
				$parameters[] = $parameter;
			}
		}
		(new LLVM_Struct_Preparation())->fields($function);
		foreach ($file->source->function_references as $index)
		{
			$use = $entries[$index];
			if (object_cast(weakref_get($use->scope), scope::class) !== $scope) {
				continue;
			}
			$definition = $file->names->function_references[$use->token_index];
			$arguments /** vector<string> */ = [];
			foreach (Syntax_Nodes::call_data($use->node)->template_arguments as $argument) {
				$arguments[] = $this->type_name($function, $argument);
			}
			$target = $this->register($this->owners[$definition], $definition, $arguments);
			$function->calls[$use->token_index] = $target;
			if ($target->file !== $file) {
				$external_functions[$target->name] = $target;
			}
		}
	}
}
