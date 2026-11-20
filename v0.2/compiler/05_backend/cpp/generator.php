<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Concrete producer layouts and resume bodies; transient state numbering never enters prepared AST facts. */
trait Cpp_Generator
{
	/** Lower one source's cursor layouts before the publication join. @return cpp_generator_layout[] */
	public function generator_plans(ast_file_node $file): array /** array<string,cpp_generator_layout> */
	{
		$plans = [];
		foreach ($file->declaration_nodes as $node) {
			if ($node instanceof ast_function_declaration && $node->return_type_def() instanceof generator_type_definition) {
				$plans[$node->symbol_key()] = $this->plan_generator($node->return_type_def());
			}
		}
		return $plans;
	}

	/** Visit the signature and then its source-owned cursor layout. @param \Closure(type_definition):void $visit */
	public function visit_application_types(ast_function_declaration $function, \Closure $visit): void
	{
		self::visit_signature_application_types($function, $visit);
		if ($function->return_type_def() instanceof generator_type_definition) {
			$plan = $this->generator_plan($function->return_type_def());
			foreach ($plan->types as $type) $visit($type);
		}
	}

	private function generator_plan(generator_type_definition $type): cpp_generator_layout
	{
		if (isset($this->generator_layouts[$type->function->symbol_key()])) return $this->generator_layouts[$type->function->symbol_key()];
		if ($this->headers !== null) throw new \LogicException('Missing source-owned cursor layout at publication');
		return $this->generator_layouts[$type->function->symbol_key()] = $this->plan_generator($type);
	}

	/** @param bool[] $headers */
	private function generator_layout(generator_type_definition $type, array &$headers /** array<string,bool> */): string
	{
		$plan = $this->generator_plan($type);
		$headers += $plan->headers;
		return $plan->text;
	}

	/** Private lowering reads only this source-owned body's prepared local index. */
	private function plan_generator(generator_type_definition $type): cpp_generator_layout
	{
		$type->function->body()->require_prepared();
		$headers = [];
		$types = [];
		$name = $type->cpp_type();
		$fields = [];
		$parameters = [];
		$values = [];
		foreach ($type->function->parameters as $parameter) {
			$native = $parameter->type_def()->cpp_type();
			$local = $this->name($parameter);
			$fields[] = $native . ' ' . $local . ';';
			$parameters[] = $native . '&& ' . $local;
			$values[] = 'static_cast<' . $native . '&&>(' . $local . ')';
			foreach ($parameter->type_def()->cpp_headers() as $header) $headers[$header] = true;
		}
		foreach ($type->cpp_headers() as $header) $headers[$header] = true;
		$headers['new'] = true;
		$params = implode(', ', $parameters);
		$init = implode(', ', $values);
		$layout = implode("\n", $fields);
		$output = $this->generator_outputs($type);
		$slots = [];
		$cleanup = [];
		$moves = [];
		$flags = [];
		self::visit_generator_bindings($type->function->body(), function (ast_local_declaration|ast_foreach_statement $local)
			use (&$headers, &$types, &$slots, &$flags, &$cleanup, &$moves): void {
			if ($local instanceof ast_foreach_statement) {
				$id = 'iteration_' . $local->start;
				$slot = $id;
				$declaration = $this->generator_iteration_layout($local, $headers, $types);
			} else {
				$local_type = $local->type_def();
				$id = $local->storage_id();
				$slot = 'local_' . $id;
				$declaration = "using {$slot}_type = " . $local_type->cpp_type() . ';';
				foreach ($local_type->cpp_headers() as $header) $headers[$header] = true;
				if ($local_type instanceof record_type_definition || $local_type instanceof record_handle_definition || $local_type instanceof enum_type_definition || $local_type instanceof application_collection_type) $types[] = $local_type;
			}
			$slots[] = "$declaration struct {$slot}_slot { union { {$slot}_type value; }; {$slot}_slot() {} ~{$slot}_slot() {} }; {$slot}_slot $slot;";
			$flags[] = "bool live_$id = false;";
			$cleanup[] = $this->generator_destroy($local);
			$moves[] = "if (other.live_$id) { new (&$slot.value) {$slot}_type(static_cast<{$slot}_type&&>(other.$slot.value)); live_$id = true; }";
		});
		$flags = implode("\n", $flags);
		$slots = implode("\n", $slots);
		$cleanup = implode("\n", array_reverse($cleanup));
		$moves = $moves === [] ? '' : 'try { ' . implode("\n", $moves) . ' } catch (...) { close(); throw; }';
		$text = <<<CPP
struct $name {
    struct arguments { $layout };
    unsigned int state = 0;
    union { arguments storage; };
    $flags
    $slots
    explicit $name($params) : storage{ $init } {}
    $name(const $name&) = delete;
    $name& operator=(const $name&) = delete;
    $name($name&& other) : state(other.state) {
        if (state != ~0u) {
            new (&storage) arguments(static_cast<arguments&&>(other.storage));
            $moves
            other.close();
        }
    }
    ~$name() { close(); }
    void close() noexcept {
        if (state != ~0u) { state = ~0u; $cleanup storage.~arguments(); }
    }
    bool next($output);
};

CPP;
		return new cpp_generator_layout($text, $headers, $types);
	}

