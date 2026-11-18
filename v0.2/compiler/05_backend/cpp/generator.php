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

	/** Application types needed by the signature and source-owned cursor layout. @return \Generator<type_definition> */
	public function application_types(ast_function_declaration $function): \Generator
	{
		yield from self::signature_application_types($function);
		if ($function->return_type_def() instanceof generator_type_definition) {
			$plan = $this->generator_plan($function->return_type_def());
			yield from $plan->types;
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
		foreach ($this->generator_bindings($type->function->body()) as $local) {
			$local_type = $local->type_def();
			$id = $local->storage_id();
			$native = $local_type->cpp_type();
			$slot = 'local_' . $id;
			$slots[] = "using {$slot}_type = $native; struct {$slot}_slot { union { {$slot}_type value; }; {$slot}_slot() {} ~{$slot}_slot() {} }; {$slot}_slot $slot;";
			$flags[] = "bool live_$id = false;";
			$cleanup[] = $this->generator_destroy($local);
			$moves[] = "if (other.live_$id) { new (&$slot.value) {$slot}_type(static_cast<{$slot}_type&&>(other.$slot.value)); live_$id = true; }";
			foreach ($local_type->cpp_headers() as $header) $headers[$header] = true;
			if ($local_type instanceof struct_type_definition || $local_type instanceof struct_handle_definition || $local_type instanceof enum_type_definition) $types[] = $local_type;
		}
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
		$state = 0;
		foreach ($this->generator_bindings($function->body()) as $local) {
			$this->generator_locals[$local->storage_id()] = 'local_' . $local->storage_id() . '.value';
		}
		try {
			$body = $this->generator_sequence($function->body(), $type, $state, $declarations, $globals, $headers);
		} finally {
			$this->generator_locals = [];
		}
		$dispatch = "switch (state) {\ncase 0: break;\n";
		for ($resume = 1; $resume <= $state; ++$resume) $dispatch .= "case $resume: goto resume_$resume;\n";
		$dispatch .= "default: return false;\n}\n";
		$body = implode("\n", $aliases) . "\n" . $dispatch . $body . "close(); return false;\n";
		$factory = $this->signature($function, $headers) . ' { return ' . $name . '(' . implode(', ', $values) . "); }\n";
		return $factory . 'bool ' . $name . '::next(' . $this->generator_outputs($type) . ") {\n"
			. "if (state == ~0u) return false;\ntry {\n" . $body . "\n} catch (...) { close(); throw; }\n}\n";
	}

	/** Visit declarations in lexical order without retaining another local inventory. @return \Generator<ast_local_declaration> */
	private function generator_bindings(ast_statement_block $block): \Generator
	{
		foreach ($block->statements as $statement) {
			if ($statement instanceof ast_local_declaration) yield $statement;
			elseif ($statement instanceof ast_if_statement) {
				foreach ($statement->arms as $arm) yield from $this->generator_bindings($arm->body);
			}
		}
	}

	/** Both lexical exits and cursor close use the same idempotent local destruction. */
	private function generator_destroy(ast_local_declaration $local): string
	{
		$id = $local->storage_id();
		return "if (live_$id) { live_$id = false; local_$id.value.~local_{$id}_type(); }";
	}

	/**
	 * Native blocks preserve branching; resume labels skip already-executed conditions and declarations.
	 * Every ordinary statement owns its native temporaries, so dispatch never jumps over an active initializer.
	 * @param ast_function_declaration[] $declarations
	 * @param ast_global_declaration[] $globals
	 * @param bool[] $headers
	 */
	private function generator_sequence(ast_statement_block $block, generator_type_definition $type, int &$state /** uint32 */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$globals /** array<string,ast_global_declaration> */,
		array &$headers /** array<string,bool> */): string
	{
		$body = '';
		foreach ($block->statements as $statement) {
			if ($statement instanceof ast_if_statement) {
				foreach ($statement->arms as $index => $arm) {
					$head = $index === 0 ? 'if' : 'else';
					if ($arm->condition !== null) {
						$head = ($index === 0 ? 'if' : 'else if') . ' ('
							. $this->condition($arm->condition, $globals, $declarations, $headers) . ')';
					}
					$body .= $head . " {\n" . $this->generator_sequence($arm->body, $type, $state, $declarations, $globals, $headers) . "}\n";
				}
			} elseif ($statement instanceof ast_yield_statement) {
				$body .= "{\n";
				if ($statement->key !== null) {
					$key_type = $type->iteration_key_type();
					$key = $this->value($statement->key, $globals, $declarations, $headers);
					foreach ($key_type->conversion_headers($statement->key_conversion) as $header) $headers[$header] = true;
					$body .= $key_type->cpp_type() . ' next_key = ' . $key_type->lower_cpp_conversion($statement->key_conversion, $key, $statement->key) . ";\n";
				}
				$value_type = $type->iteration_value_type();
				$value = $this->value($statement->value, $globals, $declarations, $headers);
				foreach ($value_type->conversion_headers($statement->conversion) as $header) $headers[$header] = true;
				$body .= $value_type->cpp_type() . ' next_value = ' . $value_type->lower_cpp_conversion($statement->conversion, $value, $statement->value) . ";\n";
				$body .= 'yield_value = static_cast<' . $value_type->cpp_type() . "&&>(next_value);\n";
				if ($statement->key !== null) $body .= 'yield_key = static_cast<' . $key_type->cpp_type() . "&&>(next_key);\n";
				++$state;
				$body .= "state = $state; return true;\n}\nresume_$state:;\n";
			} elseif ($statement instanceof ast_return_statement) {
				$body .= "close(); return false;\n";
			} else {
				$body .= "{\n" . implode("\n", $this->statements($block, $declarations, $globals, $headers, $type->function, [$statement])) . "\n}\n";
			}
		}
		foreach (array_reverse($block->locals) as $local) $body .= $this->generator_destroy($local) . "\n";
		return $body;
	}

}
