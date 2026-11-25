<?php
declare(strict_types=1);
namespace prepare_backend;

/** Layout-only C++ shells: no constructed objects, source behavior or lifecycle operations. */
final class Native_Layout {
    public static function source(Layout_Input $input, int $root): Layout_Witness {
        $text = ''; $opaque = false; $primitives /** hash<string,int> */ = [];
        foreach (Layout_Order::postorder($input,$root) as $id) {
            $shape = $input->representation_for_type($id); $kind = $shape->kind(); $name = 't' . $id;
            if ($kind === \type_model\REPRESENTATION_INTEGER) {
                $primitives[$id] = LLVM_Storage::scalar($shape);
                $text = $text . 'using ' . $name . ' = unsigned _BitInt(' . $shape->bit_width() . ");\n";
            } elseif ($kind === \type_model\REPRESENTATION_OPAQUE) {
                $opaque = true;
                $text = $text . 'struct alignas(' . $shape->opaque_alignment() . ') ' . $name
                    . ' { unsigned char bytes[' . $shape->opaque_size() . "]; };\n";
            } elseif ($kind === \type_model\REPRESENTATION_ARRAY) {
                $text = $text . 'using ' . $name . ' = t' . $shape->element() . '[' . $shape->member_count() . "];\n";
            } elseif ($kind === \type_model\REPRESENTATION_STRUCTURE) {
                $text = $text . 'struct ' . $name . " {\n";
                foreach (Layout_Order::children($input,$id) as $index => $child) { $text = $text . 't' . $child . ' f' . $index . ";\n"; }
                $text = $text . "};\n";
            } else { throw new \LogicException('Unsupported inline layout constituent'); }
        }
        if (!$opaque) { return new Layout_Witness('',$primitives); }
        $facts /** hash<string> */ = [];
        $facts['size'] = 'sizeof(t' . $root . ')'; $facts['alignment'] = 'alignof(t' . $root . ')';
        for ($index = 0; $index < q_count($input->fields_for($root)); $index++) {
            $facts['field_' . $index] = '__builtin_offsetof(t' . $root . ', f' . $index . ')';
        }
        foreach ($primitives as $id => $type) {
            $facts['primitive_size_' . $id] = 'sizeof(t' . $id . ')';
            $facts['primitive_alignment_' . $id] = 'alignof(t' . $id . ')';
        }
        foreach ($facts as $name => $value) { $text = $text . 'extern "C" const unsigned long long ' . $name . ' = ' . $value . ";\n"; }
        return new Layout_Witness($text,$primitives);
    }
}
