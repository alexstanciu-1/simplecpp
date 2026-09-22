<?php
declare(strict_types=1);
namespace term_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    private static function word(): \type_model\Named_Definition {
        $p=new \type_model\Lifetime_Policy(); $p->copy=1; $p->construction=1; $p->assignment=1; $p->expiring=1;
        $ops /** vector<\type_model\Lifecycle_Operation> */ = [];
        return new \type_model\Named_Definition('int','',\type_model\Representation::integer(32),new \type_model\Lifetime_Contract($p,$ops),true,'',false,false,true);
    }
    public static function run(): void {
        $a=\check_templates\Type_Term::source(1); $b=\check_templates\Type_Term::source(1);
        Probe::check(\check_templates\Type_Term::same($a,$b)); Probe::check(!\check_templates\Type_Term::same($a,\check_templates\Type_Term::source(2)));
        $formal=\check_templates\Type_Term::parameter(7,0); Probe::check($formal->dependent);
        Probe::check(!\check_templates\Type_Term::same($formal,\check_templates\Type_Term::parameter(7,1)));
        Probe::check(!\check_templates\Type_Term::same($formal,\check_templates\Type_Term::parameter(8,0)));
        Probe::check(!\check_templates\Type_Term::same($formal,\check_templates\Type_Term::source(7)));
        $arguments /** vector<\check_templates\Type_Term> */ = [$a,$formal];
        $application=\check_templates\Type_Term::application(9,$arguments); $arguments[0]=$formal;
        Probe::check($application->dependent); Probe::check($application->argument_at(0)===$a);
        $same_args /** vector<\check_templates\Type_Term> */ = [$b,\check_templates\Type_Term::parameter(7,0)];
        Probe::check(\check_templates\Type_Term::same($application,\check_templates\Type_Term::application(9,$same_args)));
        $reversed /** vector<\check_templates\Type_Term> */ = [$formal,$a];
        Probe::check(!\check_templates\Type_Term::same($application,\check_templates\Type_Term::application(9,$reversed)));
        Probe::check(!\check_templates\Type_Term::constant('literal:1')->dependent);
        Probe::check(!\check_templates\Type_Term::same(\check_templates\Type_Term::constant('literal:1'),\check_templates\Type_Term::constant('project_constant:1')));
        $word=Probe::word(); $named=\check_templates\Type_Term::named($word);
        Probe::check(\check_templates\Type_Term::same($named,\check_templates\Type_Term::named($word)));
        Probe::check(!\check_templates\Type_Term::same($named,\check_templates\Type_Term::named(Probe::word())));
        $fields /** vector<\type_model\Field_Declaration> */ = [new \type_model\Field_Declaration('x',\type_model\Field_Type::named($word),true)];
        $record=new \type_model\Record_Declaration('Record','',$fields,true,0,null,0,0,0,0);
        $record_term=\check_templates\Type_Term::record($record);
        Probe::check(\check_templates\Type_Term::same($record_term,\check_templates\Type_Term::record($record)));
        Probe::check(!\check_templates\Type_Term::same($record_term,$named));
        $array=\check_templates\Type_Term::array_type($formal); Probe::check($array->dependent);
        $deep_a=$formal; $deep_b=\check_templates\Type_Term::parameter(7,0);
        for ($i=0;$i<1000;$i++) { $deep_a=\check_templates\Type_Term::array_type($deep_a); $deep_b=\check_templates\Type_Term::array_type($deep_b); }
        Probe::check(\check_templates\Type_Term::same($deep_a,$deep_b));
        $expression=new \check_templates\Expression_Type($formal,true); Probe::check($expression->readonly);
        $void_expression=new \check_templates\Expression_Type(null,false); Probe::check($void_expression->type===null);
        $none /** vector<\check_templates\Type_Term> */ = [];
        for ($case_index=0;$case_index<7;$case_index++) {
            $failed=false;
            try {
                if ($case_index===0) { $bad=\check_templates\Type_Term::source(0); }
                elseif ($case_index===1) { $bad=\check_templates\Type_Term::parameter(1,-1); }
                elseif ($case_index===2) { $bad=\check_templates\Type_Term::constant(''); }
                elseif ($case_index===3) { $application->argument_at(2); }
                elseif ($case_index===4) { $bad=new \check_templates\Type_Term(99,0,0,'',null,null,$none); }
                elseif ($case_index===5) { $bad=new \check_templates\Type_Term(1,1,0,'',$word,null,$none); }
                else { $bad=new \check_templates\Type_Term(5,0,0,'',null,null,$none); }
            } catch (\InvalidArgumentException $error) { $failed=true; } Probe::check($failed);
        }
        $source=new \read_sources\Source_Buffer(); $source->path='/template.phs'; $source->content='template<typename T> function identity($x T): T { return $x; } return 0;';
        $file=\parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));
        if (!$file->valid) { throw new \LogicException($file->error_reason); }
        $frontends=new \parse\Frontend_Set(); $frontends->add($file); $frontends->entry_index=0;
        $refresh=\collect_symbols\Declaration_Collector::collect($frontends,new \collect_symbols\Symbol_Store(1),false);
        if (!$refresh->valid) { throw new \LogicException($refresh->error_reason); } $symbols=$refresh->current;
        $definitions /** vector<\type_model\Named_Definition> */ = [$word]; $catalog=new \type_model\Type_Catalog('p','key','language_values',$definitions,$word,$word,null);
        $resolution=\resolve_symbols\Symbol_Resolver::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog,true);
        if (!$resolution->valid()) { throw new \LogicException($resolution->error_reason); }
        $owner=$symbols->symbol_by_id($symbols->find_symbol('identity',\collect_symbols\SYMBOL_TEMPLATE_FUNCTION,0));
        $bindings=$resolution->result()->for_symbol($owner->symbol_id);
        if ($bindings===null) { throw new \LogicException('Missing test bindings'); }
        $task=new \check_templates\Definition_Task($owner,$bindings); Probe::check($task->owner===$owner); Probe::check($task->bindings===$bindings);
        $other=new \collect_symbols\Symbol_Record($owner->symbol_id,$owner->owner_symbol_id,$owner->name,$owner->frontend,$owner->declaration);
        $failed=false; try { $stale=new \check_templates\Definition_Task($other,$bindings); } catch (\LogicException $error) { $failed=true; } Probe::check($failed);
        $dependencies /** vector<\collect_symbols\Symbol_Record> */ = [$owner];
        $binding_rows /** vector<\resolve_symbols\Symbol_Resolution> */ = [$bindings];
        $permission=new \check_templates\Definition_Result($task,$catalog,$dependencies,$binding_rows,1);
        $rows /** vector<\check_templates\Definition_Result> */ = [$permission];
        $permissions=new \check_templates\Template_Set($rows,1); $names=$resolution->result();
        $permissions->require_definition($owner,$names,$catalog);
        Probe::check($permissions->size()===1); Probe::check($permissions->for_definition($owner->symbol_id)===$permission);
        Probe::check($permission->current($owner,$names,$catalog)); Probe::check(!$permission->current($other,$names,$catalog));
        $new_catalog=new \type_model\Type_Catalog('p','key','language_values',$definitions,$word,$word,null);
        Probe::check(!$permission->current($owner,$names,$new_catalog));
        $new_resolution=\resolve_symbols\Symbol_Resolver::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog,true);
        Probe::check(!$permission->current($owner,$new_resolution->result(),$catalog));
        $dependencies[0]=$other; Probe::check($permission->dependency_at(0)===$owner);
        $failed=false; try { $permissions->require_definition($owner,$names,$new_catalog); } catch (\LogicException $error) { $failed=true; } Probe::check($failed);
        $no_permissions /** vector<\check_templates\Definition_Result> */ = []; $unproved=new \check_templates\Template_Set($no_permissions,0);
        $failed=false; try { $unproved->require_definition($owner,$names,$catalog); } catch (\LogicException $error) { $failed=true; } Probe::check($failed);
        $entry=$symbols->symbol_by_id($symbols->entry_symbol_id('/template.phs')); $unproved->require_definition($entry,$names,$catalog); Probe::check($unproved->size()===0);
    }
}
