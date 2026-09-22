<?php
declare(strict_types=1);

/*
 * Role: Describe aligned inline storage to Clang without implementing source behavior.
 * Call map: Layout_Preparation::prepare() -> Native_Layout::source()
 * Output: layout-only C++ types and constants; no constructed objects or special members.
 */
namespace prepare_backend;

use type_model\representation_kind;

final class Native_Layout
{
    /** Select native alignment measurement only when the storage graph contains opaque fields. */
    public static function required(Layout_Input $types, int $root): bool
    {
        $pending = [$root];
        $seen = [];
        while ($pending !== [])
        {
            $id = array_pop($pending);
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $shape = $types->representation_for_type($id);
            if ($shape->kind === representation_kind::opaque_inline) {
                return true;
            }
            if ($shape->kind === representation_kind::fixed_array) {
                $pending[] = $shape->payload->element_type;
            }
            elseif ($shape->kind === representation_kind::structure) {
                for ($i = 0; $i < $shape->payload->count; ++$i) {
                    $pending[] = $types->field_for($id, $i)->type_id;
                }
            }
        }
        return false;
    }

    /** Traverse dependency types once; emit children before their by-value containing types. */
    public static function source(Layout_Input $types, int $root, array &$primitives = []): ?string
    {
        $pending = [[$root, false]];
        $done = [];
        $text = '';
        $opaque = false;
        while ($pending !== [])
        {
            [$id, $ready] = array_pop($pending);
            if (isset($done[$id])) {
                continue;
            }
            $shape = $types->representation_for_type($id);
            $children = [];
            if ($shape->kind === representation_kind::structure) {
                for ($i = 0; $i < $shape->payload->count; ++$i) {
                    $children[] = $types->field_for($id, $i)->type_id;
                }
            }
            elseif ($shape->kind === representation_kind::fixed_array) {
                $children[] = $shape->payload->element_type;
            }
            // Revisit the parent only after its child witnesses have been declared.
            if (!$ready) {
                $pending[] = [$id, true];
                foreach (array_reverse($children) as $child) {
                    $pending[] = [$child, false];
                }
                continue;
            }
            $name = 't' . $id;
            switch ($shape->kind)
            {
                case representation_kind::integer:
                    $primitives[$id] = LLVM_Types::scalar($shape);
                    $text .= 'using ' . $name . ' = unsigned _BitInt(' . $shape->payload->bit_width . ");\n";
                    break;
                case representation_kind::opaque_inline:
                    // This shell carries storage facts only; it never owns native or source behavior.
                    $opaque = true;
                    $text .= 'struct alignas(' . $shape->payload->alignment_bytes . ') ' . $name
                        . ' { unsigned char bytes[' . $shape->payload->size_bytes . "]; };\n";
                    break;
                case representation_kind::fixed_array:
                    $text .= 'using ' . $name . ' = t' . $children[0] . '[' . $shape->payload->count . "];\n";
                    break;
                case representation_kind::structure:
                    $text .= 'struct ' . $name . " {\n";
                    foreach ($children as $index => $child) {
                        $text .= 't' . $child . ' f' . $index . ";\n";
                    }
                    $text .= "};\n";
                    break;
                default:
                    throw new \LogicException('Unsupported inline layout constituent');
            }
            $done[$id] = true;
        }
        if (!$opaque) {
            return null;
        }
        $facts = ['size' => 'sizeof(t' . $root . ')', 'alignment' => 'alignof(t' . $root . ')'];
        for ($i = 0; $i < count($types->fields_for($root)); ++$i) {
            $facts['field_' . $i] = '__builtin_offsetof(t' . $root . ', f' . $i . ')';
        }
        foreach ($primitives as $id => $_) {
            $facts['primitive_size_' . $id] = 'sizeof(t' . $id . ')';
            $facts['primitive_alignment_' . $id] = 'alignof(t' . $id . ')';
        }
        foreach ($facts as $name => $value) {
            $text .= 'extern "C" const unsigned long long ' . $name . ' = ' . $value . ";\n";
        }
        return $text;
    }
}
