<?php
declare(strict_types=1);
namespace prepare_backend;

/** Shared storage-edge traversal for layout spellings; inputs are captured canonical graphs. */
final class Layout_Order {
    public static function children(Layout_Input $input, int $id): array /** vector<int> */ {
        $shape = $input->representation_for_type($id); $kind = $shape->kind(); $children /** vector<int> */ = [];
        if ($kind === \type_model\REPRESENTATION_ARRAY) { $children[] = $shape->element(); }
        elseif ($kind === \type_model\REPRESENTATION_STRUCTURE) {
            for ($index = 0; $index < $shape->member_count(); $index++) { $children[] = $input->field_for($id,$index)->type_id; }
        }
        return $children;
    }
    /** Each reachable type once, children before parents, retaining declared child order. */
    public static function postorder(Layout_Input $input, int $root): array /** vector<int> */ {
        $pending /** vector<Layout_Visit> */ = []; $first = new Layout_Visit(); $first->id = $root; $pending[] = $first;
        $depth = 1; $done /** hash<bool,int> */ = []; $active /** hash<bool,int> */ = []; $order /** vector<int> */ = [];
        while ($depth > 0) {
            $depth = $depth - 1; $visit = $pending[$depth]; $id = $visit->id;
            if (isset($done[$id])) { continue; }
            if ($visit->ready) { $done[$id] = true; $order[] = $id; continue; }
            if (isset($active[$id])) { throw new \LogicException('Cyclic inline storage'); }
            $children = Layout_Order::children($input,$id); $active[$id] = true;
            $finish = new Layout_Visit(); $finish->id = $id; $finish->ready = true;
            if ($depth === q_count($pending)) { $pending[] = $finish; } else { $pending[$depth] = $finish; }
            $depth = $depth + 1;
            for ($index = q_count($children); $index > 0; $index = $index - 1) {
                $next = new Layout_Visit(); $next->id = $children[$index-1];
                if ($depth === q_count($pending)) { $pending[] = $next; } else { $pending[$depth] = $next; }
                $depth = $depth + 1;
            }
        }
        return $order;
    }
}
