<?php
declare(strict_types=1);
namespace parser_project_test;
final class Probe {
    private static function check(bool $condition): void { echo $condition ? "true\n" : "false\n"; }
    private static function tokens(string $path, string $text): \tokenize\Lexical_Buffer {
        $source = new \read_sources\Source_Buffer();
        $source->path = $path;
        $source->content = $text;
        return \tokenize\File_Tokenizer::tokenize($source);
    }
    private static function initial(): \tokenize\Lexical_Project {
        $current = new \tokenize\Lexical_Project();
        $current->buffers[] = Probe::tokens('/a.phs', 'function a(): int { return 1; }');
        $current->buffers[] = Probe::tokens('/b.phs', 'return a();');
        $current->buffers[] = Probe::tokens('/c.phs', 'struct C { public int $x; }');
        $current->entry_index = 1;
        return $current;
    }
    private static function equal(\parse\Frontend_Set $left, \parse\Frontend_Set $right): bool {
        if ($left->entry_index !== $right->entry_index) { return false; }
        if (q_count($left->files) !== q_count($right->files)) { return false; }
        for ($i /** int */ = 0; $i < q_count($left->files); ++$i) {
            $a = $left->files[$i];
            $b = $right->files[$i];
            if ($a->tokens->source->path !== $b->tokens->source->path) { return false; }
            if (!\parse\Syntax_Comparer::equal($a, $a->root, $b, $b->root)) { return false; }
        }
        return true;
    }
    private static function rejects_segment(\parse\Frontend_Join $join, array $results /** vector<\parse\Parse_Result> */, int $index, int $count): bool {
        try { $join->merge($results, $index, $count); } catch (\LogicException $error) { return true; }
        return false;
    }
    private static function incomplete(\parse\Frontend_Join $join): bool {
        try { $join->finish(); } catch (\LogicException $error) { return true; }
        return false;
    }
    private static function rejects_selection(\tokenize\Lexical_Project $current, \parse\Frontend_Set $previous): bool {
        try { \parse\Parser_Selection::select($current, $previous, false); } catch (\LogicException $error) { return true; }
        return false;
    }
    public static function run(): void {
        $first = Probe::initial();
        $empty = new \parse\Frontend_Set();
        $initial_plan = \parse\Parser_Selection::select($first, $empty, false);
        Probe::check(q_count($initial_plan->tasks) === 3);
        $cold = \parse\Parser::parse($first, $empty, false);
        Probe::check($cold->valid);
        Probe::check(q_count($cold->files) === 3);
        Probe::check($cold->entry_index === 1);
        Probe::check($cold->find_path('/c.phs') === 2);
        Probe::check($cold->files[1]->tokens === $first->buffers[1]);
        $unchanged_plan = \parse\Parser_Selection::select($first, $cold, false);
        Probe::check(q_count($unchanged_plan->tasks) === 0);
        $same = \parse\Parser::parse($first, $cold, false);
        Probe::check($same->files[0] === $cold->files[0]);
        Probe::check(Probe::equal($same, $cold));
        $fresh = Probe::initial();
        $fresh->buffers[0]->source->mtime = 22;
        $fresh_plan = \parse\Parser_Selection::select($fresh, $cold, false);
        Probe::check(q_count($fresh_plan->tasks) === 0);
        $rebound = \parse\Parser::parse($fresh, $cold, false);
        Probe::check($rebound->files[0] !== $cold->files[0]);
        Probe::check($rebound->files[0]->tree === $cold->files[0]->tree);
        Probe::check($rebound->files[0]->tokens === $fresh->buffers[0]);
        Probe::check($rebound->files[0]->tokens->source->mtime === 22);
        Probe::check($cold->files[0]->tokens->source->mtime === 0);
        $updated = new \tokenize\Lexical_Project();
        $updated->buffers[] = Probe::tokens('/c.phs', 'struct C { public int $x; }');
        $updated->buffers[] = Probe::tokens('/a.phs', 'function a(): int { return 2; }');
        $updated->buffers[] = Probe::tokens('/d.phs', 'return a();');
        $updated->entry_index = 2;
        $plan = \parse\Parser_Selection::select($updated, $cold, false);
        Probe::check(q_count($plan->tasks) === 2);
        Probe::check(($plan->tasks[0] === 1) && ($plan->tasks[1] === 2));
        $incremental = \parse\Parser::parse($updated, $cold, false);
        Probe::check($incremental->find_path('/b.phs') === -1);
        Probe::check($incremental->find_path('/c.phs') === 0);
        Probe::check($incremental->entry_index === 2);
        Probe::check($incremental->files[0]->tree === $cold->files[2]->tree);
        Probe::check($incremental->files[1]->tree !== $cold->files[0]->tree);
        Probe::check($incremental->files[2]->tree !== $cold->files[1]->tree);
        $forced = \parse\Parser_Selection::select($updated, $incremental, true);
        Probe::check(q_count($forced->tasks) === 3);
        $full = \parse\Parser::parse($updated, $incremental, true);
        Probe::check(Probe::equal($full, $incremental));
        Probe::check($full->files[0]->tree !== $incremental->files[0]->tree);
        $results /** vector<\parse\Parse_Result> */ = [];
        $results[] = \parse\File_Parser::parse($updated->buffers[2]);
        $results[] = \parse\File_Parser::parse($updated->buffers[1]);
        $join = new \parse\Frontend_Join($plan);
        Probe::check(Probe::incomplete($join));
        $join->merge($results, 1, 1);
        Probe::check(Probe::incomplete($join));
        $join->merge($results, 0, 1);
        $joined = $join->finish();
        Probe::check(Probe::equal($joined, $full));
        Probe::check($joined->files[1] === $results[1]);
        Probe::check($joined->files[2] === $results[0]);
        $again = $join->finish();
        Probe::check($again->files[1] === $joined->files[1]);
        Probe::check(Probe::rejects_segment($join, $results, -1, 1));
        Probe::check(Probe::rejects_segment($join, $results, 0, -1));
        Probe::check(Probe::rejects_segment($join, $results, 3, 0));
        Probe::check(Probe::rejects_segment($join, $results, 1, 2));
        Probe::check(Probe::rejects_segment($join, $results, 0, 1));
        $none /** vector<\parse\Parse_Result> */ = [];
        $join->merge($none, 0, 0);
        Probe::check(Probe::equal($join->finish(), $joined));
        $atomic = new \parse\Frontend_Join($plan);
        $duplicate /** vector<\parse\Parse_Result> */ = [];
        $duplicate[] = $results[0];
        $duplicate[] = $results[0];
        Probe::check(Probe::rejects_segment($atomic, $duplicate, 0, 2));
        Probe::check(Probe::incomplete($atomic));
        Probe::check(Probe::equal($atomic->join($results), $joined));
        $stale_join = new \parse\Frontend_Join($plan);
        $stale /** vector<\parse\Parse_Result> */ = [];
        $stale[] = $results[0];
        $stale[] = \parse\File_Parser::parse(Probe::tokens('/a.phs', 'function a(): int { return 2; }'));
        Probe::check(Probe::rejects_segment($stale_join, $stale, 0, 2));
        Probe::check(Probe::equal($stale_join->join($results), $joined));
        $unselected /** vector<\parse\Parse_Result> */ = [];
        $unselected[] = \parse\File_Parser::parse($updated->buffers[0]);
        $rejection_join = new \parse\Frontend_Join($plan);
        Probe::check(Probe::rejects_segment($rejection_join, $unselected, 0, 1));
        $removed /** vector<\parse\Parse_Result> */ = [];
        $removed[] = $cold->files[1];
        Probe::check(Probe::rejects_segment($rejection_join, $removed, 0, 1));
        $invalid_entry = Probe::initial();
        $invalid_entry->entry_index = 3;
        Probe::check(Probe::rejects_selection($invalid_entry, $cold));
        $duplicate_paths = Probe::initial();
        $duplicate_paths->buffers[] = $duplicate_paths->buffers[0];
        Probe::check(Probe::rejects_selection($duplicate_paths, $cold));
        $bad_plan = new \parse\Parser_Plan($updated);
        $bad_plan->tasks[] = 1;
        $bad_plan->tasks[] = 1;
        Probe::check(Probe::incomplete(new \parse\Frontend_Join($bad_plan)));
        $partial_plan = new \parse\Parser_Plan($updated);
        $partial_plan->tasks[] = 1;
        Probe::check(Probe::incomplete(new \parse\Frontend_Join($partial_plan)));
        $bad = Probe::initial();
        $bad->buffers[1] = Probe::tokens('/b.phs', 'return; function broken(): int {');
        $failed = \parse\Parser::parse($bad, $cold, false);
        Probe::check(!$failed->valid);
        Probe::check(q_count($failed->files) === 0);
        Probe::check($failed->entry_index === -1);
        Probe::check($failed->error_path === '/b.phs');
        Probe::check(($failed->error_start === 32) && ($failed->error_length === 0));
        Probe::check($failed->error_reason !== '');
        Probe::check(Probe::rejects_selection($first, $failed));
        $repair = \parse\Parser::parse($first, $cold, false);
        Probe::check($repair->files[1] === $cold->files[1]);
        Probe::check(Probe::equal($repair, $cold));
        $lexical_bad = Probe::initial();
        $lexical_bad->buffers[0] = Probe::tokens('/a.phs', '"unfinished');
        $lexical_bad->valid = false;
        $lexical_failure = \parse\Parser::parse($lexical_bad, $cold, false);
        Probe::check(!$lexical_failure->valid);
        Probe::check($lexical_failure->error_path === '/a.phs');
        Probe::check($lexical_failure->error_reason === $lexical_bad->buffers[0]->error_reason);
        $moved = Probe::initial();
        $moved->buffers[0] = Probe::tokens('/a.phs', '/* moved */ function a(): int { return 1; }');
        $move_plan = \parse\Parser_Selection::select($moved, $cold, false);
        Probe::check(q_count($move_plan->tasks) === 1);
        $move_result = \parse\Parser::parse($moved, $cold, false);
        Probe::check($move_result->files[0]->tree !== $cold->files[0]->tree);
        Probe::check(Probe::equal($move_result, $cold));
        Probe::check(q_count($cold->files) === 3);
        Probe::check($cold->find_path('/b.phs') === 1);
    }
}
