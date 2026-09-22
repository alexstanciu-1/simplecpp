<?php
declare(strict_types=1);
namespace prepare_backend;

/** Backend storage spelling only; LLVM remains responsible for padding and stride. */
final class LLVM_Storage {
    public static function scalar(\type_model\Representation $shape): string {
        $kind = $shape->kind(); $text = '';
        if ($kind === \type_model\REPRESENTATION_VOID) { $text = 'void'; }
        elseif ($kind === \type_model\REPRESENTATION_INTEGER) { $text = 'i' . $shape->bit_width(); }
        elseif ($kind === \type_model\REPRESENTATION_FLOATING) {
            $format = $shape->floating_format();
            if ($format === 'ieee_binary16') { $text = 'half'; }
            elseif ($format === 'bfloat16') { $text = 'bfloat'; }
            elseif ($format === 'ieee_binary32') { $text = 'float'; }
            elseif ($format === 'ieee_binary64') { $text = 'double'; }
            elseif ($format === 'ieee_binary128') { $text = 'fp128'; }
            else { throw new \LogicException('Unsupported LLVM floating representation'); }
        } else { throw new \LogicException('Unsupported LLVM scalar representation'); }
        return $text;
    }

    public static function storage(\type_model\Representation $shape): string {
        if ($shape->kind() === \type_model\REPRESENTATION_OPAQUE) { return '[' . $shape->opaque_size() . ' x i8]'; }
        $text = LLVM_Storage::scalar($shape);
        if ($text === 'void') { throw new \LogicException('Void has no LLVM storage'); }
        return $text;
    }
    /** Iterative postorder avoids host/native recursion limits and shares child spelling work. */
    public static function compound(Layout_Input $input, int $root): string {
        $pending /** vector<Layout_Visit> */ = []; $first = new Layout_Visit(); $first->id = $root; $pending[] = $first;
        $depth = 1; $done /** hash<string,int> */ = []; $active /** hash<bool,int> */ = [];
        while ($depth > 0) {
            $depth = $depth - 1; $visit = $pending[$depth]; $id = $visit->id;
            if (isset($done[$id])) { continue; }
            $shape = $input->representation_for_type($id); $kind = $shape->kind();
            $children /** vector<int> */ = [];
            if ($kind === \type_model\REPRESENTATION_ARRAY) { $children[] = $shape->element(); }
            elseif ($kind === \type_model\REPRESENTATION_STRUCTURE) {
                for ($index = 0; $index < $shape->member_count(); $index++) { $children[] = $input->field_for($id,$index)->type_id; }
            }
            if (!$visit->ready) {
                if (isset($active[$id])) { throw new \LogicException('Cyclic inline storage'); }
                $active[$id] = true; $finish = new Layout_Visit(); $finish->id = $id; $finish->ready = true;
                if ($depth === q_count($pending)) { $pending[] = $finish; } else { $pending[$depth] = $finish; }
                $depth = $depth + 1;
                for ($index = q_count($children); $index > 0; $index = $index - 1) {
                    $next = new Layout_Visit(); $next->id = $children[$index - 1]; if ($depth === q_count($pending)) { $pending[] = $next; } else { $pending[$depth] = $next; }
                    $depth = $depth + 1;
                }
                continue;
            }
            $text = '';
            if ($kind === \type_model\REPRESENTATION_ARRAY) { $text = '[' . $shape->member_count() . ' x ' . $done[$children[0]] . ']'; }
            elseif ($kind === \type_model\REPRESENTATION_STRUCTURE) {
                $text = '{ '; $separator = '';
                foreach ($children as $child) { $text = $text . $separator . $done[$child]; $separator = ', '; }
                $text = $text . ' }';
            } else { $text = LLVM_Storage::storage($shape); }
            $done[$id] = $text;
        }
        return $done[$root];
    }
    /** Opaque shells need a native alignment witness; pointers are not traversed. */
    public static function requires_native(Layout_Input $input, int $root): bool {
        $pending /** vector<int> */ = [$root]; $seen /** hash<bool,int> */ = []; $cursor = 0;
        while ($cursor < q_count($pending)) {
            $id = $pending[$cursor]; $cursor = $cursor + 1;
            if (isset($seen[$id])) { continue; }
            $seen[$id] = true; $shape = $input->representation_for_type($id); $kind = $shape->kind();
            if ($kind === \type_model\REPRESENTATION_OPAQUE) { return true; }
            if ($kind === \type_model\REPRESENTATION_ARRAY) { $pending[] = $shape->element(); }
            elseif ($kind === \type_model\REPRESENTATION_STRUCTURE) {
                for ($index = 0; $index < $shape->member_count(); $index++) { $pending[] = $input->field_for($id,$index)->type_id; }
            }
        }
        return false;
    }
}
