<?php
declare(strict_types=1);
namespace check_bodies;
/** Read-only graph queries; no name, lifetime or target decisions. */
final class Flow_Graph {
    public static function successors(Typed_Block $block): array /** vector<int> */ {
        $out /** vector<int> */ = [];
        if (($block->end === \check_bodies\FLOW_JUMP) || ($block->end === \check_bodies\FLOW_BRANCH)) { $out[] = $block->first; }
        if ($block->end === \check_bodies\FLOW_BRANCH) { $out[] = $block->second; }
        return $out;
    }
    public static function reachable(array $blocks /** vector<Typed_Block> */): array /** vector<int> */ {
        $size = q_count($blocks);
        if ($size === 0) { throw new \LogicException('Missing checked flow block'); }
        $seen /** vector<bool> */ = [];
        foreach ($blocks as $block) { $seen[] = false; }
        $ids /** vector<int> */ = [1];
        $seen[0] = true;
        for ($cursor = 0; $cursor < q_count($ids); $cursor++) {
            $id = $ids[$cursor];
            foreach (Flow_Graph::successors($blocks[$id - 1]) as $target) {
                if (($target < 1) || ($target > $size)) { throw new \LogicException('Missing checked flow block'); }
                if (!$seen[$target - 1]) { $seen[$target - 1] = true; $ids[] = $target; }
            }
        }
        // Bottom-up merge sort preserves O(n log n) without PHP callback sorting.
        $scratch /** vector<int> */ = [];
        foreach ($ids as $id) { $scratch[] = $id; }
        $count = q_count($ids);
        for ($width = 1; $width < $count; $width = $width * 2) {
            for ($start = 0; $start < $count; $start = $start + $width * 2) {
                $middle = $start + $width;
                if ($middle > $count) { $middle = $count; }
                $end = $middle + $width;
                if ($end > $count) { $end = $count; }
                $left = $start; $right = $middle;
                for ($position = $start; $position < $end; $position++) {
                    $use_left = false;
                    if ($left < $middle) {
                        if ($right === $end) { $use_left = true; }
                        else {
                            $a = $blocks[$ids[$left] - 1]->statement_start;
                            $b = $blocks[$ids[$right] - 1]->statement_start;
                            $use_left = $a < $b;
                            if ($a === $b) { $use_left = $ids[$left] < $ids[$right]; }
                        }
                    }
                    if ($use_left) { $scratch[$position] = $ids[$left]; $left++; }
                    else { $scratch[$position] = $ids[$right]; $right++; }
                }
            }
            for ($i = 0; $i < $count; $i++) { $ids[$i] = $scratch[$i]; }
        }
        return $ids;
    }
}
