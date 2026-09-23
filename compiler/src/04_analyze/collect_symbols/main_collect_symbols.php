<?php
declare(strict_types=1);
namespace collect_symbols;

/** Reconcile source and provider declarations into one private candidate store. */
final class Declaration_Collector {
    public static function collect(\parse\Frontend_Set $frontends, Symbol_Store $previous, bool $full): Symbol_Refresh {
        $providers /** vector<Provider_Declaration> */ = [];
        return Declaration_Collector::with_providers($frontends,$previous,$full,$providers);
    }
    public static function with_providers(\parse\Frontend_Set $frontends, Symbol_Store $previous, bool $full,
        array $providers /** vector<Provider_Declaration> */): Symbol_Refresh {
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
                if ($entry_id !== 0) { $reuse = $previous->symbol_by_id($entry_id)->source_frontend() === $file; }
            }
            if ($reuse) {
                foreach ($previous->file_symbol_ids($path) as $id) {
                    $old = $previous->symbol_by_id($id);
                    $conflict = $candidate->conflict($old->name, $old->kind(), $old->owner_symbol_id,'');
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
                if ($kind !== \collect_symbols\SYMBOL_FILE_ENTRY) { $id = $previous->find_symbol($name, $kind, $owner,''); }
                if ($id === 0) { $id = $candidate->allocate_id(); }
                $record = Symbol_Record::from_source($id, $owner, $name, $file, $fact);
                $conflict = $candidate->conflict($name, $kind, $owner,'');
                if ($conflict !== 0) { return Declaration_Collector::duplicate($result, $record, $conflict); }
                $candidate->add($record);
                if (($kind === \collect_symbols\SYMBOL_STRUCT) || ($kind === \collect_symbols\SYMBOL_TEMPLATE_STRUCT)) {
                    $owners[(int)$fact->declaration_node_id] = $id;
                }
            }
        }
        foreach ($providers as $provider) { Declaration_Collector::import_provider($candidate,$previous,$provider); }
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
    /** Fixed provider membership is supplied by the accepted package/family consumer. */
    private static function import_provider(Symbol_Store $candidate, Symbol_Store $previous, Provider_Declaration $provider): void {
        $owner = 0;
        if ($provider->kind() === \collect_symbols\PROVIDER_METHOD) {
            $family = $provider->method()->family;
            $owner = $candidate->find_symbol($family->name,\collect_symbols\SYMBOL_TEMPLATE_STRUCT,0,$family->namespace_name);
            if ($owner === 0) { throw new \LogicException('Provider family must precede its method'); }
        }
        $kind = Symbol_Record::provider_kind($provider);
        if ($candidate->conflict($provider->name(),$kind,$owner,$provider->namespace_name()) !== 0) {
            throw new \RuntimeException('Duplicate project/provider declaration: ' . $provider->name());
        }
        $id = $previous->find_symbol($provider->name(),$kind,$owner,$provider->namespace_name());
        if ($id !== 0) {
            $old = $previous->symbol_by_id($id);
            if (!$old->is_source()) {
                if ($provider->same($old->provider())) { $candidate->add($old); return; }
            }
        } else { $id = $candidate->allocate_id(); }
        $candidate->add(Symbol_Record::from_provider($id,$owner,$provider));
    }
    private static function duplicate(Symbol_Refresh $result, Symbol_Record $record, int $first_id): Symbol_Refresh {
        $node = $record->source_frontend()->tree->row((int)$record->source_fact()->name_node_id);
        $first = $result->current->symbol_by_id($first_id);
        $first_node = $first->source_frontend()->tree->row((int)$first->source_fact()->declaration_node_id);
        $reason = 'Duplicate declaration ' . $record->name . '; first defined at '
            . $first->source_frontend()->tokens->source->path . ':' . (int)$first_node->start;
        return Declaration_Collector::failed($result, $record->source_frontend()->tokens->source->path, (int)$node->start, (int)$node->length, $reason);
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
