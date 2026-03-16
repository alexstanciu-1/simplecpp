<?php

namespace simplecpp\generator;

class cpp
{
	const use_indent = true;

	/**
	 * Takes the root AST node, separates global declarations from executable statements,
	 * and builds the complete, valid C++ file.
	 */
	public function compile(\ast\Node $root_node): string
	{
		$includes = "#include <iostream>\n";
		$includes .= "#include <cmath>\n";
		$includes .= "#include <string>\n";
		$includes .= "#include <memory>\n\n";
		$global_code = "";
		$main_code = "";

		// A standard PHP script always starts with an AST_STMT_LIST
		if ($root_node->kind === \ast\AST_STMT_LIST) {
			foreach ($root_node->children as $child) {
				if ($child === null) {
					continue;
				}

				// If it is a function or class, it belongs in the C++ global scope
				if ($child->kind === \ast\AST_FUNC_DECL || $child->kind === \ast\AST_CLASS) {
					$global_code .= $this->generate($child, [], 0) . "\n";
				} else {
					// Executable statements go inside main() at level 1
					$main_code .= $this->generate($child, [], 1);
				}
			}
		} else {
			$main_code = $this->generate($root_node, [], 1);
		}

		// Assemble the final C++ program
		$final_cpp = $includes;
		$final_cpp .= "namespace scpp {\n"; // WRAP START

		if ($global_code !== "") {
			$final_cpp .= $global_code . "\n";
		}

		// Create an entry point for main_code
		$final_cpp .= $this->indent(1) . "void run_main() {\n";
		$final_cpp .= $main_code;
		$final_cpp .= $this->indent(1) . "}\n";
		$final_cpp .= "}\n\n"; // WRAP END

		$final_cpp .= "int main() {\n";
		$final_cpp .= $this->indent(1) . "scpp::run_main();\n"; // Call into scpp
		$final_cpp .= $this->indent(1) . "return 0;\n";
		$final_cpp .= "}\n";

		return $final_cpp;
	}

	/**
	 * Map PHP types to C++ types using internal AST flags.
	 */
	private function mapType($typeNode): string {
		$typeName = null;

		if ($typeNode instanceof \ast\Node) {
			// Check if the node has flags (this is where built-in types like int/void live)
			if (isset($typeNode->flags) && $typeNode->flags > 0) {
				// Internal php-ast type mappings for version 85+
				$typeName = [
					1  => 'bool',
					4  => 'int',
					5  => 'float',
					6  => 'string',
					14 => 'void',
				][$typeNode->flags] ?? null;
			}

			// If it's a user-defined class/type, it will be in the children
			if ($typeName === null) {
				$typeName = $typeNode->children['name'] ?? ($typeNode->name ?? null);
			}
		} elseif (is_string($typeNode)) {
			$typeName = $typeNode;
		}

		if ($typeName === null) {
			throw new \Exception("Strict Typing Error: All function parameters and returns must have a type hint.");
		}
		
		$map = [
			'int'    => 'int',
			'float'  => 'double',
			'string' => 'std::string',
			'bool'   => 'bool',
			'void'   => 'void',
		];

		$lowerName = strtolower($typeName);
		return $map[$lowerName] ?? $typeName;
	}

	/**
	 * Helper to create the indentation string based on current level.
	 */
	private function indent(int $level): string {
		if (!self::use_indent || $level <= 0) {
			return "";
		}
		return str_repeat("\t", $level);
	}
	
	/**
     * Helper to check if a type is a primitive
     */
    private function isPrimitive(string $type): bool {
        return in_array($type, ['int', 'double', 'bool', 'void', 'std::string']);
    }

