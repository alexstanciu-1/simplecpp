<?php
declare(strict_types=1);
namespace collect_symbols;

/** Source-only coordinator. Provider imports and semantic comparison are separate later work. */
final class Declaration_Collector {
    public static function collect(\parse\Frontend_Set $frontends, Symbol_Store $previous, bool $full): Symbol_Refresh {
        if (!$frontends->valid) { throw new \LogicException('Cannot collect failed frontends'); }
        $candidate = new Symbol_Store($previous->next_symbol_id());
        $result = new Symbol_Refresh($previous, $candidate);
        $seen /** hash<bool> */ = [];
        foreach ($frontends->files as $file) {
            \parse\Frontend_Set::require_file($file);
            $path = $file->tokens->source->path;
            if (isset($seen[$path])) { throw new \LogicException('Duplicate collection path'); }
            $seen[$path] = true;
            $entry_id = $previous->entry_symbol_id($path);
            $reuse = false;
            if (!$full) {
                if ($entry_id !== 0) { $reuse = $previous->symbol_by_id($entry_id)->frontend === $file; }
            }
            if ($reuse) {
                foreach ($previous->file_symbol_ids($path) as $id) {
                    $old = $previous->symbol_by_id($id);
                    $conflict = $candidate->conflict($old->name, (int)$old->declaration->kind, $old->owner_symbol_id);
                    if ($conflict !== 0) { return Declaration_Collector::duplicate($result, $old, $conflict); }
                    $candidate->add($old);
                }
                continue;
            }
            $facts = File_Collector::collect_file($file);
            if (!$facts->valid) {
                return Declaration_Collector::failed($result, $path, $facts->error_start, $facts->error_length, $facts->error_reason);
            }
            $owners /** hash<int,int> */ = [];
            foreach ($facts->rows as $fact) {
                $owner = 0;
                $owner_node = (int)$fact->owner_declaration_node_id;
                if ($owner_node !== 0) {
                    if (!isset($owners[$owner_node])) { throw new \LogicException('Declaration owner must precede member'); }
                    $owner = $owners[$owner_node];
                }
                $kind = (int)$fact->kind;
                $name = File_Collector::name_text($file, (int)$fact->name_node_id);
                $id = $entry_id;
                if ($kind !== \collect_symbols\SYMBOL_FILE_ENTRY) { $id = $previous->find_symbol($name, $kind, $owner); }
                if ($id === 0) { $id = $candidate->allocate_id(); }
                $record = new Symbol_Record($id, $owner, $name, $file, $fact);
                $conflict = $candidate->conflict($name, $kind, $owner);
                if ($conflict !== 0) { return Declaration_Collector::duplicate($result, $record, $conflict); }
                $candidate->add($record);
                if (($kind === \collect_symbols\SYMBOL_STRUCT) || ($kind === \collect_symbols\SYMBOL_TEMPLATE_STRUCT)) {
                    $owners[(int)$fact->declaration_node_id] = $id;
                }
            }
        }
        for ($i /** int */ = 0; $i < $candidate->size(); ++$i) {
            $record = $candidate->record_at($i);
            $status = \collect_symbols\CHANGE_ADDED;
            if ($previous->contains($record->symbol_id)) {
                if ($previous->symbol_by_id($record->symbol_id) === $record) { continue; }
                $status = \collect_symbols\CHANGE_UNCOMPARED;
            }
            $change = new Symbol_Change();
            $change->symbol_id = $record->symbol_id;
            $change->status = $status;
            $result->changes[] = $change;
        }
        for ($i /** int */ = 0; $i < $previous->size(); ++$i) {
            $record = $previous->record_at($i);
            if (!$candidate->contains($record->symbol_id)) {
                $change = new Symbol_Change();
                $change->symbol_id = $record->symbol_id;
                $change->status = \collect_symbols\CHANGE_REMOVED;
                $result->changes[] = $change;
            }
        }
        return $result;
    }
    private static function duplicate(Symbol_Refresh $result, Symbol_Record $record, int $first_id): Symbol_Refresh {
        $node = $record->frontend->tree->row((int)$record->declaration->name_node_id);
        $first = $result->current->symbol_by_id($first_id);
        $first_node = $first->frontend->tree->row((int)$first->declaration->declaration_node_id);
        $reason = 'Duplicate declaration ' . $record->name . '; first defined at '
            . $first->frontend->tokens->source->path . ':' . (int)$first_node->start;
        return Declaration_Collector::failed($result, $record->frontend->tokens->source->path, (int)$node->start, (int)$node->length, $reason);
    }
    private static function failed(Symbol_Refresh $result, string $path, int $start, int $length, string $reason): Symbol_Refresh {
        $result->valid = false;
        $result->error_path = $path;
        $result->error_start = $start;
        $result->error_length = $length;
        $result->error_reason = $reason;
        $result->current = new Symbol_Store($result->previous->next_symbol_id());
        return $result;
    }
}
