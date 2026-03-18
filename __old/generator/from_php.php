<?php

namespace simplecpp\generator;

/**
 * The cpp class is responsible for S2S compiler PHP Abstract Syntax Tree (AST) nodes 
 * into valid C++23 source code.
 */
class from_php
{
    const use_indent = true;

	private ?string $currentNamespace = null;
    private bool $hasExecutableCode = false;
    private array $declared_vars = [];
	
	private ?string $executableCode = null;
	private ?string $executableCodeNamespace = null;

    /**
     * Entry point for transpilation. 
     * Wraps declarations and main logic into the scpp namespace.
     */
	public function compile(\ast\Node $root_node, string $sourceCode): string
	{
		// Reset state for a fresh compile
		$this->buildTokenMap($sourceCode);
		$this->declared_vars = [];
		$this->hasExecutableCode = false;
		$this->currentNamespace = null;
		$this->executableCodeNamespace = null;

		// 1. Header and Namespace Open
		$final_cpp = "#include \"_inc.h\"\n";
		$final_cpp .= "#include \"_php/_inc.h\"\n\n";
		$final_cpp .= "namespace scpp {\n";

		// 2. Generate Body
		// This pass will populate $this->currentNamespace if code is inside one
		$body = $this->generate($root_node, null, true, [], 1);

		$final_cpp .= $body;
		$final_cpp .= "}\n\n"; // Close scpp
		
		if ($this->executableCode !== null) {

			$run_namespace = '::scpp';

			$final_cpp .= $this->indent(0) . "namespace scpp {\n";
			$ns_offset = 0;
			if ($this->executableCodeNamespace) {
				// Entry point is namespaced: scpp::my_ns::run_script()
				$final_cpp .= $this->indent(1) . "namespace {$this->executableCodeNamespace} {\n";
				$run_namespace .= "::{$this->executableCodeNamespace}";
				$ns_offset = 1;
			}
			// Entry point is global (within scpp): scpp::run_script()
			$final_cpp .= $this->indent(1 + $ns_offset) . "int run_script() {\n";
			$final_cpp .= $this->indent(1 + $ns_offset) . $this->executableCode . "\n";
			$final_cpp .= $this->indent(1 + $ns_offset) . "	return 0;\n";
			$final_cpp .= $this->indent(1 + $ns_offset) . "}\n";
			
			if ($this->executableCodeNamespace) {
				// Entry point is namespaced: scpp::my_ns::run_script()
				$final_cpp .= $this->indent(1) . "}\n";
			}
			$final_cpp .= $this->indent(0) . "}\n";
			
			// 3. Dynamic Main Entry Point
			$final_cpp .= "int main() {\n";
			$final_cpp .= $this->indent(1) . "return {$run_namespace}::run_script();\n";
			$final_cpp .= "}\n";
		}
		
		return $final_cpp;
	}
	
	private function buildTokenMap(string $source): void {
        $raw_tokens = token_get_all($source);
        $last_line = 1;
        foreach ($raw_tokens as $tok) {
            $line = is_array($tok) ? $tok[2] : $last_line;
            $this->token_map[$line][] = $tok;
            $last_line = $line;
        }
    }
	
