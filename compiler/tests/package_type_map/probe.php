<?php
declare(strict_types=1);
namespace package_type_map_test;
final class Probe {
    private static function source_definition(\type_model\Lifetime_Contract $life): \prepare_backend\Source_Type_Export {
        $definition = new \type_model\Named_Definition('R','',\type_model\Representation::structure(0,0),$life,null,'',false,false,true);
        $config = new \prepare_backend\Backend_Configuration('b','t','d','','','a','r');
        $fields /** vector<\type_model\Type_Member> */ = []; $children /** hash<\prepare_backend\Layout_Dependency,int> */ = [];
        $dependency = new \prepare_backend\Layout_Dependency(1,$definition,$fields,$children);
        $offsets /** vector<int> */ = [];
        $layout = new \prepare_backend\Storage_Layout($definition,$config,$fields,'{}',8,8,$offsets,new \type_model\Type_Lineage(),$dependency);
        $arguments /** vector<\resolve_types\Export_Argument> */ = [];
        $identity = \resolve_types\Export_Type_Identity::source('p','r.phs','','R',$arguments);
        $identities /** hash<\resolve_types\Export_Type_Identity,int> */ = []; $identities[1] = $identity;
        $capabilities /** hash<\prepare_backend\Source_Export_Capability> */ = [];
        $task = new \prepare_backend\Source_Export_Task(new \compile\Native_Project('p','/src','/out'),$identity,$layout,$identities,$capabilities);
        $exports /** hash<\prepare_backend\Source_Operation_Export> */ = [];
        return new \prepare_backend\Source_Type_Export($task,$exports);
    }
    public static function run(string $text): void {
        $policy = new \type_model\Lifetime_Policy(); $policy->copy = 1; $policy->assignment = 1;
        $none /** vector<\type_model\Lifecycle_Operation> */ = [];
        $life = new \type_model\Lifetime_Contract($policy,$none);
        $integer = new \type_model\Named_Definition('Int','',\type_model\Representation::integer(64),$life,true,'',false,false,false);
        $definitions /** vector<\type_model\Named_Definition> */ = [$integer];
        $catalog = new \type_model\Type_Catalog('p','key','language_values',$definitions,$integer,$integer,null);
        $source_export = Probe::source_definition($life);
        $language = new \type_model\Named_Definition('Owned','provider_ns',\type_model\Representation::opaque(8,8),$life,null,'',false,false,false);
        $native_type = new \load_runtime\Runtime_Type('foreign',new \load_runtime\Runtime_Storage(\load_runtime\RUNTIME_STORAGE_OPAQUE,8,8),null,null,$language);
        $owner = new \load_runtime\Runtime_Type_Import('accepted','foreign',$native_type,'t','d');
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index);
            $rows /** vector<\scpp\Json_View> */ = []; $data = $fixture->member('rows');
            for ($i = 0; $i < $data->size(); $i++) { $rows[] = $data->at($i); }
            $operations /** vector<\scpp\Json_View> */ = []; $operation_data = $fixture->member('operations');
            for ($i = 0; $i < $operation_data->size(); $i++) { $operations[] = $operation_data->at($i); }
            $types /** hash<\type_model\Type_Reference> */ = []; $type_data = $fixture->member('types');
            for ($i = 0; $i < $type_data->size(); $i++) {
                $id = $type_data->key($i);
                $name = $type_data->member($id)->text();
                if ($name === '@parameter') { $types[$id] = \type_model\Type_Reference::parameter('owner',0); }
                else { $types[$id] = \type_model\Type_Reference::named($name,''); }
            }
            $imports /** hash<\load_runtime\Runtime_Type_Import> */ = []; $import_data = $fixture->member('imports');
            for ($i = 0; $i < $import_data->size(); $i++) { $imports[$import_data->at($i)->text()] = $owner; }
            $sources /** hash<\prepare_backend\Source_Type_Export> */ = []; $source_data = $fixture->member('sources');
            for ($i = 0; $i < $source_data->size(); $i++) { $sources[$source_data->at($i)->text()] = $source_export; }
            $callables /** hash<\type_model\Type_Reference> */ = [];
            $bindings = new \load_runtime\Package_Bindings($types,$callables,$imports,$sources);
            $accepted = true; $matches = true;
            try {
                $result = \load_runtime\Package_Type_Map::types($rows,$catalog,$operations,'provider',$bindings);
                if (q_count($result) !== q_count($rows)) { $matches = false; }
                foreach ($rows as $row) {
                    $id = $row->member('id')->text();
                    if (!isset($result[$id])) { $matches = false; continue; }
                    $value = $result[$id];
                    if (isset($sources[$id])) {
                        if (($value->language_type !== $source_export->task->layout->definition) || ($value->storage->kind !== \load_runtime\RUNTIME_STORAGE_RECORD)) { $matches = false; }
                    } elseif (isset($imports[$id])) {
                        if (($value->language_type !== $language) || ($value->storage->kind !== \load_runtime\RUNTIME_STORAGE_OPAQUE)) { $matches = false; }
                    } else {
                        $exposed = isset($types[$id]);
                        if ($row->has('language_type')) { if ($row->member('language_type')->kind() !== 'null') { $exposed = true; } }
                        if ($exposed) { if ($value->language_type !== $integer) { $matches = false; } }
                        else { if ($value->language_type !== null) { $matches = false; } }
                    }
                }
            } catch (\RuntimeException $error) { $accepted = false; }
            // Rejections, including late failures, cannot mutate accepted owner records or binding membership.
            if ((q_count($bindings->types) !== $type_data->size()) || (q_count($bindings->sources) !== $source_data->size()) || (q_count($bindings->imports) !== $import_data->size())) { $matches = false; }
            if (($owner->type !== $native_type) || ($native_type->language_type !== $language) || ($source_export->task->layout->size !== 8) || ($catalog->find_type('Int','') !== $integer)) { $matches = false; }
            echo (($accepted === $fixture->member('accept')->boolean()) && $matches) ? "true\n" : "false\n";
        }
    }
}
