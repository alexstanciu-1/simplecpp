<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Definite initialization over reachable checked blocks. No cleanup or allocation analysis. */
final class Local_Flow {
    public static function entries(\check_bodies\Checked_Body $body): Initialization_Entries {
        $parameters /** vector<Initialization_Fact> */ = [];
        for ($id = 1; $id < $body->entry_parameter_count()+1; $id++) { $parameters[] = Initialization_Rows::make($id,0); }
        $entries /** hash<Initialization_State,int> */ = []; $entries[1] = new Initialization_State($parameters);
        $pending /** vector<int> */ = [1]; $depth = 1;
        $queued /** hash<bool,int> */ = []; $queued[1] = true;
        while ($depth > 0) {
            $depth = $depth - 1; $id = $pending[$depth]; unset($queued[$id]);
            $block = $body->block_at($id-1); $state = $entries[$id]->facts(); $scope = $block->scope_id;
            $positions /** hash<int,int> */ = [];
            for ($i = 0; $i < q_count($state); $i++) { $local = (int)$state[$i]->local_id; $positions[$local] = $i; }
            for ($index = $block->statement_start; $index < $block->statement_start+$block->statement_count; $index++) {
                $statement = $body->statement_at($index);
                if (!Local_Flow::contains($body,$scope,$statement->scope_id)) {
                    $state = Local_Flow::retain($body,$state,$statement->scope_id); $positions = [];
                    for ($i = 0; $i < q_count($state); $i++) { $local = (int)$state[$i]->local_id; $positions[$local] = $i; }
                }
                $scope = $statement->scope_id;
                if ($statement->kind === \check_bodies\STATEMENT_LOCAL_DECLARATION) {
                    $target = $statement->target;
                    if ($target === null) { throw new \LogicException('Declaration has no checked local target'); }
                    $local = $target->local_id; $row = Initialization_Rows::make($local,$index+1);
                    if (isset($positions[$local])) { $state[$positions[$local]] = $row; }
                    else { $positions[$local] = q_count($state); $state[] = $row; }
                }
            }
            foreach (\check_bodies\Flow_Graph::successors($block) as $target) {
                $next = new Initialization_State(Local_Flow::retain($body,$state,$body->block_at($target-1)->scope_id));
                $changed = true; $merged = $next;
                if (isset($entries[$target])) {
                    $merged = $entries[$target]->intersect($next);
                    // Intersection only removes keys and retains previous initialization values/order.
                    $changed = $merged->size() !== $entries[$target]->size();
                }
                if ($changed) {
                    $entries[$target] = $merged;
                    if (!isset($queued[$target])) {
                        $queued[$target] = true;
                        if ($depth === q_count($pending)) { $pending[] = $target; } else { $pending[$depth] = $target; }
                        $depth++;
                    }
                }
            }
        }
        return new Initialization_Entries($body,$entries);
    }
    public static function contains(\check_bodies\Checked_Body $body, int $outer, int $inner): bool {
        for ($id = $inner; $id !== 0; $id = (int)$body->names->scope_for($id)->parent_scope_id) {
            if ($id === $outer) { return true; }
        }
        return false;
    }
    public static function retain(\check_bodies\Checked_Body $body, array $state /** vector<Initialization_Fact> */, int $scope): array /** vector<Initialization_Fact> */ {
        $retained /** vector<Initialization_Fact> */ = [];
        foreach ($state as $row) {
            if (Local_Flow::contains($body,(int)$body->names->local_for((int)$row->local_id)->scope_id,$scope)) { $retained[] = Initialization_Rows::copy($row); }
        }
        return $retained;
    }
}