	private function generator_outputs(generator_type_definition $type): string
	{
		return '[[maybe_unused]] ' . $type->iteration_value_type()->cpp_type() . '& yield_value'
			. ($type->iteration_key_type() === null ? '' : ', [[maybe_unused]] ' . $type->iteration_key_type()->cpp_type() . '& yield_key');
	}

	/** Emit one producer's factory and resume body. No synthetic syntax or copied prepared statements.
	 * @param ast_function_declaration[] $declarations
	 * @param ast_global_declaration[] $globals
	 * @param bool[] $headers
	 */
	private function generator_definition(generator_type_definition $type, array &$declarations /** array<string,ast_function_declaration> */,
		array &$globals /** array<string,ast_global_declaration> */, array &$headers /** array<string,bool> */): string
	{
		$function = $type->function;
		$function->body()->require_prepared();
		$name = $type->cpp_type();
		$values = [];
		$aliases = [];
		foreach ($function->parameters as $parameter) {
			$local = $this->name($parameter);
			$values[] = 'static_cast<' . $parameter->type_def()->cpp_type() . '&&>(' . $local . ')';
			$aliases[] = '[[maybe_unused]] auto& ' . $local . ' = storage.' . $local . ';';
		}
		$resume = new cpp_resume_context();
		self::visit_generator_bindings($function->body(), function (ast_local_declaration|ast_foreach_statement $local): void {
			if ($local instanceof ast_foreach_statement) {
				$prefix = 'iteration_' . $local->start . '.value.';
				if ($local->destination === $local->value) $this->generator_locals[$local->value->storage_id()] = $prefix . 'output' . ($local->source_value->type_def()->iteration()->iteration_output_type() === null ? '' : '.value');
				if ($local->key !== null) $this->generator_locals[$local->key->storage_id()] = $prefix . 'key';
			} else $this->generator_locals[$local->storage_id()] = 'local_' . $local->storage_id() . '.value';
		});
		try {
			$body = implode("\n", $this->statements($function->body(), $declarations, $globals, $headers, $function, $resume));
		} finally {
			$this->generator_locals = [];
		}
		$dispatch = "switch (state) {\ncase 0: break;\n";
		for ($state = 1; $state <= $resume->state; ++$state) $dispatch .= "case $state: goto resume_$state;\n";
		$dispatch .= "default: return false;\n}\n";
		$body = implode("\n", $aliases) . "\n" . $dispatch . $body . "close(); return false;\n";
		$factory = $this->signature($function, $headers) . ' { return ' . $name . '(' . implode(', ', $values) . "); }\n";
		return $factory . 'bool ' . $name . '::next(' . $this->generator_outputs($type) . ") {\n"
			. "if (state == ~0u) return false;\ntry {\n" . $body . "\n} catch (...) { close(); throw; }\n}\n";
	}

	/** Visit declarations in lexical order without retaining another local inventory. @param \Closure(ast_local_declaration|ast_foreach_statement):void $visit */
	private static function visit_generator_bindings(ast_statement_block $block, \Closure $visit): void
	{
		foreach ($block->statements as $statement) {
			if ($statement instanceof ast_local_declaration) $visit($statement);
			elseif ($statement instanceof ast_block_statement) self::visit_generator_bindings($statement->body, $visit);
			elseif ($statement instanceof ast_if_statement) {
				foreach ($statement->arms as $arm) self::visit_generator_bindings($arm->body, $visit);
			} elseif ($statement instanceof ast_foreach_statement) {
				$visit($statement);
				self::visit_generator_bindings($statement->body, $visit);
			} elseif ($statement instanceof ast_loop_statement) {
				if ($statement instanceof ast_for_statement) {
					foreach ($statement->initializers as $item) if ($item instanceof ast_local_declaration) $visit($item);
				}
				self::visit_generator_bindings($statement->body, $visit);
			}
		}
	}

	/** Both lexical exits and cursor close use the same idempotent local destruction. */
	private function generator_destroy(ast_local_declaration|ast_foreach_statement $local): string
	{
		$id = $local instanceof ast_foreach_statement ? 'iteration_' . $local->start : $local->storage_id();
		$slot = $local instanceof ast_foreach_statement ? $id : 'local_' . $id;
		return "if (live_$id) { live_$id = false; $slot.value.~{$slot}_type(); }";
	}

	/** Destroy scopes exited by a transfer; normal lexical exit uses this same path. */
	private function generator_cleanup(cpp_resume_context $resume, int $depth /** uint32 */): string
	{
		$text = '';
		for ($index = count($resume->scopes) - 1; $index >= $depth; --$index) {
			$scope = $resume->scopes[$index];
			if ($scope instanceof ast_foreach_statement) $text .= $this->generator_destroy($scope) . "\n";
			else foreach (array_reverse($scope->locals) as $local) $text .= $this->generator_destroy($local) . "\n";
		}
		return $text;
	}

