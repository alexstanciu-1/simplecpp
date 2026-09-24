<?php

/*
 * Role: prepare demanded function instances without changing source.
 * Call map: Compiler::llvm -> LLVM_Preparation::prepare_program -> register -> prepare_instance.
 */
namespace scpp\compiler;

/** One rebuild registry; ordinary and template functions share concrete preparation. */
final class LLVM_Preparation
{
	private llvm_policy $policy;
	private \SplObjectStorage $structs;
	private \SplObjectStorage $owners;
	private \SplObjectStorage $file_indexes;
	/** Exact definition/argument keys; references to file.functions. */
	private Keyed_Storage $instances /** Keyed_Storage<llvm_prepared_function> */;
	/** Append-only work queue referencing file.functions; indexed traversal may grow it. */
	private Storage $pending /** Storage<llvm_prepared_function> */;

	/** Bind files first, register concrete roots, then process only newly demanded instances.
	 */
	public function prepare_program(Storage $sources /** Storage<collected_file> */, llvm_policy $policy): Storage /** Storage<llvm_prepared_file> */
	{
		$this->policy = $policy;
		$this->instances = new Keyed_Storage();
		$this->pending = new Storage();
		$this->owners = new \SplObjectStorage();
		$this->file_indexes = new \SplObjectStorage();
		$this->structs = (new LLVM_Struct_Preparation())->prepare($sources, $policy);
		$files /** Storage<llvm_prepared_file> */ = new Storage();
		foreach ($sources as $index => $source)
		{
			$file = new llvm_prepared_file();
			$file->source = $source;
			$file->names = (new Name_Preparation())->prepare($source);
			$this->file_indexes[$file] = $index;
			$files[] = $file;
			foreach ($source->defined_elements as $entry_index)
			{
				$entry = $source->entries[$entry_index];
				$this->owners[$entry] = $file;
				if ($entry->kind === collected_name_kind::struct_declaration) {
					$type = $this->structs[$entry];
					$file->struct_types[$type->name] = $type;
				}
			}
		}
		(new Template_Checker())->check($files, $policy);
		foreach ($files as $file)
		{
			foreach ($file->source->root->specialization->children as $statement) {
				if (!in_array($statement->kind, [node_kind::function_declaration, node_kind::struct_declaration], true)) {
					$this->register($file, null, []);
					break;
				}
			}
			foreach ($file->source->defined_elements as $index) {
				$entry = $file->source->entries[$index];
				if (($entry->kind === collected_name_kind::function_declaration) && ($entry->node->specialization->template_parameters === [])) {
					$this->register($file, $entry, []);
				}
			}
		}
		for ($index = 0; $index < count($this->pending); $index++) {
			$this->prepare_instance($this->pending[$index]);
		}
		return $files;
	}

