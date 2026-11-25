<?php

/*
 * Role: lower prepared files to LLVM modules.
 * Call map: Compiler::llvm -> LLVM_Generator::generate -> LLVM_Function_Generator::generate -> LLVM_Writer::text.
 */
namespace scpp\compiler;

final class LLVM_Generator
{
	/** Lower each prepared source into its own module, sharing one program entry.
	 */
	public function generate(Storage $files /** Storage<llvm_prepared_file> */, llvm_policy $policy): Storage /** Storage<llvm_module> */
	{
		$outputs /** Storage<llvm_module> */ = new Storage();
		$entry_count = 0;
		foreach ($files as $prepared)
		{
			$module = new llvm_module();
			$functions /** Storage<llvm_function> */ = $module->functions;
			foreach ($prepared->struct_types as $type) {
				$fields /** vector<string> */ = [];
				foreach ($type->fields as $field) {
					$fields[] = $field->type;
				}
				$module->types[] = $type->name . ' = type { ' . LLVM_Text::join($fields, ', ') . ' }';
			}
			$module->file_name = LLVM_Text::output_name($prepared->source->source_file()->path);
			foreach ($prepared->external_functions as $target)
			{
				$types /** vector<string> */ = [];
				foreach ($target->parameters as $parameter) {
					$types[] = $parameter->incoming->type;
				}
				$signature = LLVM_Text::join($types, ', ');
				$module->external_functions[] = ("declare " . $target->return_type . " @" . $target->name . "(" . $signature . ")");
			}
			foreach ($prepared->functions as $function) {
				$entry_count = $entry_count + (int) $function->is_entry;
				$functions[] = (new LLVM_Function_Generator($function, $policy))->generate();
			}
			$module->text = (new LLVM_Writer())->text($module);
			$outputs[] = $module;
		}
		if ($entry_count !== 1) {
			throw new \RuntimeException('LLVM experiment requires exactly one file with top-level executable code; other files may contain function definitions');
		}
		return $outputs;
	}
}

/** One function emission owns its block, local initialization and temporary names. */
final class LLVM_Function_Generator
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

	/** Capture one function and establish its initial block before emitting instructions. */
	public function __construct(llvm_prepared_function $function, llvm_policy $policy)
	{
		$this->instance = $function;
		$this->prepared = $function->file;
		$this->policy = $policy;
		$this->return_type = $function->return_type;
		$this->block = new llvm_block();
		$this->block->label = '_Gb0';
	}

	/** Dispatch expression kinds through the shared value and address lowering paths. */
	private function expression(ast_node $node): llvm_operand
	{
		if ($node->kind() === node_kind::integer_literal) {
			return $this->to_llvm_integer_literal($node);
		}
		if (($node->kind() === node_kind::variable_reference) || ($node->kind() === node_kind::index_expression) || ($node->kind() === node_kind::field_expression)) {
			return $this->to_llvm_variable_reference($node);
		}
		if ($node->kind() === node_kind::call_expression) {
			return $this->to_llvm_call_expression($node);
		}
		throw new \RuntimeException('Unsupported LLVM expression: ' . Node_Kind_Name::text($node->kind()));
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
