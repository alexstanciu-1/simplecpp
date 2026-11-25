<?php
declare(strict_types=1);
namespace resolve_types;
/** Source parameter boundaries; borrowing never implicitly authorizes aggregate value copying. */
final class Parameter_Contracts {
    public static function passing(\parse\Syntax_Arena $tree, int $id): int {
        $reference = (int)\parse\Syntax_Access::parameter_parts($tree,$id)->reference;
        $mode = \type_model\PASS_VALUE;
        if ($reference === 0) { $mode = \type_model\PASS_VALUE; }
        else if ($reference === \parse\SYNTAX_REFERENCE_ANNOTATION) { $mode = \type_model\PASS_BORROW_MUTABLE; }
        else if ($reference === \parse\SYNTAX_CONST_REFERENCE_ANNOTATION) { $mode = \type_model\PASS_BORROW_CONST; }
        else { throw new \LogicException('Invalid reference parameter annotation'); }
        return $mode;
    }
    public static function validate(\collect_symbols\Symbol_Record $owner, int $node, int $passing,
        \type_model\Named_Definition $definition, Annotation_Types $annotations): void {
        $shape = $definition->representation->kind();
        if (\type_model\Semantic_Modes::is_borrow($passing)) {
            $valid = ($shape === \type_model\REPRESENTATION_STRUCTURE) || ($shape === \type_model\REPRESENTATION_OPAQUE);
            if ($passing !== \type_model\PASS_BORROW_CONST) {
                $life = $definition->lifetime;
                if ($life === null) { $valid = false; }
                else {
                    $policy = $life->policy();
                    if (((int)$policy->copy !== \type_model\COPY_VALUE) || ((int)$policy->cleanup !== \type_model\CLEANUP_NONE)) { $valid = false; }
                }
            }
            if (!$valid) { $annotations->fail($owner,$node,'Source reference parameters require a plain record type or a const record borrow'); }
            return;
        }
        if ($shape === \type_model\REPRESENTATION_VOID) { $annotations->fail($owner,$node,'A parameter requires a value type; void has no value'); }
        if ($shape === \type_model\REPRESENTATION_STRUCTURE) { $annotations->fail($owner,$node,'Struct function parameters by value are unsupported'); }
        if ($shape === \type_model\REPRESENTATION_OPAQUE) { $annotations->fail($owner,$node,'Unsupported inline object parameter in a source function; value argument passing is not implemented'); }
    }
}
