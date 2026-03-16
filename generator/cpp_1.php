<?php

namespace simplecpp\generator;

/**
 * The cpp class is responsible for transpiling PHP Abstract Syntax Tree (AST) nodes 
 * into valid C++23 source code.
 */
class cpp
{
    const use_indent = true;

    /**
     * Entry point for transpilation. 
     * Wraps declarations and main logic into the scpp namespace.
     */
    public function compile(\ast\Node $root_node): string
    {
        // MASTER INCLUDE: Points to the library bridge header
        $includes = "#include \"_inc.h\"\n\n";
        
        $global_code = ""; 
        $main_code = "";   

        if ($root_node->kind === \ast\AST_STMT_LIST) {
            foreach ($root_node->children as $child) {
                if ($child === null) continue;

                // Function and Class declarations go to scpp global scope
                if ($child->kind === \ast\AST_FUNC_DECL || $child->kind === \ast\AST_CLASS) {
                    $global_code .= $this->generate($child, [], 1) . "\n";
                } else {
                    // Script logic goes into the internal runner function
                    $main_code .= $this->generate($child, [], 2);
                }
            }
        } else {
            $main_code = $this->generate($root_node, [], 2);
        }

        // BUILDING THE CLEAN ROOM: Everything generated is inside namespace scpp
        $final_cpp = $includes;
        $final_cpp .= "namespace scpp {\n";
        
        if ($global_code !== "") {
            $final_cpp .= $global_code . "\n";
        }
        
        // Internal entry point for the PHP script logic
        $final_cpp .= $this->indent(1) . "void run_script() {\n";
        $final_cpp .= $main_code;
        $final_cpp .= $this->indent(1) . "}\n";
        $final_cpp .= "}\n\n";

        // STANDARD GLOBAL MAIN: The OS entry point calls into our scpp namespace
        $final_cpp .= "int main() {\n";
        $final_cpp .= $this->indent(1) . "scpp::run_script();\n";
        $final_cpp .= $this->indent(1) . "return 0;\n";
        $final_cpp .= "}\n";

        return $final_cpp;
    }

