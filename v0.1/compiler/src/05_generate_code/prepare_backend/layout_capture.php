<?php
declare(strict_types=1);
namespace prepare_backend;

/** Iterative storage-DAG capture and reuse. No target measurement, tools or mutable store escapes. */
final class Layout_Capture {
    private static function fields_same(array $left /** vector<\type_model\Type_Member> */, array $right /** vector<\type_model\Type_Member> */): bool {
        if (q_count($left) !== q_count($right)) { return false; }
        foreach ($left as $index => $field) { if ($field !== $right[$index]) { return false; } }
        return true;
    }
    private static function ordered(array $nodes /** hash<Layout_Dependency,int> */): array /** hash<Layout_Dependency,int> */ {
        $ids /** vector<int> */ = []; foreach ($nodes as $id => $node) { $ids[] = $id; }
        $out /** hash<Layout_Dependency,int> */ = [];
        foreach (Layout_Ids::ordered($ids) as $id) { $out[$id] = $nodes[$id]; }
        return $out;
    }
    public static function capture(\type_model\Type_Store $types, array $roots /** vector<int> */, array $known /** hash<Layout_Dependency,int> */): Layout_Input {
        $ordered_roots = Layout_Ids::ordered($roots);
        $dependencies /** hash<Layout_Dependency,int> */ = []; $active /** hash<Layout_Capture_Row,int> */ = [];
        $pending /** vector<Layout_Visit> */ = [];
        foreach ($ordered_roots as $id) { $visit = new Layout_Visit(); $visit->id = $id; $pending[] = $visit; }
        $depth = q_count($pending);
        while ($depth > 0) {
            $depth = $depth - 1; $visit = $pending[$depth]; $id = $visit->id;
            if (isset($dependencies[$id])) { continue; }
            if ($visit->ready) {
                $row = $active[$id]; $links /** hash<Layout_Dependency,int> */ = [];
                foreach ($row->children as $child) { $links[$child] = $dependencies[$child]; }
                $reuse = false;
                if (isset($known[$id])) {
                    $old = $known[$id];
                    $reuse = ($old->type_id === $id) && ($old->definition === $row->definition) && Layout_Capture::fields_same($old->fields,$row->fields)
                        && (q_count($old->children) === q_count($links));
                    if ($reuse) {
                        foreach ($links as $child => $node) {
                            if (!isset($old->children[$child])) { $reuse = false; break; }
                            if ($old->children[$child] !== $node) { $reuse = false; break; }
                        }
                    }
                }
                if ($reuse) { $dependencies[$id] = $known[$id]; }
                else { $dependencies[$id] = new Layout_Dependency($id,$row->definition,$row->fields,$links); }
                continue;
            }
            if (isset($active[$id])) { throw new \LogicException('Cyclic selected layout dependency'); }
            $definition = $types->definition_for_type($id); $shape = $definition->representation;
            $fields /** vector<\type_model\Type_Member> */ = []; $children /** vector<int> */ = [];
            if ($shape->kind() === \type_model\REPRESENTATION_STRUCTURE) {
                for ($index = 0; $index < $shape->member_count(); $index++) { $field = $types->field_for($id,$index); $fields[] = $field; $children[] = $field->type_id; }
            } elseif ($shape->kind() === \type_model\REPRESENTATION_ARRAY) { $children[] = $shape->element(); }
            $active[$id] = new Layout_Capture_Row($definition,$fields,$children);
            $finish = new Layout_Visit(); $finish->id = $id; $finish->ready = true;
            if ($depth === q_count($pending)) { $pending[] = $finish; } else { $pending[$depth] = $finish; } $depth = $depth + 1;
            for ($index = q_count($children); $index > 0; $index = $index - 1) {
                $next = new Layout_Visit(); $next->id = $children[$index - 1];
                if ($depth === q_count($pending)) { $pending[] = $next; } else { $pending[$depth] = $next; } $depth = $depth + 1;
            }
        }
        return new Layout_Input($types->lineage,$ordered_roots,Layout_Capture::ordered($dependencies));
    }
    public static function subset(Layout_Input $input, array $roots /** vector<int> */): Layout_Input {
        $nodes /** hash<Layout_Dependency,int> */ = []; $pending = $roots; $next = 0;
        while ($next < q_count($pending)) {
            $id = $pending[$next]; $next = $next + 1;
            if (isset($nodes[$id])) { continue; }
            if (!isset($input->dependencies[$id])) { throw new \LogicException('Missing selected layout dependency'); }
            $node = $input->dependencies[$id]; $nodes[$id] = $node;
            foreach ($node->children as $child) { $pending[] = $child->type_id; }
        }
        return new Layout_Input($input->lineage,$roots,Layout_Capture::ordered($nodes));
    }
    public static function dependencies_match(Layout_Dependency $left, Layout_Dependency $right): bool {
        $pending /** vector<Layout_Dependency_Pair> */ = [new Layout_Dependency_Pair($left,$right)];
        $seen /** hash<bool,int> */ = []; $next = 0;
        while ($next < q_count($pending)) {
            $pair = $pending[$next]; $next = $next + 1; $a = $pair->left; $b = $pair->right;
            if ($a === $b) { continue; }
            if ($a->type_id !== $b->type_id) { return false; }
            if (isset($seen[$a->type_id])) { continue; }
            if (($a->definition !== $b->definition) || !Layout_Capture::fields_same($a->fields,$b->fields) || (q_count($a->children) !== q_count($b->children))) { return false; }
            $seen[$a->type_id] = true;
            foreach ($a->children as $id => $child) {
                if (!isset($b->children[$id])) { return false; }
                $pending[] = new Layout_Dependency_Pair($child,$b->children[$id]);
            }
        }
        return true;
    }
    public static function current(Storage_Layout $layout, Layout_Input $input, int $id, Backend_Configuration $configuration): bool {
        if (($layout->lineage !== $input->lineage) || ($layout->configuration !== $configuration)) { return false; }
        if (!isset($input->dependencies[$id])) { return false; }
        return ($layout->definition === $input->definition_for_type($id)) && Layout_Capture::dependencies_match($layout->dependency,$input->dependencies[$id]);
    }
}
