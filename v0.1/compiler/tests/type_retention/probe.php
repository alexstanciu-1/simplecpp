<?php
declare(strict_types=1);
namespace type_retention_test;
final class Probe {
    public static function row(int $mode, string $id): \load_runtime\Runtime_Type {
        $policy = new \type_model\Lifetime_Policy(); $policy->copy = 1;
        $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        $life = new \type_model\Lifetime_Contract($policy,$operations);
        $bits = 64; if ($mode === 1) { $bits = 32; }
        $size = 8; if ($mode === 2) { $size = 16; }
        $alignment = 8; if ($mode === 3) { $alignment = 4; }
        $is_signed = $mode !== 4;
        $definition_name = $id; if ($id === 'catalog') { $definition_name = 'T'; }
        $definition = new \type_model\Named_Definition($definition_name,'',\type_model\Representation::integer($bits),$life,$is_signed,'',false,$mode === 6,false);
        if ($mode === 7) { $id = 'other'; }
        if ($mode === 5) { return new \load_runtime\Runtime_Type($id,new \load_runtime\Runtime_Storage(0,$size,$alignment),$bits,$is_signed,null); }
        if (($mode === 8) || ($mode === 9)) {
            $fields /** vector<\type_model\Field_Declaration> */ = [new \type_model\Field_Declaration('field',\type_model\Field_Type::named($definition),$mode === 9)];
            $record = new \type_model\Record_Declaration('T','',$fields,true,0,null,0,0,0,0);
            return new \load_runtime\Runtime_Type($id,new \load_runtime\Runtime_Storage(5,8,8),null,null,null,$record);
        }
        if ($mode === 10) { return new \load_runtime\Runtime_Type($id,new \load_runtime\Runtime_Storage(3,0,1),null,null,new \type_model\Named_Definition('Void','',\type_model\Representation::void_type(),null,null,'',false,false,false)); }
        return new \load_runtime\Runtime_Type($id,new \load_runtime\Runtime_Storage(0,$size,$alignment),$bits,$is_signed,$definition);
    }
    public static function package(array $types /** hash<\load_runtime\Runtime_Type> */, \load_runtime\Package_Bindings $bindings, bool $absent): \load_runtime\Runtime_Package {
        $value = Probe::row(0,'catalog')->language_type;
        if ($value === null) { throw new \LogicException('Fixture catalog missing'); }
        $definitions /** vector<\type_model\Named_Definition> */ = [$value];
        $catalog = new \type_model\Type_Catalog('p','key','language_values',$definitions,$value,$value,null);
        $strings /** vector<string> */ = []; $modules /** hash<string> */ = []; $calls /** vector<\type_model\Runtime_Callable> */ = [];
        $families /** hash<\type_model\Storage_Family> */ = []; $imports /** hash<\prepare_backend\Source_Operation_Export> */ = [];
        if ($absent) { return new \load_runtime\Runtime_Package('p','/p','t','d','clang',$strings,$types,$calls,$modules,$strings,'manifest',$catalog,$catalog,$families,null,null,$imports); }
        return new \load_runtime\Runtime_Package('p','/p','t','d','clang',$strings,$types,$calls,$modules,$strings,'manifest',$catalog,$catalog,$families,$bindings,null,$imports);
    }
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
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index); $matches = true;
            if ($fixture->member('kind')->text() === 'compare') {
                $a = Probe::row($fixture->member('left')->integer(),'T'); $b = Probe::row($fixture->member('right')->integer(),'T');
                $matches = ($a !== $b) && (\load_runtime\Type_Retention::same($a,$b) === $fixture->member('equal')->boolean());
            } else {
                $mode = $fixture->member('mode')->integer(); $binding_mode = $fixture->member('binding')->integer();
                $old = Probe::row(0,'T'); $old_first = Probe::row(0,'U'); $current = Probe::row($mode,'T'); $new_first = Probe::row(0,'U');
                $old_types /** hash<\load_runtime\Runtime_Type> */ = []; $old_types['U'] = $old_first;
                if ($binding_mode !== 5) { $old_types['T'] = $old; }
                $types /** hash<\load_runtime\Runtime_Type> */ = []; $types['U'] = $new_first; $types['T'] = $current;
                $old_names /** hash<\type_model\Type_Reference> */ = []; $old_names['U'] = \type_model\Type_Reference::named('U','');
                if ($binding_mode !== 3) { $old_names['T'] = \type_model\Type_Reference::named('T',''); }
                $names /** hash<\type_model\Type_Reference> */ = []; $names['U'] = \type_model\Type_Reference::named('U','');
                if ($binding_mode !== 2) {
                    $name = 'T'; if ($binding_mode === 1) { $name = 'Changed'; }
                    $names['T'] = \type_model\Type_Reference::named($name,'');
                }
                $calls /** hash<\type_model\Type_Reference> */ = []; $imports /** hash<\load_runtime\Runtime_Type_Import> */ = []; $sources /** hash<\prepare_backend\Source_Type_Export> */ = [];
                if ($fixture->member('kind')->text() === 'source') {
                    $policy = new \type_model\Lifetime_Policy(); $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
                    $life = new \type_model\Lifetime_Contract($policy,$operations);
                    $source_export = Probe::source_definition($life); $sources['T'] = $source_export;
                    $definition = $source_export->task->layout->definition;
                    if ($mode === 1) { $other_export = Probe::source_definition($life); $definition = $other_export->task->layout->definition; }
                    $current = new \load_runtime\Runtime_Type('T',new \load_runtime\Runtime_Storage(5,8,8),null,null,$definition);
                    $source_types /** hash<\load_runtime\Runtime_Type> */ = []; $source_types['U'] = $new_first;
                    if ($mode !== 2) { $source_types['T'] = $current; }
                    $types = $source_types;
                }
                $old_sources /** hash<\prepare_backend\Source_Type_Export> */ = [];
                $old_bindings = new \load_runtime\Package_Bindings($old_names,$calls,$imports,$old_sources);
                $bindings = new \load_runtime\Package_Bindings($names,$calls,$imports,$sources);
                $package = Probe::package($old_types,$old_bindings,$binding_mode === 4); $accepted = true;
                try {
                    $result = \load_runtime\Type_Retention::retain($types,$bindings,$package);
                    $wanted = $current; if ($binding_mode === 0) { $wanted = $old; }
                    if (($result['T'] !== $wanted) || (q_count($result) !== 2)) { $matches = false; }
                    $wanted_first = $old_first; if ($binding_mode === 4) { $wanted_first = $new_first; }
                    if ($result['U'] !== $wanted_first) { $matches = false; }
                } catch (\RuntimeException $error) { $accepted = false; }
                if ($accepted !== $fixture->member('accept')->boolean()) { $matches = false; }
                if (($types['U'] !== $new_first) || ($package->type_for('U') !== $old_first)) { $matches = false; }
                if (isset($types['T'])) { if ($types['T'] !== $current) { $matches = false; } }
            }
            echo $matches ? "true\n" : "false\n";
        }
    }
}