	public function generate($node, array $context = [], int $level = 0): string
	{
		// Base Case: Handle raw scalar values
		if (!is_object($node)) {
			if (is_string($node)) {
				$escaped = str_replace("\n", "\\n", $node);
				return '"' . $escaped . '"';
			}
			if (is_int($node) || is_float($node)) {
				return (string) $node; 
			}
			if (is_bool($node)) {
				return $node ? "true" : "false";
			}
			
			throw new \Exception("Unsupported scalar type: " . gettype($node));
		}

		switch ($node->kind) {
			
			// Inside generate() switch:
			case \ast\AST_FUNC_DECL:
				$name = $node->name ?? $node->children['name'] ?? 'unknown_func';
				
				// 1. Determine Return Type
				$baseReturnType = $this->mapType($node->children['returnType'] ?? null);
				$primitives = ['int', 'double', 'bool', 'void', 'std::string'];
				
				// Rule: Objects are returned as shared_ptr
				$isObjReturn = !in_array($baseReturnType, $primitives);
				$returnType = $isObjReturn ? "std::shared_ptr<" . $baseReturnType . ">" : $baseReturnType;
				
				// 2. Generate Params & Body
				$params = $this->generate($node->children['params'], $context, 0);
				$stmts = $this->generate($node->children['stmts'], $context, 1);

				$output = $returnType . " " . $name . "(" . $params . ") {\n";
				$output .= $stmts;
				$output .= "}\n";
				return $output;

			case \ast\AST_PARAM:
				// Rule 4: Explicit PHP Reference (&) - flag 1
				$refFlag = \defined('ast\flags\PARAM_BY_REF') ? \ast\flags\PARAM_BY_REF : 1;
				$isPhpRef = $node->flags & $refFlag;

				$baseType = $this->mapType($node->children['type'] ?? null);
				$paramName = $node->children['name'];

				if ($isPhpRef) {
					return $baseType . "& " . $paramName;
				}

				// Rule 1: Small primitives (Value)
				if (in_array($baseType, ['int', 'double', 'bool'])) {
					return $baseType . " " . $paramName;
				}

				// Rule 2: Strings (Const Ref)
				if ($baseType === 'std::string') {
					return "const " . $baseType . "& " . $paramName;
				}

				// Rule 3: Objects (shared_ptr)
				return "std::shared_ptr<" . $baseType . "> " . $paramName;

			case \ast\AST_PARAM_LIST:
				$params = [];
				foreach ($node->children as $child) {
					if ($child !== null) {
						// This will call case \ast\AST_PARAM below
						$params[] = $this->generate($child, $context, 0);
					}
				}
				return implode(", ", $params);

			case \ast\AST_RETURN:
				$expr = $this->generate($node->children['expr'], $context, 0);
				return $this->indent($level) . "return " . $expr . ";\n";

			case \ast\AST_CALL:
				$nameNode = $node->children['expr'];
				$name = $this->generate($nameNode, $context, 0);
				$args = $this->generate($node->children['args'], $context, 0);
				$code = $name . "(" . $args . ")";
				return ($level > 0) ? $this->indent($level) . $code . ";\n" : $code;
			
			case \ast\AST_STMT_LIST:
				$code = "";
				foreach ($node->children as $child) {
					if ($child !== null) {
						$code .= $this->generate($child, $context, $level);
					}
				}
				return $code;

			case \ast\AST_ASSIGN:
				$var_name = $this->generate($node->children['var'], $context, 0);
				$expr = $this->generate($node->children['expr'], $context, 0);
				return $this->indent($level) . "auto " . $var_name . " = " . $expr . ";\n";

			case \ast\AST_VAR:
				return $node->children['name'];

			case \ast\AST_ECHO:
				$expr = $this->generate($node->children['expr'], $context, 0);
				return $this->indent($level) . "std::cout << " . $expr . ";\n";

			case \ast\AST_BINARY_OP:
				$left = $this->generate($node->children['left'], $context, 0);
				$right = $this->generate($node->children['right'], $context, 0);
				
				$ops = [
					\ast\flags\BINARY_ADD => '+', \ast\flags\BINARY_SUB => '-',
					\ast\flags\BINARY_MUL => '*', \ast\flags\BINARY_DIV => '/',
					\ast\flags\BINARY_MOD => '%', \ast\flags\BINARY_POW => 'pow',
					\ast\flags\BINARY_IS_EQUAL => '==', \ast\flags\BINARY_IS_NOT_EQUAL => '!=',
					\ast\flags\BINARY_IS_SMALLER => '<', \ast\flags\BINARY_IS_GREATER => '>',
					\ast\flags\BINARY_IS_SMALLER_OR_EQUAL => '<=', \ast\flags\BINARY_IS_GREATER_OR_EQUAL => '>=',
					\ast\flags\BINARY_BOOL_AND => '&&', \ast\flags\BINARY_BOOL_OR  => '||',
					\ast\flags\BINARY_BITWISE_AND => '&', \ast\flags\BINARY_BITWISE_OR  => '|',
					\ast\flags\BINARY_BITWISE_XOR => '^', \ast\flags\BINARY_SHIFT_LEFT  => '<<',
					\ast\flags\BINARY_SHIFT_RIGHT => '>>',
				];
				
				if (!isset($ops[$node->flags])) {
					throw new \Exception("Unknown Binary Operator Flag: " . $node->flags);
				}

				$op = $ops[$node->flags];
				if ($node->flags === \ast\flags\BINARY_POW) {
					return "pow(" . $left . ", " . $right . ")";
				}
				return "(" . $left . " " . $op . " " . $right . ")";

			case \ast\AST_ASSIGN_OP:
				$var = $this->generate($node->children['var'], $context, 0);
				$expr = $this->generate($node->children['expr'], $context, 0);
				
				$ops = [
					\ast\flags\BINARY_ADD => '+=', \ast\flags\BINARY_SUB => '-=',
					\ast\flags\BINARY_MUL => '*=', \ast\flags\BINARY_DIV => '/=',
					\ast\flags\BINARY_MOD => '%=', \ast\flags\BINARY_BITWISE_AND => '&=',
					\ast\flags\BINARY_BITWISE_OR  => '|=', \ast\flags\BINARY_BITWISE_XOR => '^=',
					\ast\flags\BINARY_SHIFT_LEFT  => '<<=', \ast\flags\BINARY_SHIFT_RIGHT => '>>=',
				];
				
				if (!isset($ops[$node->flags])) {
					throw new \Exception("Unknown Assignment Operator Flag: " . $node->flags);
				}

				return $this->indent($level) . $var . " " . $ops[$node->flags] . " " . $expr . ";\n";

			case \ast\AST_UNARY_OP:
				$expr = $this->generate($node->children['expr'], $context, 0);
				$ops = [
					\ast\flags\UNARY_BOOL_NOT => '!', \ast\flags\UNARY_BITWISE_NOT => '~',
					\ast\flags\UNARY_MINUS => '-', \ast\flags\UNARY_PLUS => '+',
				];
				
				if (!isset($ops[$node->flags])) {
					throw new \Exception("Unknown Unary Operator Flag: " . $node->flags);
				}

				return "(" . $ops[$node->flags] . $expr . ")";

			case \ast\AST_PRE_INC:
			case \ast\AST_PRE_DEC:
			case \ast\AST_POST_INC:
			case \ast\AST_POST_DEC:
				$var = $this->generate($node->children['var'], $context, 0);
				$kind_name = \ast\get_kind_name($node->kind);
				$op = (strpos($kind_name, 'INC') !== false) ? '++' : '--';
				
				if (strpos($kind_name, 'PRE') !== false) {
					return $this->indent($level) . $op . $var . ";\n";
				} else {
					return $this->indent($level) . $var . $op . ";\n";
				}

			case \ast\AST_IF:
				$code = "";
				foreach ($node->children as $i => $if_elem) {
					$code .= $this->generate($if_elem, ['index' => $i], $level);
				}
				return $code;

			case \ast\AST_IF_ELEM:
				$condition_node = $node->children['cond'];
				$statements_node = $node->children['stmts'];
				$index = $context['index'] ?? 0;

				$output = "";

				if ($condition_node !== null) {
					$condition_code = $this->generate($condition_node, $context, 0);
					$prefix = ($index === 0) ? $this->indent($level) . "if (" : " else if (";
					$output .= $prefix . $condition_code . ") {\n";
				} else {
					$output .= " else {\n";
				}

				// Generate the body with increased indentation level
				$output .= $this->generate($statements_node, $context, $level + 1);
				$output .= $this->indent($level) . "}\n";

				return $output;

			case \ast\AST_WHILE:
				$cond = $this->generate($node->children['cond'], $context, 0);
				$stmts = $node->children['stmts'];

				$output = $this->indent($level) . "while (" . $cond . ") {\n";
				$output .= $this->generate($stmts, $context, $level + 1);
				$output .= $this->indent($level) . "}\n";

				return $output;

			case \ast\AST_FOR:
				$init = $this->generate($node->children['init'], $context, 0);
				$cond = $this->generate($node->children['cond'], $context, 0);
				$loop = $this->generate($node->children['loop'], $context, 0);
				$stmts = $node->children['stmts'];

				// C++ for loops use semicolons to separate parts. 
				// PHP AST stores these as lists, so we trim trailing semicolons/commas if they exist.
				$output = $this->indent($level) . "for (" . trim($init, "; \t\n") . "; " . trim($cond, "; \t\n") . "; " . trim($loop, "; \t\n") . ") {\n";
				$output .= $this->generate($stmts, $context, $level + 1);
				$output .= $this->indent($level) . "}\n";

				return $output;

			case \ast\AST_EXPR_LIST:
				$expressions = [];
				foreach ($node->children as $child) {
					if ($child !== null) {
						// Pass level 0 to ensure no tabs are added inside the list
						$expressions[] = $this->generate($child, $context, 0);
					}
				}
				// Join with commas for C++ compatibility (e.g., for multiple initializers in for loops)
				return implode(", ", $expressions);

			case \ast\AST_VAR:
				if ($node->children['name'] instanceof \ast\Node) {
					throw new \Exception("Variable-variables ($$var) are not supported.");
				}
				return $node->children['name'];

			// $var["info"]
			case \ast\AST_DIM:
				$expr = $this->generate($node->children['expr'], $context, 0);
				$dim = $this->generate($node->children['dim'], $context, 0);
				$code = $expr . "[" . $dim . "]";
				return ($level > 0) ? $this->indent($level) . $code . ";\n" : $code;

			// $var->path
			case \ast\AST_PROP:
				$expr = $this->generate($node->children['expr'], $context, 0);
				$prop = $node->children['prop'];
				if ($prop instanceof \ast\Node) {
					$prop = $this->generate($prop, $context, 0);
				}
				$code = $expr . "->" . $prop;
				return ($level > 0) ? $this->indent($level) . $code . ";\n" : $code;

			// $var->a_call()
			case \ast\AST_METHOD_CALL:
				$expr = $this->generate($node->children['expr'], $context, 0);
				$method = $node->children['method'];
				if ($method instanceof \ast\Node) {
					$method = $this->generate($method, $context, 0);
				}
				$args = $this->generate($node->children['args'], $context, 0);
				$code = $expr . "->" . $method . "(" . $args . ")";
				return ($level > 0) ? $this->indent($level) . $code . ";\n" : $code;

			// Support for function/method arguments
			case \ast\AST_ARG_LIST:
				$args = [];
				foreach ($node->children as $child) {
					$args[] = $this->generate($child, $context, 0);
				}
				return implode(", ", $args);

			// Grouping (round brackets) - internal value 522
			case 522:
			case (\defined('ast\AST_GROUP') ? \ast\AST_GROUP : -1):
				$expr = $this->generate($node->children['expr'], $context, 0);
				return "(" . $expr . ")";

			case \ast\AST_NAME:
				return $node->children['name'];

			case \ast\AST_NAMESPACE:
				$name = str_replace('\\', '::', $node->children['name']);
				$stmts = $this->generate($node->children['stmts'], $context, $level + 1);
				return "namespace " . $name . " {\n" . $stmts . "}\n";

			default:
				throw new \Exception("Unsupported AST Node: " . \ast\get_kind_name($node->kind));
		}
	}
}