	/** Register before visiting the body so recursive calls reuse the same instance. */
	private function register(llvm_prepared_file $file, ?collected_name $definition, array $arguments /** vector<string> */): llvm_prepared_function
	{
		$formals /** hash<int> */ = $definition?->node->specialization->template_parameters ?? [];
		if (count($formals) !== count($arguments)) {
			throw new \RuntimeException('Explicit template argument count mismatch');
		}
		foreach ($arguments as $argument) {
			if (($this->policy->types[$argument] ?? null) !== $this->policy->integer_type) {
				throw new \RuntimeException('Template proof currently supports int type arguments only');
			}
		}
		$key = json_encode([$this->file_indexes[$file], $definition?->local_index, $arguments], JSON_THROW_ON_ERROR);
		if (isset($this->instances[$key])) {
			return $this->instances[$key];
		}
		$function = new llvm_prepared_function();
		$function->file = $file;
		$function->declaration = $definition;
		$function->arguments = $arguments;
		$function->is_entry = $definition === null;
		$function->body = $definition?->node->specialization->body ?? $file->source->root;
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
			$function->name = $arguments === [] ? $name : $name . LLVM_Names::encode('<' . implode(',', $arguments) . '>');
			$type = $this->type_name($function, $definition->node->specialization->return_type);
			$function->return_type = $this->policy->types[$type] ?? throw new \RuntimeException('Unsupported function return type');
		}
		$this->instances[$key] = $function;
		$this->pending[] = $function;
		$file->functions[] = $function;
		return $function;
	}

	/** Interpret bound parameter slots without changing retained syntax or name bindings. */
	private function type_name(llvm_prepared_function $function, ast_node $syntax): string
	{
		$slot = $function->file->names->template_slots[$syntax->token_index] ?? null;
		return $slot === null ? $function->file->source->source->tokens[$syntax->token_index]->text
			: ($function->arguments[$slot] ?? throw new \RuntimeException('Missing concrete template binding'));
	}

	/** Prepare storage and calls in one concrete context; local source indexes remain unchanged. */
	private function prepare_instance(llvm_prepared_function $function): void
	{
		$file = $function->file;
		$scope = $function->body->specialization->scope;
		foreach ($file->source->defined_elements as $index)
		{
			$declaration = $file->source->entries[$index];
			if (($declaration->kind !== collected_name_kind::variable_declaration) || ($declaration->scope !== $scope)) {
				continue;
			}
			$binding = $declaration->node->specialization;
			if ((!($binding instanceof binding_specialization) && !($binding instanceof parameter_specialization)) || ($binding->type_syntax === null)) {
				throw new \RuntimeException('LLVM preparation requires an explicitly typed variable');
			}
			$type_syntax = $binding->type_syntax;
			$array_syntax = $type_syntax->specialization;
			$name = $this->type_name($function, $type_syntax);
			$record_declaration = $file->names->types[$type_syntax->token_index] ?? null;
			$record = $record_declaration === null ? null : $this->structs[$record_declaration];
			if (($record !== null) && (($array_syntax instanceof array_type_specialization) || ($binding instanceof parameter_specialization))) {
				throw new \RuntimeException('Struct arrays and whole-struct parameters are not supported yet');
			}
			$type = $record?->name ?? ($this->policy->types[$name] ?? null);
			if (($type === null) || ($type === 'void')) {
				throw new \RuntimeException("LLVM experiment has no type mapping for $name");
			}
			$local = new llvm_local();
			$local->declaration = $declaration;
			$local->type = $type;
			$local->struct_type = $record;
			if ($record !== null) {
				$file->struct_types[$record->name] = $record;
			}
			if ($array_syntax instanceof array_type_specialization)
			{
				if ($type !== $this->policy->integer_type) {
					throw new \RuntimeException('Fixed arrays currently require int elements');
				}
				$text = ltrim($file->source->source->tokens[$array_syntax->count->token_index]->text, '0');
				$maximum = (string) PHP_INT_MAX;
				if ((strlen($text) > strlen($maximum)) || ((strlen($text) === strlen($maximum)) && (strcmp($text, $maximum) > 0))) {
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
			foreach ($function->declaration->node->specialization->parameters as $index => $syntax)
			{
				$entry = $file->names->declarations[$syntax->specialization->name_token_index];
				$parameter = new llvm_parameter();
				$parameter->mode = $syntax->specialization->mode;
				$parameter->local = $function->locals[$entry->local_index];
				$parameter->local->borrowed = $parameter->mode === passing_mode::reference;
				$parameter->incoming = new llvm_operand();
				$parameter->incoming->type = $parameter->local->borrowed ? 'ptr' : $parameter->local->type;
				$parameter->incoming->text = $parameter->local->borrowed ? $parameter->local->address : '%_Garg' . $index;
				$function->parameters[] = $parameter;
			}
		}
		(new LLVM_Struct_Preparation())->fields($function);
		foreach ($file->source->function_references as $index)
		{
			$use = $file->source->entries[$index];
			if ($use->scope !== $scope) {
				continue;
			}
			$definition = $file->names->function_references[$use->token_index];
			$arguments /** vector<string> */ = [];
			foreach ($use->node->specialization->template_arguments as $argument) {
				$arguments[] = $this->type_name($function, $argument);
			}
			$target = $this->register($this->owners[$definition], $definition, $arguments);
			$function->calls[$use->token_index] = $target;
			if ($target->file !== $file) {
				$file->external_functions[$target->name] = $target;
			}
		}
	}
}
