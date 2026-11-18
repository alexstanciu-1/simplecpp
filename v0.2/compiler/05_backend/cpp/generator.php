<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Concrete producer layouts and resume bodies; transient state numbering never enters prepared AST facts. */
trait Cpp_Generator
{
	/** Publish layout from signature alone. @param bool[] $headers */
	private function generator_layout(generator_type_definition $type, array &$headers /** array<string,bool> */): string
	{
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
		return <<<CPP
struct $name {
    struct arguments { $layout };
    unsigned int state = 0;
    union { arguments storage; };
    explicit $name($params) : storage{ $init } {}
    $name(const $name&) = delete;
    $name& operator=(const $name&) = delete;
    $name($name&& other) : state(other.state) {
        if (state != ~0u) {
            new (&storage) arguments(static_cast<arguments&&>(other.storage));
            other.close();
        }
    }
    ~$name() { close(); }
    void close() noexcept {
        if (state != ~0u) { state = ~0u; storage.~arguments(); }
    }
    bool next($output);
};

CPP;
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
		$body = implode("\n", $aliases) . "\nswitch (state) {\ncase 0: {\n";
		$state = 0;
		foreach ($function->body()->statements as $statement) {
			if ($statement instanceof ast_yield_statement) {
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
				$body .= 'state = ' . ++$state . "; return true;\n}\ncase $state: {\n";
			} elseif ($statement instanceof ast_return_statement) {
				$body .= "close(); return false;\n";
			} else {
				$body .= implode("\n", $this->statements($function->body(), $declarations, $globals, $headers, $function, [$statement])) . "\n";
			}
		}
		$body .= "close(); return false;\n}\ndefault: return false;\n}\n";
		$factory = $this->signature($function, $headers) . ' { return ' . $name . '(' . implode(', ', $values) . "); }\n";
		return $factory . 'bool ' . $name . '::next(' . $this->generator_outputs($type) . ") {\n"
			. "if (state == ~0u) return false;\ntry {\n" . $body . "\n} catch (...) { close(); throw; }\n}\n";
	}
}
