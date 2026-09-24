<?php

/*
 * Role: lower prepared files to LLVM modules.
 * Call map: Compiler::llvm -> LLVM_Generator::generate -> to_llvm_function -> LLVM_Writer::text.
 */
namespace scpp\compiler;

final class LLVM_Generator
{
	use LLVM_Expressions;
	use LLVM_Statements;
	use LLVM_Functions;

	private llvm_prepared_file $prepared;
	private llvm_prepared_function $instance;
	private llvm_policy $policy;
	private llvm_block $block;
	private string $return_type;
	private int $next_value = 0;
	/** Initialized declaration indexes in this function. */
	private array $initialized /** hash<bool, int> */ = [];

	/** Lower each prepared source into its own module, sharing one program entry.
	 */
	public function generate(Storage $files /** Storage<llvm_prepared_file> */, llvm_policy $policy): Storage /** Storage<llvm_module> */
	{
		$this->policy = $policy;
		$outputs /** Storage<llvm_module> */ = new Storage();
		$entry_count = 0;
		foreach ($files as $prepared)
		{
			$this->prepared = $prepared;
			$module = new llvm_module();
			foreach ($prepared->struct_types as $type) {
				$fields /** vector<string> */ = [];
				foreach ($type->fields as $field) {
					$fields[] = $field->type;
				}
				$module->types[] = $type->name . ' = type { ' . implode(', ', $fields) . ' }';
			}
			$module->file_name = pathinfo($prepared->source->source->file->path, PATHINFO_FILENAME) . '.ll';
			foreach ($prepared->external_functions as $target) {
				$types /** vector<string> */ = [];
				foreach ($target->parameters as $parameter) {
					$types[] = $parameter->incoming->type;
				}
				$signature = implode(', ', $types);
				$module->external_functions[] = "declare {$target->return_type} @{$target->name}({$signature})";
			}
			foreach ($prepared->functions as $function) {
				$entry_count += (int) $function->is_entry;
				$module->functions[] = $this->to_llvm_function($function);
			}
			$module->text = (new LLVM_Writer())->text($module);
			$outputs[] = $module;
		}
		if ($entry_count !== 1) {
			throw new \RuntimeException('LLVM experiment requires exactly one file with top-level executable code; other files may contain function definitions');
		}
		return $outputs;
	}

	/** Dispatch expression kinds through the shared value and address lowering paths. */
	private function expression(ast_node $node): llvm_operand
	{
		return match ($node->kind) {
			node_kind::integer_literal => $this->to_llvm_integer_literal($node),
			node_kind::variable_reference, node_kind::index_expression, node_kind::field_expression => $this->to_llvm_variable_reference($node),
			node_kind::call_expression => $this->to_llvm_call_expression($node),
			default => throw new \RuntimeException('Unsupported LLVM expression: ' . $node->kind->name),
		};
	}

	/** Allocate a unique generated value name within the current LLVM function. */
	private function temporary(): string
	{
		return '%_Gv' . $this->next_value++;
	}

	private function emit(string $instruction): void
	{
		if ($this->block->terminated) {
			throw new \LogicException('Cannot emit after a block terminator');
		}
		$this->block->instructions[] = $instruction;
	}
}
