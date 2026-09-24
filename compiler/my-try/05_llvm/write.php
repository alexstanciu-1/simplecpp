<?php

/*
 * Role: serialize LLVM records into module text.
 * Call map: LLVM_Generator::generate -> LLVM_Writer::text.
 */
namespace scpp\compiler;

final class LLVM_Writer
{
	/** Serialize the module model; no source lookup or semantic decisions happen here. */
	public function text(llvm_module $module): string
	{
		$sections /** vector<string> */ = array_merge($module->types, $module->globals, $module->external_functions);
		foreach ($module->functions as $function)
		{
			$parameters /** vector<string> */ = [];
			foreach ($function->parameters as $parameter) {
				$parameters[] = $parameter->type . ' ' . $parameter->text;
			}
			$signature = implode(', ', $parameters);
			$lines /** vector<string> */ = ["define {$function->return_type} @{$function->name}({$signature}) {"];
			foreach ($function->blocks as $block)
			{
				if (!$block->terminated) {
					throw new \LogicException('LLVM block has no terminator');
				}
				$lines[] = $block->label . ':';
				foreach ($block->instructions as $instruction) {
					$lines[] = '    ' . $instruction;
				}
			}
			$lines[] = '}';
			$sections[] = implode("\n", $lines);
		}
		return implode("\n\n", array_merge($sections, $module->metadata)) . "\n";
	}
}