	private function getInlineTypeHint(int $lineno, string $varName): ?string {
		
        if (!isset($this->token_map[$lineno])) return null;

        $foundVar = false;
        foreach ($this->token_map[$lineno] as $token) {
            // Find the variable (e.g., $b)
            if (is_array($token) && $token[0] === T_VARIABLE && $token[1] === '$' . $varName) {
                $foundVar = true;
                continue;
            }

            if ($foundVar) {
                if (is_array($token) && $token[0] === T_WHITESPACE) continue;

                // Check for the comment exactly after the variable
                if (is_array($token) && ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT)) {
                    $hint = trim($token[1]);
                    // Clean comment markers
                    $hint = preg_replace('/^\/\*\*?|\*\/$/', '', $hint);
                    return trim($hint);
                }
                break; // Stop if we hit anything else (like '=')
            }
        }
        return null;
    }

    /**
     * Orchestrates the mapping of PHP types to C++, handling explicit nullability.
     */
    private function mapType($typeNode): array {
        $isNullable = false;

        // Detect PHP nullable types (e.g., ?string)
        if ($typeNode instanceof \ast\Node && $typeNode->kind === \ast\AST_NULLABLE_TYPE) {
            $isNullable = true;
            $typeNode = $typeNode->children['type']; 
        }

        $typeName = $this->getBaseTypeName($typeNode);
        $cppType = $this->lookupCppMapping($typeName);

        // Rule: Wrap in scpp::optional only if explicitly marked as nullable in PHP
        return [$isNullable ? "scpp::optional<" . $cppType . ">" : $cppType, $typeName, $typeNode, $isNullable];
    }

    /**
     * Extracts the raw PHP type name from an AST node or string.
     */
    private function getBaseTypeName($typeNode): string {
        if ($typeNode instanceof \ast\Node) {
            if (isset($typeNode->flags) && $typeNode->flags > 0) {
                $mapped = [
                    1  => 'bool',
                    4  => 'int',
                    5  => 'float',
                    6  => 'string',
                    14 => 'void',
                ][$typeNode->flags] ?? null;
                if ($mapped) return $mapped;
            }
            return $typeNode->children['name'] ?? ($typeNode->name ?? 'mixed');
        }
        return is_string($typeNode) ? $typeNode : 'mixed';
    }

    /**
     * Translates a PHP type name into its corresponding scpp library type.
     */
	private function lookupCppMapping(string $typeName): string {
        $mapping = [
            'int'    => '::scpp::int_t',
            'float'  => '::scpp::float_t',
            'bool'   => '::scpp::bool_t',
            'string' => '::scpp::string',
            'void'   => 'void',
        ];

        $lowerName = strtolower($typeName);
        return $mapping[$lowerName] ?? 'auto';
    }

    private function isPrimitive(string $type): bool {
        return in_array($type, ['int_t', 'float_t', 'bool_t', 'void', 'string']);
    }

    private function indent(int $level): string {
        return (self::use_indent && $level > 0) ? str_repeat("\t", $level) : "";
    }

    /**
     * The core recursive engine that traverses the AST and generates C++23 syntax.
     */
    public function generate($node, \ast\Node $parent = null, bool $is_on_exec_level = true, array $context = [], int $level = 0): string
    {
        if (!is_object($node)) {
            if (is_string($node)) return '"' . str_replace("\n", "\\n", $node) . '"';
            if (is_int($node) || is_float($node)) return (string)$node;
            if (is_bool($node)) return $node ? "true" : "false";
            return "";
        }

        switch ($node->kind) {
            case \ast\AST_FUNC_DECL:
            case \ast\AST_METHOD:
                $name = $node->name ?? $node->children['name'] ?? 'unknown_func';
               list($retType) = $this->mapType($node->children['returnType'] ?? null);
                
                if (!$this->isPrimitive($retType) && !str_starts_with($retType, "scpp::optional")) {
                    $retType = "shared_p<" . $retType . ">";
                }
                
                $params = $this->generate($node->children['params'], $node, false, $context, 0);
                $stmts = $this->generate($node->children['stmts'], $node, false, $context, $level + 1);
                
                return $this->indent($level) . "$retType $name($params) {\n$stmts" . $this->indent($level) . "}\n";

            case \ast\AST_PARAM:
                $refFlag = \defined('ast\flags\PARAM_BY_REF') ? \ast\flags\PARAM_BY_REF : 1;
                $isPhpRef = $node->flags & $refFlag;
				# [$isNullable ? "scpp::optional<" . $cppType . ">" : $cppType, $typeName, $typeNode, $isNullable];
                list($type) = $this->mapType($node->children['type'] ?? null);
                $name = $node->children['name'];
				
				# var_dump('$isPhpRef', $isPhpRef, $type, $node->children['type']);
				
                if ($isPhpRef) return $type . "& " . $name;
                if ($type === 'string') return "const string& $name";
				if ($this->isPrimitive($type)) return "{$type} {$name}";
                if (str_starts_with($type, "scpp::optional")) return "$type $name";
				return "shared_p<{$type}> {$name}";

            case \ast\AST_PARAM_LIST:
                $params = [];
                foreach ($node->children as $child) {
                    if ($child !== null) $params[] = $this->generate($child, $node, $is_on_exec_level, $context, 0);
                }
                return (!empty($params)) ? implode(", ", $params) : '';

           case \ast\AST_STMT_LIST:
				$topLevelDefinitions = ""; // Functions, Classes, Namespaces
				# $executableCode = "";       // Echoes, assignments, try/catch, function calls
			   $has_exec_code_already = ($this->executableCode !== null);
			   
			   foreach ($node->children as $child) {
					if ($child === null) continue;
					
					if ((!$is_on_exec_level) || ($child->kind === \ast\AST_FUNC_DECL || $child->kind === \ast\AST_CLASS))
					{
						$topLevelDefinitions .= $this->generate($child, $node, false, $context, $level);
					}
					else if ($child->kind === \ast\AST_NAMESPACE) {
						$topLevelDefinitions .= $this->generate($child, $node, $is_on_exec_level, $context, $level);
					}
					else {
						if ($has_exec_code_already) {
							throw new \Exception("Code to be executed must only be in one place.");
						}
						if ($parent->kind === \ast\AST_NAMESPACE) {
							$this->executableCodeNamespace = $this->currentNamespace;
						}
						// Otherwise, it's code that needs to be executed
						$this->executableCode .= $this->generate($child, $node, false, $context, $level + 1);
					}
				}
				
				$result = $topLevelDefinitions;
				return $result;

            case \ast\AST_RETURN:
                $expr = $this->generate($node->children['expr'], $node, $is_on_exec_level, $context, 0);
				return $this->indent($level) . "return {$expr};\n";

			case \ast\AST_ASSIGN:
				$varNode = $node->children['var'];
				$varName = $varNode->children['name'];
				$expr = $this->generate($node->children['expr'], $node, $is_on_exec_level, $context, 0);

				// Check for explicit type hint
				$hint = $this->getInlineTypeHint($node->lineno, $varName);

				// Determine the C++ declaration style
				if (!isset($this->declared_vars[$varName])) {
					$this->declared_vars[$varName] = true;
					$type = $hint ? $this->lookupCppMapping($hint) : "auto";
					
					// Handle nullable hints (e.g. string?)
					if ($hint && str_ends_with($hint, '?')) {
						$baseHint = rtrim($hint, '?');
						$type = "scpp::optional<" . $this->lookupCppMapping($baseHint) . ">";
					}
					return $this->indent($level) . "{$type} {$varName} = {$expr};\n";
				}
				return $this->indent($level) . "{$varName} = {$expr};\n";

            case \ast\AST_VAR:
                if ($node->children['name'] instanceof \ast\Node) throw new \Exception("Variable-variables not supported.");
				if (!isset($node->children['name'])) {
					var_dump('paaanic!');
					die;
				}
                return $node->children['name'];

			case \ast\AST_ECHO:
				
				$args = [];
				if ($node->_echo_skip_ ?? false) {
					# it was already processed
					return '';
				}
				else if ($parent->kind === \ast\AST_STMT_LIST) {
					# avoid echo repeating !
					$found = false;
					foreach ($parent->children as $child) {
						if ($child === $node) {
							$found = true;
						}
						if ($found) {
							if ($child->kind === \ast\AST_ECHO) {
								if ($child !== $node) {
									# we flag it that we have used it
									$child->_echo_skip_ = true;
								}
								$exprNode = $child->children['expr'];
								$args[] = $this->generate($exprNode, $child, $is_on_exec_level, $context, 0);
							}
							else {
								# no longer consecutive, we stop
								break;
							}
						}
					}
				}
				else {
					# is there another type of parent that echo may have ?
					var_dump('$parent->kind', $parent->kind, $node->kind);
					throw new \Exception('What to do in this case ?!');
				}
				
                // Join all generated arguments with a comma for a single variadic call
                return $this->indent($level) . "::scpp::echo(" . implode(", ", $args) . ");\n";

            case \ast\AST_CALL:
                $name = $this->generate($node->children['expr'], $node, $is_on_exec_level, $context, 0);
                $args = $this->generate($node->children['args'], $node, $is_on_exec_level, $context, 0);
                $code = "$name($args)";
                return ($level > 0) ? $this->indent($level) . $code . ";\n" : $code;

            case \ast\AST_BINARY_OP:
				$left = $this->generate($node->children['left'], $node, $is_on_exec_level, $context, $level);
				$right = $this->generate($node->children['right'], $node, $is_on_exec_level, $context, $level);
				$ops = [
					
                    \ast\flags\BINARY_ADD => '+', \ast\flags\BINARY_SUB => '-',
                    \ast\flags\BINARY_MUL => '*', \ast\flags\BINARY_DIV => '/',
					\ast\flags\BINARY_CONCAT => '+', // PHP '.' maps to C++ '+'
                    \ast\flags\BINARY_MOD => '%', \ast\flags\BINARY_POW => 'pow',
                    \ast\flags\BINARY_IS_EQUAL => '==', \ast\flags\BINARY_IS_NOT_EQUAL => '!=',
                    \ast\flags\BINARY_IS_SMALLER => '<', \ast\flags\BINARY_IS_GREATER => '>',
                    \ast\flags\BINARY_IS_SMALLER_OR_EQUAL => '<=', \ast\flags\BINARY_IS_GREATER_OR_EQUAL => '>=',
                    \ast\flags\BINARY_BOOL_AND => '&&', \ast\flags\BINARY_BOOL_OR  => '||',
                ];
                $op = $ops[$node->flags] ?? '??';
				if ($node->flags === \ast\flags\BINARY_CONCAT) {
					// [REQ-005] Force the left side into an scpp::string to enable 
					// overloaded concatenation and avoid pointer arithmetic errors.
					return "(::scpp::string(" . $left . ") + " . $right . ")";
				}
                else if ($node->flags === \ast\flags\BINARY_POW) {
				   return "pow({$left}, {$right})";
			    }
				return "({$left} {$op} {$right})";

            case \ast\AST_ASSIGN_OP:
                $var = $this->generate($node->children['var'], $node, $is_on_exec_level, $context, 0);
                $expr = $this->generate($node->children['expr'], $node, $is_on_exec_level, $context, 0);
                $ops = [\ast\flags\BINARY_ADD => '+=', \ast\flags\BINARY_SUB => '-=', \ast\flags\BINARY_MUL => '*=', \ast\flags\BINARY_DIV => '/='];
                return $this->indent($level) . $var . " " . ($ops[$node->flags] ?? '=') . " " . $expr . ";\n";

            case \ast\AST_UNARY_OP:
                $expr = $this->generate($node->children['expr'], $node, $is_on_exec_level, $context, 0);
                $ops = [\ast\flags\UNARY_BOOL_NOT => '!', \ast\flags\UNARY_BITWISE_NOT => '~', \ast\flags\UNARY_MINUS => '-', \ast\flags\UNARY_PLUS => '+'];
                return "(" . $ops[$node->flags] . $expr . ")";

            case \ast\AST_PRE_INC: case \ast\AST_PRE_DEC: case \ast\AST_POST_INC: case \ast\AST_POST_DEC:
                $var = $this->generate($node->children['var'], $node, $is_on_exec_level, $context, 0);
                $op = (strpos(\ast\get_kind_name($node->kind), 'INC') !== false) ? '++' : '--';
                return (strpos(\ast\get_kind_name($node->kind), 'PRE') !== false) ? $this->indent($level) . "$op$var;\n" : $this->indent($level) . "$var$op;\n";

            case \ast\AST_IF:
                $code = "";
                foreach ($node->children as $i => $if_elem) {
					$code .= $this->generate($if_elem, $node, $is_on_exec_level, ['index' => $i], $level);
				}
                return $code;

            case \ast\AST_IF_ELEM:
                $cond = isset($node->children['cond']) ? $this->generate($node->children['cond'], $node, $is_on_exec_level, $context, 0) : null;
                $stmts = $this->generate($node->children['stmts'], $node, $is_on_exec_level, $context, $level + 1);
                $prefix = isset($cond) ? (($context['index'] ?? 0) === 0 ? "if ($cond)" : " else if ($cond)") : " else";
				return $this->indent($level) . $prefix . " {\n{$stmts}" . $this->indent($level) . "}\n";

            case \ast\AST_WHILE:
                $cond = $this->generate($node->children['cond'], $node, $is_on_exec_level, $context, 0);
				return $this->indent($level) . "while ({$cond}) {\n" . $this->generate($node->children['stmts'], $node, $is_on_exec_level, $context, $level + 1) . $this->indent($level) . "}\n";

            case \ast\AST_FOR:
                $init = trim($this->generate($node->children['init'], $node, $is_on_exec_level, $context, 0), "; ");
                $cond = trim($this->generate($node->children['cond'], $node, $is_on_exec_level, $context, 0), "; ");
                $loop = trim($this->generate($node->children['loop'], $node, $is_on_exec_level, $context, 0), "; ");
				return $this->indent($level) . "for ({$init}; {$cond}; {$loop}) {\n" . $this->generate($node->children['stmts'], $node, $is_on_exec_level, $context, $level + 1) . $this->indent($level) . "}\n";

            case \ast\AST_EXPR_LIST:
                $exprs = [];
                foreach ($node->children as $child) {
					if ($child) {
						$exprs[] = $this->generate($child, $node, $is_on_exec_level, $context, 0);
					}
				}
                return implode(", ", $exprs);

            case \ast\AST_DIM:
                return $this->generate($node->children['expr'], $node, $is_on_exec_level, $context, 0) . "[" . $this->generate($node->children['dim'], $node, $is_on_exec_level, $context, 0) . "]" . ($level > 0 ? ";\n" : "");

            case \ast\AST_PROP:
                return $this->generate($node->children['expr'], $node, $is_on_exec_level, $context, 0) . "->" . $this->generate($node->children['prop'], $node, $is_on_exec_level, $context, 0) . ($level > 0 ? ";\n" : "");

            case \ast\AST_METHOD_CALL:
                return $this->generate($node->children['expr'], $node, $is_on_exec_level, $context, 0) . "->" . $this->generate($node->children['method'], $node, $is_on_exec_level, $context, 0) . "(" . $this->generate($node->children['args'], $node, $is_on_exec_level, $context, 0) . ")" . ($level > 0 ? ";\n" : "");

            case \ast\AST_ARG_LIST:
                $args = [];
                foreach ($node->children as $child) $args[] = $this->generate($child, $node, $is_on_exec_level, $context, 0);
                return implode(", ", $args);

			case \ast\AST_NAMESPACE:
                $name = $node->children['name'];
                
                // Track the active namespace for the main() caller
                $this->currentNamespace = $name;

                $code = $this->indent($level) . "namespace $name {\n";
                $code .= $this->generate($node->children['stmts'], $node, $is_on_exec_level, $context, $level + 1);
                $code .= $this->indent($level) . "}\n";
                return $code;

           case \ast\AST_NAME:
				$name = $node->children['name'];
				if (!isset($name)) {
					var_dump("paaanic #2");
					die;
				}
				// Map PHP 'null' to C++ 'nullptr'
				if (strtolower($name) === 'null') {
					return 'scpp::null';
				}
				return $name;
		
            case (\defined('ast\AST_GROUP') ? \ast\AST_GROUP : 522): 
				return "(" . $this->generate($node->children['expr'], $node, $is_on_exec_level, $context, 0) . ")";

			case \ast\AST_CONST:
				return $this->generate($node->children['name'], $node, $is_on_exec_level, $context, 0);

			case \ast\AST_CONST_DECL:
				$code = "";
				foreach ($node->children as $const) {
					$name = $const->children['name'];
					$value = $this->generate($const->children['value'], $node, $is_on_exec_level, $context, 0);
					$code .= $this->indent($level) . "static constexpr auto $name = $value;\n";
				}
				return $code;

			case \ast\flags\BINARY_CONCAT: // Node flag for the '.' operator
				return "($l + $r)";

			case \ast\AST_CAST:
				// The cast type (int, float, etc) is found in the node's flags
				$type = $node->flags; 
				$expr = $this->generate($node->children['expr'], $node, $is_on_exec_level, $context, $level);

				switch ($type) {
					// Flag 4 corresponds to TYPE_LONG (int)
					case \ast\flags\TYPE_LONG:
						return "::scpp::int_t(" . $expr . ")";

					case \ast\flags\TYPE_DOUBLE:
						return "::scpp::float_t(" . $expr . ")";

					case \ast\flags\TYPE_BOOL:
						return "::scpp::bool_t(" . $expr . ")";

					case \ast\flags\TYPE_STRING:
						return "::scpp::string(" . $expr . ")";

					default:
						throw new \Exception("Simple C++ Error: Unsupported Cast Flag: " . $type);
				}
				
			case \ast\AST_TRY:
				$out = $this->indent($level) . "try {\n";
				// Generate the 'try' body
				$out .= $this->generate($node->children['try'], $node, $is_on_exec_level, $context, $level + 1);
				$out .= $this->indent($level) . "}\n";

				// Generate the 'catch' blocks
				foreach ($node->children['catches']->children as $catch) {
					$out .= $this->generate($catch, $node, $is_on_exec_level, $context, $level);
				}
				return $out;

			case \ast\AST_CATCH:
				// We ignore the specific PHP class (e.g., Exception) and 
				// always use std::exception for binary compatibility.
				$var = $node->children['var'] ? $this->generate($node->children['var'], $node, $is_on_exec_level, $context, 0) : "e";
				$out = $this->indent($level) . "catch (const std::exception& " . $var . ") {\n";
				$out .= $this->generate($node->children['stmts'], $node, $is_on_exec_level, $context, $level + 1);
				$out .= $this->indent($level) . "}\n";
				return $out;

			case \ast\AST_THROW:
				$expr = $this->generate($node->children['expr'], $node, $is_on_exec_level, $context, 0);
				return $this->indent($level) . "throw $expr;\n";
		
			case \ast\AST_CONDITIONAL:
				$cond = $this->generate($node->children['cond'], $node, $is_on_exec_level, $context, 0);

				$hasTrue = isset($node->children['true']);
				$trueExpr = $hasTrue 
					? $this->generate($node->children['true'], $node, $is_on_exec_level, $context, 0) 
					: $cond;

				$falseExpr = $this->generate($node->children['false'], $node, $is_on_exec_level, $context, 0);

				// Adding !! forces a boolean context if C++'s template deduction gets stuck
				return "::scpp::ternary_op((bool)($cond), ($trueExpr), ($falseExpr))";

			default:
				unset($node->children);
				var_dump($node);
				throw new \Exception("Unmanaged node type : {$node->kind}");
        }
    }
}
