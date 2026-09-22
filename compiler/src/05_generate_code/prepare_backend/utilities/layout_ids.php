<?php
declare(strict_types=1);
namespace prepare_backend;

/** Deterministic numeric membership order without scanning unrelated canonical IDs. */
final class Layout_Ids {
    public static function ordered(array $ids /** vector<int> */): array /** vector<int> */ {
        $sorted = $ids; $count = q_count($sorted); $width = 1;
        while ($width < $count) {
            $merged /** vector<int> */ = []; $start = 0;
            while ($start < $count) {
                $middle = $start + $width; if ($middle > $count) { $middle = $count; }
                $end = $middle + $width; if ($end > $count) { $end = $count; }
                $left = $start; $right = $middle;
                while (($left < $middle) || ($right < $end)) {
                    $from_left = false;
                    if ($right === $end) { $from_left = true; }
                    elseif ($left < $middle) { $from_left = $sorted[$left] < $sorted[$right]; }
                    if ($from_left) { $merged[] = $sorted[$left]; $left = $left + 1; }
                    else { $merged[] = $sorted[$right]; $right = $right + 1; }
                }
                $start = $end;
            }
            $sorted = $merged;
            if ($width > $count - $width) { break; }
            $width = $width * 2;
        }
        $unique /** vector<int> */ = []; $first = true; $last = 0;
        foreach ($sorted as $id) {
            if ($first || ($id !== $last)) { $unique[] = $id; }
            $first = false; $last = $id;
        }
        return $unique;
    }
}
