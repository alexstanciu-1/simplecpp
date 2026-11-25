<?php
declare(strict_types=1);
namespace parse;

/** Pure parser planning. Paths identify files within snapshots, never stable semantic IDs. */
final class Parser_Selection {
    public static function index(\tokenize\Lexical_Project $current): array /** hash<int> */ {
        if (($current->entry_index < 0) || ($current->entry_index >= q_count($current->buffers))) { throw new \LogicException('Invalid parser entry index'); }
        $by_path /** hash<int> */ = [];
        foreach ($current->buffers as $position => $tokens) {
            $path = $tokens->source->path;
            if (($path === '') || isset($by_path[$path])) { throw new \LogicException('Empty or duplicate parser path'); }
            $by_path[$path] = $position;
        }
        return $by_path;
    }
    public static function select(\tokenize\Lexical_Project $current, Frontend_Set $previous, bool $full): Parser_Plan {
        $paths = Parser_Selection::index($current);
        if (!$previous->valid) { throw new \LogicException('Cannot reuse a failed frontend set'); }
        $plan = new Parser_Plan($current);
        foreach ($current->buffers as $position => $tokens) {
            $old_index = $previous->find_path($tokens->source->path);
            if (($full) || ($old_index < 0) || (!$tokens->valid)) { $plan->tasks[] = $position; continue; }
            $old = $previous->files[$old_index];
            Frontend_Set::require_file($old);
            if ($old->tokens->source->content !== $tokens->source->content) { $plan->tasks[] = $position; continue; }
            if ($old->tokens === $tokens) { $plan->retained[$position] = $old; continue; }
            // Equal bytes permit sharing syntax, while diagnostics and source ownership use current tokens.
            $rebound = new Parse_Result($tokens, $old->tree);
            $rebound->root = $old->root;
            $rebound->entry = $old->entry;
            $rebound->definitions = $old->definitions;
            $plan->retained[$position] = $rebound;
        }
        return $plan;
    }
}
