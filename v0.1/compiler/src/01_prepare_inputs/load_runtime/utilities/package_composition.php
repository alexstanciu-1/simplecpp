<?php
declare(strict_types=1);
namespace load_runtime;

/** Privately compose normalized semantic contracts; filesystem acceptance and lease publication belong to the adapter. */
final class Package_Composition {
    public static function build(Package_Composition_Input $input): Package_Contract_Set {
        $context = $input->context; $manifest = $input->manifest; $catalog = $context->catalog;
        if ($manifest->project !== ($context->project !== null)) { throw new \RuntimeException('Package composition project mode mismatch'); }
        $references /** hash<\type_model\Type_Reference> */ = [];
        $imports /** hash<Runtime_Type_Import> */ = []; $sources /** hash<\prepare_backend\Source_Type_Export> */ = [];
        $bindings = new Package_Bindings($references,$references,$imports,$sources);
        if ($context->bindings !== null) { $bindings = $context->bindings; }
        foreach ($bindings->imports as $import) {
            if (($import->target_triple !== $manifest->target->triple) || ($import->data_layout !== $manifest->target->data_layout)) { throw new \RuntimeException('Native type import target mismatch'); }
        }
        foreach ($bindings->sources as $source_export) {
            $project = $context->project;
            if ($project === null) { throw new \RuntimeException('Source payload requires current project export'); }
            $key = $source_export->task->identity->key();
            if (!isset($project->exports[$key])) { throw new \RuntimeException('Source payload requires current project export'); }
            if ($project->exports[$key] !== $source_export) { throw new \RuntimeException('Source payload requires exact current project export'); }
        }
        $rows = $input->metadata->types; $operations = $input->metadata->operations;
        $types = Package_Type_Map::types($rows,$catalog,$operations,$manifest->provider,$bindings);
        $previous = $input->previous;
        if ($previous !== null) {
            if ($context->bindings !== null) {
                if (($previous->directory === $context->directory) && ($previous->base_catalog === $catalog)
                    && ($previous->provider === $manifest->provider) && ($previous->target_triple === $manifest->target->triple)
                    && ($previous->data_layout === $manifest->target->data_layout)) { $types = Type_Retention::retain($types,$bindings,$previous); }
            }
        }
        $record_batch = Record_Import::records($rows,$types,$catalog,$manifest->target);
        $types = $record_batch->type_map();
        $storage = Storage_Import::storage_families($rows,$types,$operations,$manifest->provider);
        $families /** hash<\type_model\Storage_Family> */ = [];
        foreach ($storage as $family) { $families[$family->id] = $family; }
        $definitions /** vector<\type_model\Named_Definition> */ = [];
        for ($index = 0; $index < $catalog->size(); $index++) { $definitions[] = $catalog->definition_at($index); }
        foreach ($types as $type) {
            if (($type->storage->kind === \load_runtime\RUNTIME_STORAGE_OPAQUE) || ($type->storage->kind === \load_runtime\RUNTIME_STORAGE_BYTE_SPAN)) {
                if ($type->language_type !== null) { if (!isset($families[$type->id])) { $definitions[] = $type->language_type; } }
            }
        }
        $records /** vector<\type_model\Record_Declaration> */ = [];
        for ($index = 0; $index < $catalog->record_count(); $index++) { $records[] = $catalog->record_at($index); }
        for ($index = 0; $index < $record_batch->record_count(); $index++) { $records[] = $record_batch->record_at($index); }
        $composed = $catalog;
        if ((q_count($definitions) !== $catalog->size()) || ($record_batch->record_count() !== 0) || (q_count($families) !== 0)) {
            $composed = new \type_model\Type_Catalog($catalog->provider,$input->catalog_content_key,$catalog->representation_scope,
                $definitions,$catalog->integer_literal_type,$catalog->entry_return_type,$catalog->boolean_type,$records);
        }
        $payload_ids /** vector<string> */ = [];
        foreach ($bindings->sources as $id => $source_export) { $payload_ids[] = $id; }
        $callables = Callable_Import::callables($operations,$types,$manifest->provider,$bindings->callables,$payload_ids);
        if ($previous !== null) { $callables = Callable_Retention::retain($callables,$previous->callables()); }
        return new Package_Contract_Set($types,$composed,$families,$callables);
    }
}
