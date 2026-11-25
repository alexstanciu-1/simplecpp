<?php
declare(strict_types=1);
namespace parse;

/** Sequential composition of the same fixed plan, independent workers and atomic join. */
final class Parser {
    public static function parse(\tokenize\Lexical_Project $current, Frontend_Set $previous, bool $full): Frontend_Set {
        $plan = Parser_Selection::select($current, $previous, $full);
        $results /** vector<Parse_Result> */ = [];
        foreach ($plan->tasks as $position) {
            $result = File_Parser::parse($current->buffers[$position]);
            if (!$result->valid) { return Frontend_Set::failed($result); }
            $results[] = $result;
        }
        $join = new Frontend_Join($plan);
        return $join->join($results);
    }
}
