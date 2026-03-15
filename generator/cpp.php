<?php

namsepace simplecpp\generator;

class cpp
{	
	public function generate($node, array $context = []): string
  {
		// Base Case: Handle raw scalar values (strings, ints, floats)
		// ext-ast often passes literal strings directly rather than as AST nodes
		if (!is_object($node)) {
			if (is_string($node)) {
				// Wrap strings in quotes and escape them for C++
				return '"' . addslashes($node) . '"';
			}
			// Return numbers or booleans directly as strings
			return (string) $node; 
		}

		switch ($node->kind) {
			case ast\AST_STMT_LIST:
				$code = "";
				foreach ($node->children as $child) {
					if ($child !== null) {
						$code .= $this->generate($child, $context);
					}
				}
				return $code;

			// NEW: Handle the echo statement
			case ast\AST_ECHO:
				$expr = $this->generate($node->children['expr'], $context);
				// Translate to C++ std::cout. 
				// Note: PHP's echo doesn't add a newline by default, so we don't add std::endl here.
				return "\tstd::cout << " . $expr . ";\n";

			default:
				return "/* unsupported node: " . ast\get_kind_name($node->kind) . " */\n";
		}
	}
}
