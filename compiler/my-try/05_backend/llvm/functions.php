<?php

/*
 * Role: function emission methods on LLVM_Function_Generator.
 * Call map: LLVM_Generator::generate -> LLVM_Function_Generator::generate -> to_llvm_block.
 */
namespace scpp\compiler;

trait LLVM_Functions
{
	/** Each function gets independent locals, temporary names and termination state. */
	public function generate(): llvm_function
	{
		$prepared = $this->instance;
		$body_scope /** scope */ = Syntax_Nodes::block_data($prepared->body)->lexical_scope();
		foreach ($prepared->locals as $local) {
			if ((object_cast(weakref_get($local->declaration->scope), scope::class) === $body_scope) && (!$local->borrowed)) {
				$this->emit($local->address . ' = alloca ' . $local->type);
			}
		}
		$function = new llvm_function();
		$parameters /** Storage<llvm_operand> */ = $function->parameters;
		$blocks /** Storage<llvm_block> */ = $function->blocks;
		foreach ($prepared->parameters as $parameter)
		{
			$local = $parameter->local;
			$incoming = $parameter->incoming;
			$operand = new llvm_operand();
			$operand->type = $incoming->type;
			$operand->text = $incoming->text;
			$parameters[] = $operand;
			if ($parameter->mode === passing_mode::value) {
				$this->emit('store ' . $local->type . ' ' . $incoming->text . ', ptr ' . $local->address);
			}
			$this->initialized[$local->declaration->local_index] = true;
		}
		$this->to_llvm_block($prepared->body);
		$function->name = $prepared->name;
		$function->return_type = $prepared->return_type;
		$function->is_entry = $prepared->is_entry;
		$blocks[] = $this->block;
		return $function;
	}
}