    /**
     * Orchestrates the mapping of PHP types to C++, handling explicit nullability.
     */
    private function mapType($typeNode): string {
        $isNullable = false;

        // Detect PHP nullable types (e.g., ?string)
        if ($typeNode instanceof \ast\Node && $typeNode->kind === \ast\AST_NULLABLE_TYPE) {
            $isNullable = true;
            $typeNode = $typeNode->children['type']; 
        }

        $typeName = $this->getBaseTypeName($typeNode);
        $cppType = $this->lookupCppMapping($typeName);

        // Rule: Wrap in scpp::optional only if explicitly marked as nullable in PHP
        return $isNullable ? "scpp::optional<" . $cppType . ">" : $cppType;
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
        $map = [
            'int'    => 'int_t',
            'float'  => 'float_t',
            'string' => 'string',
            'bool'   => 'bool_t',
            'void'   => 'void',
        ];

        $lowerName = strtolower($typeName);
        return $map[$lowerName] ?? $typeName;
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
    public function generate($node, array $context = [], int $level = 0): string
    {
        if (!is_object($node)) {
            if (is_string($node)) return '"' . str_replace("\n", "\\n", $node) . '"';
            if (is_int($node) || is_float($node)) return (string)$node;
            if (is_bool($node)) return $node ? "true" : "false";
            return "";
        }

        switch ($node->kind) {
            case \ast\AST_FUNC_DECL:
                $name = $node->name ?? $node->children['name'] ?? 'unknown_func';
                $retType = $this->mapType($node->children['returnType'] ?? null);
                
                if (!$this->isPrimitive($retType) && !str_starts_with($retType, "scpp::optional")) {
                    $retType = "shared_p<" . $retType . ">";
                }
                
                $params = $this->generate($node->children['params'], $context, 0);
                $stmts = $this->generate($node->children['stmts'], $context, $level + 1);
                
                return $this->indent($level) . "$retType $name($params) {\n$stmts" . $this->indent($level) . "}\n";

            case \ast\AST_PARAM:
                $refFlag = \defined('ast\flags\PARAM_BY_REF') ? \ast\flags\PARAM_BY_REF : 1;
                $isPhpRef = $node->flags & $refFlag;
                $type = $this->mapType($node->children['type'] ?? null);
                $name = $node->children['name'];

                if ($isPhpRef) return $type . "& " . $name;
                if ($type === 'string') return "const string& $name";
                if ($this->isPrimitive($type)) return "$type $name";
                if (str_starts_with($type, "scpp::optional")) return "$type $name";
                return "shared_p<$type> $name";

            case \ast\AST_PARAM_LIST:
                $params = [];
                foreach ($node->children as $child) {
                    if ($child !== null) $params[] = $this->generate($child, $context, 0);
                }
                return implode(", ", $params);

            case \ast\AST_STMT_LIST:
                $code = "";
                foreach ($node->children as $child) {
                    if ($child !== null) $code .= $this->generate($child, $context, $level);
                }
                return $code;

            case \ast\AST_RETURN:
                $expr = $this->generate($node->children['expr'], $context, 0);
                return $this->indent($level) . "return $expr;\n";

            case \ast\AST_ASSIGN:
                $var = $this->generate($node->children['var'], $context, 0);
                $expr = $this->generate($node->children['expr'], $context, 0);
                return $this->indent($level) . "auto $var = $expr;\n";

            case \ast\AST_VAR:
                if ($node->children['name'] instanceof \ast\Node) throw new \Exception("Variable-variables not supported.");
                return $node->children['name'];

            case \ast\AST_ECHO:
                $expr = $this->generate($node->children['expr'], $context, 0);
                return $this->indent($level) . "std::cout << $expr;\n";

            case \ast\AST_CALL:
                $name = $this->generate($node->children['expr'], $context, 0);
                $args = $this->generate($node->children['args'], $context, 0);
                $code = "$name($args)";
                return ($level > 0) ? $this->indent($level) . $code . ";\n" : $code;

            case \ast\AST_BINARY_OP:
                $l = $this->generate($node->children['left'], $context, 0);
                $r = $this->generate($node->children['right'], $context, 0);
                $ops = [
                    \ast\flags\BINARY_ADD => '+', \ast\flags\BINARY_SUB => '-',
                    \ast\flags\BINARY_MUL => '*', \ast\flags\BINARY_DIV => '/',
                    \ast\flags\BINARY_MOD => '%', \ast\flags\BINARY_POW => 'pow',
                    \ast\flags\BINARY_IS_EQUAL => '==', \ast\flags\BINARY_IS_NOT_EQUAL => '!=',
                    \ast\flags\BINARY_IS_SMALLER => '<', \ast\flags\BINARY_IS_GREATER => '>',
                    \ast\flags\BINARY_IS_SMALLER_OR_EQUAL => '<=', \ast\flags\BINARY_IS_GREATER_OR_EQUAL => '>=',
                    \ast\flags\BINARY_BOOL_AND => '&&', \ast\flags\BINARY_BOOL_OR  => '||',
                ];
                $op = $ops[$node->flags] ?? '??';
                if ($node->flags === \ast\flags\BINARY_POW) return "pow($l, $r)";
                return "($l $op $r)";

            case \ast\AST_ASSIGN_OP:
                $var = $this->generate($node->children['var'], $context, 0);
                $expr = $this->generate($node->children['expr'], $context, 0);
                $ops = [\ast\flags\BINARY_ADD => '+=', \ast\flags\BINARY_SUB => '-=', \ast\flags\BINARY_MUL => '*=', \ast\flags\BINARY_DIV => '/='];
                return $this->indent($level) . $var . " " . ($ops[$node->flags] ?? '=') . " " . $expr . ";\n";

            case \ast\AST_UNARY_OP:
                $expr = $this->generate($node->children['expr'], $context, 0);
                $ops = [\ast\flags\UNARY_BOOL_NOT => '!', \ast\flags\UNARY_BITWISE_NOT => '~', \ast\flags\UNARY_MINUS => '-', \ast\flags\UNARY_PLUS => '+'];
                return "(" . $ops[$node->flags] . $expr . ")";

            case \ast\AST_PRE_INC: case \ast\AST_PRE_DEC: case \ast\AST_POST_INC: case \ast\AST_POST_DEC:
                $var = $this->generate($node->children['var'], $context, 0);
                $op = (strpos(\ast\get_kind_name($node->kind), 'INC') !== false) ? '++' : '--';
                return (strpos(\ast\get_kind_name($node->kind), 'PRE') !== false) ? $this->indent($level) . "$op$var;\n" : $this->indent($level) . "$var$op;\n";

            case \ast\AST_IF:
                $code = "";
                foreach ($node->children as $i => $if_elem) $code .= $this->generate($if_elem, ['index' => $i], $level);
                return $code;

            case \ast\AST_IF_ELEM:
                $cond = $node->children['cond'] ? $this->generate($node->children['cond'], $context, 0) : null;
                $stmts = $this->generate($node->children['stmts'], $context, $level + 1);
                $prefix = ($cond) ? (($context['index'] ?? 0) === 0 ? "if ($cond)" : " else if ($cond)") : " else";
                return $this->indent($level) . $prefix . " {\n$stmts" . $this->indent($level) . "}\n";

            case \ast\AST_WHILE:
                $cond = $this->generate($node->children['cond'], $context, 0);
                return $this->indent($level) . "while ($cond) {\n" . $this->generate($node->children['stmts'], $context, $level + 1) . $this->indent($level) . "}\n";

            case \ast\AST_FOR:
                $init = trim($this->generate($node->children['init'], $context, 0), "; ");
                $cond = trim($this->generate($node->children['cond'], $context, 0), "; ");
                $loop = trim($this->generate($node->children['loop'], $context, 0), "; ");
                return $this->indent($level) . "for ($init; $cond; $loop) {\n" . $this->generate($node->children['stmts'], $context, $level + 1) . $this->indent($level) . "}\n";

            case \ast\AST_EXPR_LIST:
                $exprs = [];
                foreach ($node->children as $child) if ($child) $exprs[] = $this->generate($child, $context, 0);
                return implode(", ", $exprs);

            case \ast\AST_DIM:
                return $this->generate($node->children['expr'], $context, 0) . "[" . $this->generate($node->children['dim'], $context, 0) . "]" . ($level > 0 ? ";\n" : "");

            case \ast\AST_PROP:
                return $this->generate($node->children['expr'], $context, 0) . "->" . $this->generate($node->children['prop'], $context, 0) . ($level > 0 ? ";\n" : "");

            case \ast\AST_METHOD_CALL:
                return $this->generate($node->children['expr'], $context, 0) . "->" . $this->generate($node->children['method'], $context, 0) . "(" . $this->generate($node->children['args'], $context, 0) . ")" . ($level > 0 ? ";\n" : "");

            case \ast\AST_ARG_LIST:
                $args = [];
                foreach ($node->children as $child) $args[] = $this->generate($child, $context, 0);
                return implode(", ", $args);

            case \ast\AST_NAMESPACE:
                $name = str_replace('\\', '::', $node->children['name']);
                return $this->indent($level) . "namespace $name {\n" . $this->generate($node->children['stmts'], $context, $level + 1) . $this->indent($level) . "}\n";

            case \ast\AST_NAME: return $node->children['name'];
            case \ast\AST_GROUP: return "(" . $this->generate($node->children['expr'], $context, 0) . ")";

            default:
                throw new \Exception("Unsupported AST Node: " . \ast\get_kind_name($node->kind));
        }
    }
}