	/** Capture the nearest shared owner or an owned temporary; named frame storage is addressed directly. */
	private function generator_iteration_capture(ast_expression $source): ?ast_expression
	{
		while ($source instanceof ast_field_expression && !($source->type_def() instanceof shared_record_type_definition)) $source = $source->receiver;
		return $source instanceof ast_name_expression && !($source->type_def() instanceof shared_record_type_definition) ? null : $source;
	}

	/** Decide output staging once from the existing conversion and method contract. */
	private function iteration_direct(ast_foreach_statement $statement): bool
	{
		return $statement->conversion === value_conversion::identity
			&& ($statement->source_value->type_def()->iteration()->iteration_method() === null || $statement->destination === $statement->value);
	}

	/** @param bool[] $headers @param type_definition[] $types */
	private function generator_iteration_layout(ast_foreach_statement $statement, array &$headers /** array<string,bool> */,
		array &$types /** list<type_definition> */): string
	{
		$source_type = $statement->source_value->type_def();
		$type = $source_type->iteration();
		$capture = $this->generator_iteration_capture($statement->source_value);
		$fields = $capture === null ? '' : $capture->type_def()->cpp_type() . ' receiver;';
		if ($type->iteration_output_type() !== null) $fields .= $type->iteration_output_type() . ' output{};';
		elseif (!$this->iteration_direct($statement) || $statement->destination === $statement->value) $fields .= $type->iteration_value_type()->cpp_type() . ' output{};';
		$key_type = $statement->key?->type_def() ?? ($type->iteration_method() !== null ? $type->iteration_key_type() : null);
		if ($key_type !== null) $fields .= $key_type->cpp_type() . ' key{};';
		$fields .= $type->lower_cpp_iteration_storage($statement->key !== null);
		foreach ([$source_type, $capture?->type_def(), $type->iteration_value_type(), $key_type] as $used) {
			if ($used === null) continue;
			foreach ($used->cpp_headers() as $header) $headers[$header] = true;
			if ($used instanceof record_type_definition || $used instanceof record_handle_definition || $used instanceof enum_type_definition || $used instanceof application_collection_type) $types[] = $used;
		}
		return 'struct iteration_' . $statement->start . '_type { ' . $fields . ' };';
	}

	/** Rebuild receiver access from its current frame; never retain a pointer into a movable cursor.
	 * @param ast_function_declaration[] $declarations
	 * @param ast_global_declaration[] $globals
	 * @param bool[] $headers
	 */
	private function generator_iteration_receiver(ast_foreach_statement $statement, string $prefix,
		array &$globals /** array<string,ast_global_declaration> */, array &$declarations /** array<string,ast_function_declaration> */,
		array &$headers /** array<string,bool> */): string
	{
		$capture = $this->generator_iteration_capture($statement->source_value);
		if ($capture === null) return $this->value($statement->source_value, $globals, $declarations, $headers);
		$fields = [];
		$source = $statement->source_value;
		while ($source !== $capture) {
			$fields[] = Cpp_Identifier::source($source->field);
			$source = $source->receiver;
		}
		$receiver = $prefix . 'receiver';
		if ($capture->type_def() instanceof shared_record_type_definition) {
			if ($fields === []) return '*' . $receiver . '.operator->()';
			$receiver .= '->' . array_pop($fields);
		}
		foreach (array_reverse($fields) as $field) $receiver .= '.' . $field;
		return $receiver;
	}

	/**
	 * Output temporaries end before the resume label; no automatic initialization is crossed.
	 * @param ast_function_declaration[] $declarations
	 * @param ast_global_declaration[] $globals
	 * @param bool[] $headers
	 */
	private function generator_yield(ast_yield_statement $statement, generator_type_definition $type, cpp_resume_context $resume,
		array &$declarations /** array<string,ast_function_declaration> */, array &$globals /** array<string,ast_global_declaration> */,
		array &$headers /** array<string,bool> */): string
	{
		$body = "{\n";
		if ($statement->key !== null) {
			$key_type = $type->iteration_key_type();
			$key = $this->value($statement->key, $globals, $declarations, $headers);
			foreach ($key_type->conversion_headers($statement->key_conversion) as $header) $headers[$header] = true;
			$body .= $key_type->cpp_type() . ' next_key = ' . $this->conversion($key_type, $statement->key_conversion, $key, $statement->key) . ";\n";
		}
		$value_type = $type->iteration_value_type();
		$value = $this->value($statement->value, $globals, $declarations, $headers);
		foreach ($value_type->conversion_headers($statement->conversion) as $header) $headers[$header] = true;
		$body .= $value_type->cpp_type() . ' next_value = ' . $this->conversion($value_type, $statement->conversion, $value, $statement->value) . ";\n";
		$body .= 'yield_value = static_cast<' . $value_type->cpp_type() . "&&>(next_value);\n";
		if ($statement->key !== null) $body .= 'yield_key = static_cast<' . $key_type->cpp_type() . "&&>(next_key);\n";
		$state = ++$resume->state;
		$body .= "state = $state; return true;\n}\nresume_$state:;\n";
		return $body;
	}
}
