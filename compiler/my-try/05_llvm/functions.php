<?php

/*
 * Role: function emission methods on LLVM_Generator.
 * Call map: LLVM_Generator::generate -> to_llvm_function -> to_llvm_block.
 */
namespace scpp\compiler;

trait LLVM_Functions
{
	/** Each function gets independent locals, temporary names and termination state. */
	private function to_llvm_function(llvm_prepared_function $prepared): llvm_function
	{
		$this->instance = $prepared;
		$this->next_value = 0;
		$this->initialized = [];
		$this->return_type = $prepared->return_type;
		$this->block = new llvm_block();
		$this->block->label = '_Gb0';
		foreach ($prepared->locals as $local) {
			if (($local->declaration->scope === $prepared->body->specialization->scope) && !$local->borrowed) {
				$this->emit("{$local->address} = alloca {$local->type}");
			}
		}
		$function = new llvm_function();
		foreach ($prepared->parameters as $parameter)
		{
			$local = $parameter->local;
			$incoming = $parameter->incoming;
			$operand = new llvm_operand();
			$operand->type = $incoming->type;
			$operand->text = $incoming->text;
			$function->parameters[] = $operand;
			if ($parameter->mode === passing_mode::value) {
				$this->emit("store {$local->type} {$incoming->text}, ptr {$local->address}");
			}
			$this->initialized[$local->declaration->local_index] = true;
		}
		$this->to_llvm_block($prepared->body);
		$function->name = $prepared->name;
		$function->return_type = $prepared->return_type;
		$function->is_entry = $prepared->is_entry;
		$function->blocks[] = $this->block;
		return $function;
	}
}
