<?php
declare(strict_types=1);
namespace resolve_types;

/** Source policy only; the provider catalog will supply the language return definition later. */
final class Entry_Preparation {
    public static function prepare(\parse\Frontend_Set $frontends, \collect_symbols\Symbol_Refresh $collection): Entry_Selection {
        if ((!$frontends->valid) || (!$collection->valid)) { throw new \LogicException('Entry preparation requires successful inputs'); }
        $count = q_count($frontends->files);
        $selected = $frontends->entry_index;
        if (($selected < 0) || ($selected >= $count)) { throw new \LogicException('Missing participating project entry'); }
        $symbols = $collection->current;
        // Validate fixed snapshot membership before producing any source-policy diagnostic.
        for ($i /** int */ = 0; $i < $count; ++$i) {
            $file = $frontends->files[$i];
            \parse\Frontend_Set::require_file($file);
            $path = $file->tokens->source->path;
            if ($frontends->find_path($path) !== $i) { throw new \LogicException('Inconsistent frontend membership'); }
            $id = $symbols->entry_symbol_id($path);
            if ($id === 0) { throw new \LogicException('Missing participating file entry'); }
            if ($symbols->symbol_by_id($id)->source_frontend() !== $file) { throw new \LogicException('Stale file entry snapshot'); }
        }
        $entries = 0;
        for ($i /** int */ = 0; $i < $symbols->size(); ++$i) {
            $record = $symbols->record_at($i);
            if (!$record->is_source()) { continue; }
            $position = $frontends->find_path($record->source_frontend()->tokens->source->path);
            if (($position < 0) || ($position >= $count)) { throw new \LogicException('Symbol outside participating sources'); }
            if ($frontends->files[$position] !== $record->source_frontend()) { throw new \LogicException('Stale source symbol snapshot'); }
            if ($record->kind() === \collect_symbols\SYMBOL_FILE_ENTRY) { ++$entries; }
        }
        if ($entries !== $count) { throw new \LogicException('Incomplete file entry membership'); }
        $selected_file = $frontends->files[$selected];
        $selected_id = $symbols->entry_symbol_id($selected_file->tokens->source->path);
        foreach ($frontends->files as $file) {
            $id = $symbols->entry_symbol_id($file->tokens->source->path);
            $entry = $symbols->symbol_by_id($id);
            $body = $file->tree->row((int)$entry->source_fact()->body_node_id);
            if ((int)$body->kind !== \parse\SYNTAX_BLOCK) { throw new \LogicException('Missing file entry body'); }
            if ($id === $selected_id) { continue; }
            if ((int)$body->first_child !== 0) {
                $statement = $file->tree->row((int)$body->first_child);
                $failure = new Entry_Selection($symbols, 0);
                $failure->error_path = $file->tokens->source->path;
                $failure->error_start = (int)$statement->start;
                $failure->error_length = (int)$statement->length;
                $failure->error_reason = 'Executable top-level code outside the manifest entry is unsupported; initialization ordering is not defined';
                return $failure;
            }
        }
        return new Entry_Selection($symbols, $selected_id);
    }
}
