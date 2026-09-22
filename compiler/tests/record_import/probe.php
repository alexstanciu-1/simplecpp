<?php
declare(strict_types=1);
namespace record_import_test;
final class Probe {
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index); $mode = $fixture->member('scalar_mode')->integer();
            $policy = new \type_model\Lifetime_Policy(); $policy->copy = 1;
            $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
            $word = new \type_model\Named_Definition('word', '', \type_model\Representation::integer(32), new \type_model\Lifetime_Contract($policy, $operations), true, '', false, false, $mode !== 1);
            $definitions /** vector<\type_model\Named_Definition> */ = [$word];
            $known_records /** vector<\type_model\Record_Declaration> */ = [];
            $collision = $fixture->member('collision')->integer();
            if ($collision === 1) { $definitions[] = new \type_model\Named_Definition('Pair', '', \type_model\Representation::void_type(), null, null, '', false, false, false); }
            if ($collision === 2) {
                $none /** vector<\type_model\Field_Declaration> */ = [];
                $known_records[] = new \type_model\Record_Declaration('Pair', '', $none, true, 0, null, 0, 0, 0, 0);
            }
            $catalog = new \type_model\Type_Catalog('provider', 'content', 'language_values', $definitions, $word, $word, null, $known_records);
            $types /** hash<\load_runtime\Runtime_Type> */ = [];
            $kind = 0; $size = 4;
            if ($mode === 3) { $kind = 1; }
            if ($mode === 4) { $size = 32; }
            $types['word'] = new \load_runtime\Runtime_Type('word', new \load_runtime\Runtime_Storage($kind, $size, 4), 32, true, $word);
            if ($mode === 2) { $types['word'] = new \load_runtime\Runtime_Type('word', new \load_runtime\Runtime_Storage(0, 4, 4), 32, true, null); }
            $types['row'] = new \load_runtime\Runtime_Type('row', new \load_runtime\Runtime_Storage(5, $fixture->member('size')->integer(), $fixture->member('alignment')->integer()), null, null, null);
            $accepted = true; $correct = true;
            try {
                $rows = \load_runtime\Package_Syntax::rows($fixture->member('rows'), 'type');
                $batch = \load_runtime\Record_Import::records($rows, $types, $catalog, $fixture->member('target'));
                $correct = $batch->record_count() === 1;
                $record = $batch->record_at(0); $map = $batch->type_map();
                $correct = $correct && ($record->name === 'Pair') && ($record->namespace_name === '') && $record->automatic_lifecycle && ($record->layout_policy === 1) && ($record->field_count() === 2);
                $first = $record->field_at(0); $last = $record->field_at(1);
                $correct = $correct && ($first->name === 'first') && $first->writable && ($first->definition->element === $word) && ($first->definition->extent === 0) && ($last->name === 'last') && (!$last->writable);
                $layout = $record->native_layout;
                if ($layout === null) { $correct = false; }
                else { $correct = $correct && ($layout->size === 16) && ($layout->alignment === 8) && ($layout->field_offset(0) === 0) && ($layout->field_offset(1) === 8) && ($layout->target_triple === 'test-target') && ($layout->data_layout === 'e-p:64:64'); }
                $correct = $correct && ($map['row']->record === $record) && ($map['row']->storage === $types['row']->storage) && ($map['word'] === $types['word']);
                $map['word'] = $types['row']; $again = $batch->type_map();
                $correct = $correct && ($again['word'] === $types['word']);
            } catch (\RuntimeException $error) { $accepted = false; }
            catch (\InvalidArgumentException $error) { $accepted = false; }
            $correct = $correct && ($types['row']->record === null);
            echo (($accepted === $fixture->member('accept')->boolean()) && $correct) ? "true\n" : "false\n";
            if ($index === 0) { Probe::empty_batches($types, $catalog); }
        }
    }
    private static function empty_batches(array $types /** hash<\load_runtime\Runtime_Type> */, \type_model\Type_Catalog $catalog): void {
        $empty_rows /** vector<\scpp\Json_View> */ = [];
        $empty_batch = \load_runtime\Record_Import::records($empty_rows, $types, $catalog, json_read('{}'));
        echo $empty_batch->record_count() === 0 ? "true\n" : "false\n";
        $unchanged = $empty_batch->type_map();
        echo $unchanged['row'] === $types['row'] ? "true\n" : "false\n";
        $skipped = \load_runtime\Package_Syntax::rows(json_read('[{"kind":"integer"},{},{"kind":false}]'), 'type');
        $skipped_batch = \load_runtime\Record_Import::records($skipped, $types, $catalog, json_read('{}'));
        echo $skipped_batch->record_count() === 0 ? "true\n" : "false\n";
        $rejected = false;
        try { $missing = $empty_batch->record_at(0); } catch (\InvalidArgumentException $error) { $rejected = true; }
        echo $rejected ? "true\n" : "false\n";
    }
}
