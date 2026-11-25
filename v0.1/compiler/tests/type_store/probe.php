<?php
declare(strict_types=1);
namespace type_store_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    private static function same_row(\type_model\Type_Record $left, \type_model\Type_Record $right): bool { return $left === $right; }
    private static function same_lineage(\type_model\Type_Lineage $left, \type_model\Type_Lineage $right): bool { return $left === $right; }
    public static function run(): void {
        $context = new \type_model\Type_Context('config','providers','target'); $types = \type_model\Type_Store::fresh($context);
        Probe::check($types->type_count() === 0); Probe::check($types->representation_count() === 0); Probe::check($types->member_count() === 0);
        $pending = $types->reference_type('word','fixture'); $pending_row = $types->type_by_id($pending);
        Probe::check(!$pending_row->declared); Probe::check($types->needs_representation($pending)); Probe::check($pending_row->definition === null);
        Probe::check($types->reference_type('word','fixture') === $pending);
        $signed_value = $types->declare_type('word','fixture'); Probe::check($signed_value === $pending); Probe::check($types->is_declared($signed_value));
        Probe::check(!$pending_row->declared);
        $unsigned_value = $types->declare_type('unsigned','fixture'); $integer = $types->intern_integer(32);
        $types->set_representation($signed_value,$integer); $types->set_representation($unsigned_value,$integer);
        Probe::check($signed_value !== $unsigned_value); Probe::check($types->representation_for_type($signed_value) === $types->representation_for_type($unsigned_value));
        Probe::check($types->find_type('word','') === 0); Probe::check($types->find_type('word','fixture') === $signed_value);
        $other = $types->declare_type('word','other'); Probe::check($other !== $signed_value);
        $a = $types->declare_type('c','ab'); $b = $types->declare_type('bc','a'); Probe::check($a !== $b);
        Probe::check($types->find_type('c','ab') === $a); Probe::check($types->find_type('bc','a') === $b);
        $unicode = $types->declare_type('名','é'); Probe::check($types->find_type('名','é') === $unicode);
        $void_type = $types->declare_type('nothing','fixture'); $types->set_representation($void_type,$types->intern_void());
        Probe::check($types->representation_for_type($void_type)->kind() === \type_model\REPRESENTATION_VOID);
        $binary = $types->intern_float('ieee_binary16'); $bfloat = $types->intern_float('bfloat16');
        Probe::check($binary !== $bfloat); Probe::check($types->representation_by_id($binary)->bit_width() === 16);
        Probe::check($types->representation_by_id($bfloat)->bit_width() === 16);
        $node = $types->declare_type('node','fixture'); $pointer = $types->declare_type('pointer','fixture');
        $types->set_representation($pointer,$types->intern_pointer($node,0));
        $words = $types->declare_type('words','fixture'); $types->set_representation($words,$types->intern_array($signed_value,4));
        $fields /** vector<\type_model\Type_Member> */ = [new \type_model\Type_Member($pointer,'next',true),new \type_model\Type_Member($words,'values',false)];
        $structure = $types->intern_structure($fields); $types->set_representation($node,$structure);
        $parameters /** vector<int> */ = [$pointer,$unsigned_value]; $value_modes /** vector<int> */ = [];
        $signature = $types->intern_signature($pointer,$parameters,$value_modes);
        $shape = $types->representation_for_type($node); $call = $types->representation_by_id($signature);
        Probe::check($shape->member_count() === 2); Probe::check($call->member_count() === 2);
        Probe::check($types->member_at($shape->member_first()) === $fields[0]); Probe::check(!$types->member_at($shape->member_first()+1)->writable);
        Probe::check($call->signature_return() === $pointer); Probe::check($types->member_at($call->member_first())->type_id === $pointer);
        Probe::check($types->member_at($call->member_first()+1)->type_id === $unsigned_value);
        Probe::check($call->result_production() === \type_model\RESULT_VALUE); Probe::check($call->parameter_passing(0) === \type_model\PASS_VALUE);
        Probe::check($types->representation_for_type($pointer)->element() === $node); Probe::check($types->representation_for_type($words)->member_count() === 4);
        Probe::check($types->intern_array($signed_value,0) !== $types->intern_array($signed_value,4)); Probe::check($types->intern_pointer($node,1) !== $types->intern_pointer($node,0));
        $members_before = $types->member_count(); $representations_before = $types->representation_count();
        Probe::check($types->intern_structure($fields) === $structure); Probe::check($types->intern_signature($pointer,$parameters,$value_modes) === $signature);
        Probe::check($types->member_count() === $members_before); Probe::check($types->representation_count() === $representations_before);
        $explicit_modes /** vector<int> */ = [\type_model\PASS_VALUE,\type_model\PASS_VALUE];
        Probe::check($types->intern_signature($pointer,$parameters,$explicit_modes) === $signature);
        $borrow_modes /** vector<int> */ = [\type_model\PASS_BORROW_CONST,\type_model\PASS_BORROW_MUTABLE];
        $borrowed = $types->intern_signature($pointer,$parameters,$borrow_modes); Probe::check($borrowed !== $signature);
        Probe::check($types->representation_by_id($borrowed)->parameter_passing(1) === \type_model\PASS_BORROW_MUTABLE);
        $reversed /** vector<\type_model\Type_Member> */ = [$fields[1],$fields[0]]; Probe::check($types->intern_structure($reversed) !== $structure);
        $renamed /** vector<\type_model\Type_Member> */ = [new \type_model\Type_Member($pointer,'other',true),$fields[1]]; Probe::check($types->intern_structure($renamed) !== $structure);
        $writable /** vector<\type_model\Type_Member> */ = [$fields[0],new \type_model\Type_Member($words,'values',true)]; Probe::check($types->intern_structure($writable) !== $structure);
        $no_fields /** vector<\type_model\Type_Member> */ = []; $no_parameters /** vector<int> */ = [];
        $empty_structure = $types->intern_structure($no_fields); Probe::check($types->representation_by_id($empty_structure)->member_count() === 0);
        $no_args = $types->intern_signature($void_type,$no_parameters,$value_modes); Probe::check($types->representation_by_id($no_args)->result_production() === \type_model\RESULT_NONE);
        Probe::check($types->representation_by_id($no_args)->signature_return() === $void_type);
        $owned = $types->intern_signature($node,$no_parameters,$value_modes); Probe::check($types->representation_by_id($owned)->result_production() === \type_model\RESULT_OWNED);
        $span = $types->intern_byte_span(); Probe::check($span === $types->intern_byte_span());
        Probe::check($types->representation_by_id($span)->kind() === \type_model\REPRESENTATION_BYTE_SPAN);
        $opaque = $types->intern_opaque(32,8); Probe::check($opaque === $types->intern_opaque(32,8)); Probe::check($opaque !== $types->intern_opaque(32,16));
        Probe::check($types->representation_by_id($opaque)->opaque_size() === 32);
        Probe::check($types->field_index($node,'next') === 0); Probe::check($types->field_index($node,'values') === 1);
        Probe::check($types->field_index($node,'missing') === -1); Probe::check($types->field_index($signed_value,'next') === -1);
        Probe::check($types->field_for($node,1) === $fields[1]);
        $candidate = $types->fork(); Probe::check(Probe::same_lineage($candidate->lineage,$types->lineage));
        Probe::check($candidate->context === $types->context); Probe::check(Probe::same_row($candidate->type_by_id($signed_value),$types->type_by_id($signed_value)));
        $candidate->set_representation($unsigned_value,$candidate->intern_integer(64)); $candidate->declare_type('added','fixture');
        Probe::check(!Probe::same_row($candidate->type_by_id($unsigned_value),$types->type_by_id($unsigned_value)));
        Probe::check($types->representation_for_type($unsigned_value)->bit_width() === 32); Probe::check($candidate->representation_for_type($unsigned_value)->bit_width() === 64);
        Probe::check($types->find_type('added','fixture') === 0); Probe::check($candidate->type_count() === $types->type_count()+1);
        $unchanged = $candidate->type_by_id($signed_value); $candidate->set_representation($signed_value,$integer); Probe::check(Probe::same_row($unchanged,$candidate->type_by_id($signed_value)));
        $candidate->invalidate_definition($signed_value); Probe::check($candidate->needs_representation($signed_value)); Probe::check(!$types->needs_representation($signed_value));
        Probe::check($candidate->is_declared($signed_value));
        $invalidated = $candidate->type_by_id($signed_value); $candidate->invalidate_definition($signed_value); Probe::check(Probe::same_row($invalidated,$candidate->type_by_id($signed_value)));
        // A changed return production is part of signature identity even when its type ID survives.
        $before_signature = $types->intern_signature($signed_value,$no_parameters,$value_modes);
        $types->set_representation($signed_value,$empty_structure); $after_signature = $types->intern_signature($signed_value,$no_parameters,$value_modes);
        Probe::check($after_signature !== $before_signature); Probe::check($types->representation_by_id($after_signature)->result_production() === \type_model\RESULT_OWNED);
        Probe::check($types->representation_by_id($before_signature)->result_production() === \type_model\RESULT_VALUE);
        $types->set_representation($signed_value,$integer);
        Probe::check(\type_model\Representation::integer(32)->same(\type_model\Representation::integer(32)));
        Probe::check(!\type_model\Representation::integer(32)->same(\type_model\Representation::integer(64)));
        Probe::check(!\type_model\Representation::floating('bfloat16')->same(\type_model\Representation::floating('ieee_binary16')));
        Probe::check(!\type_model\Representation::pointer($node,0)->same(\type_model\Representation::pointer($node,1)));
        Probe::check(!\type_model\Representation::fixed_array($signed_value,4)->same(\type_model\Representation::fixed_array($signed_value,0)));
        Probe::check(!\type_model\Representation::structure(0,1)->same(\type_model\Representation::structure(1,1)));
        Probe::check(!\type_model\Representation::signature($signed_value,0,2,$explicit_modes,\type_model\RESULT_VALUE)->same(\type_model\Representation::signature($signed_value,0,2,$borrow_modes,\type_model\RESULT_VALUE)));
        Probe::check(!\type_model\Representation::byte_span()->same(\type_model\Representation::void_type()));
        Probe::check(!\type_model\Representation::opaque(32,8)->same(\type_model\Representation::opaque(32,16)));
        $catalog = \load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
        $catalog_context = new \type_model\Type_Context('config',$catalog->content_key,$catalog->representation_scope);
        $cache = new \resolve_types\Type_Cache(null); Probe::check($cache->requires_rebuild($catalog_context));
        $resident = $cache->prepare($catalog_context,true);
        $int_id = \resolve_types\Type_Cache::materialize($resident,$catalog->entry_return_type);
        Probe::check($resident->definition_for_type($int_id) === $catalog->entry_return_type); Probe::check($resident->representation_for_type($int_id)->bit_width() === 64);
        $bound = $resident->type_by_id($int_id); Probe::check(\resolve_types\Type_Cache::materialize($resident,$catalog->entry_return_type) === $int_id);
        Probe::check(Probe::same_row($resident->type_by_id($int_id),$bound));
        $materialized = 0; for ($i=0;$i<$catalog->size();$i++) { \resolve_types\Type_Cache::materialize($resident,$catalog->definition_at($i)); $materialized++; }
        Probe::check($resident->type_count() === $materialized); Probe::check(q_count($resident->lifecycle_operations()) === 0);
        $resident_cache = new \resolve_types\Type_Cache($resident);
        $same_context = new \type_model\Type_Context('config',$catalog->content_key,$catalog->representation_scope);
        Probe::check(!$resident_cache->requires_rebuild($same_context));
        $incremental = $resident_cache->prepare($same_context,false); Probe::check(Probe::same_lineage($incremental->lineage,$resident->lineage));
        $fresh = $resident_cache->prepare($same_context,true); Probe::check(!Probe::same_lineage($fresh->lineage,$resident->lineage)); Probe::check($fresh->type_count() === 0);
        $different = new \type_model\Type_Context('changed',$catalog->content_key,$catalog->representation_scope); Probe::check($resident_cache->requires_rebuild($different));
        $bad = false; try { $resident_cache->prepare($different,false); } catch (\LogicException $e) { $bad=true; } Probe::check($bad);
        $other_catalog = \load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
        $bad = false; try { $incremental->bind_definition($int_id,$other_catalog->entry_return_type); } catch (\LogicException $e) { $bad=true; } Probe::check($bad);
        $incremental->invalidate_definition($int_id); \resolve_types\Type_Cache::materialize($incremental,$other_catalog->entry_return_type);
        Probe::check($incremental->definition_for_type($int_id) === $other_catalog->entry_return_type); Probe::check($resident->definition_for_type($int_id) === $catalog->entry_return_type);
    }
    public static function contracts(): void {
        $catalog = \load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
        $types = \type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));
        $id = $types->reference_type('int',''); $bad=false;
        try { $types->bind_definition($id,$catalog->entry_return_type); } catch (\LogicException $e) { $bad=true; } Probe::check($bad);
        $types->declare_type('int',''); $types->set_representation($id,$types->intern_integer(32)); $bad=false;
        try { $types->bind_definition($id,$catalog->entry_return_type); } catch (\LogicException $e) { $bad=true; } Probe::check($bad);
        $types->invalidate_definition($id); \resolve_types\Type_Cache::materialize($types,$catalog->entry_return_type); $bad=false;
        try { $types->set_representation($id,$types->intern_integer(32)); } catch (\LogicException $e) { $bad=true; } Probe::check($bad);
        Probe::check($types->representation_for_type($id)->bit_width() === 64);
        $other = $types->declare_type('other',''); $bad=false;
        try { $types->bind_definition($other,$catalog->entry_return_type); } catch (\LogicException $e) { $bad=true; } Probe::check($bad);
        $policy = new \type_model\Lifetime_Policy();
        $policy->copy=\type_model\COPY_CONSTRUCT; $policy->cleanup=\type_model\CLEANUP_DESTROY; $policy->construction=\type_model\CONSTRUCTION_CONSTRUCT;
        $policy->assignment=\type_model\ASSIGNMENT_CALL; $policy->expiring=\type_model\EXPIRING_CONSTRUCT;
        $ops /** vector<\type_model\Lifecycle_Operation> */ = []; $members /** vector<\type_model\Lifecycle_Member> */ = [];
        for ($role=1;$role<6;$role++) {
            if ($role===\type_model\LIFECYCLE_DESTROY) { $ops[]=\type_model\Lifecycle_Operation::runtime('provider','destroy','destroy','c',$role); }
            else { $ops[]=\type_model\Lifecycle_Operation::source($other,'op' . $role,$role,$members,0,'c',0); }
        }
        $life = new \type_model\Lifetime_Contract($policy,$ops);
        $definition = new \type_model\Named_Definition('other','',\type_model\Representation::integer(32),$life,true,'',false,false,false);
        $types->bind_definition($other,$definition); $source_ops=$types->lifecycle_operations();
        Probe::check(q_count($source_ops)===4);
        $expected /** vector<int> */ = [1,3,5,4];
        foreach ($expected as $index=>$role) { Probe::check($source_ops[$index]->kind===$role); }
    }
    public static function invalid(int $which): void {
        $types = \type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t')); $id=$types->declare_type('x',''); $bad=false;
        $parameters /** vector<int> */ = []; $modes /** vector<int> */ = []; $fields /** vector<\type_model\Type_Member> */ = [];
        try {
            if ($which===0) { $types->type_by_id(0); }
            elseif ($which===1) { $types->representation_by_id(0); }
            elseif ($which===2) { $types->member_at(-1); }
            elseif ($which===3) { $types->representation_for_type($id); }
            elseif ($which===4) { $types->definition_for_type($id); }
            elseif ($which===5) { $types->declare_type('x',''); }
            elseif ($which===6) { $types->reference_type('',''); }
            elseif ($which===7) { $types->intern_integer(0); }
            elseif ($which===8) { $types->intern_float('fake'); }
            elseif ($which===9) { $types->intern_pointer(999,0); }
            elseif ($which===10) { $types->intern_pointer($id,-1); }
            elseif ($which===11) { $types->intern_array($id,-1); }
            elseif ($which===12) { $types->intern_opaque(8,3); }
            elseif ($which===13) { $types->set_representation($types->reference_type('pending',''),$types->intern_void()); }
            elseif ($which===14) { $types->intern_signature($id,$parameters,$modes); }
            elseif ($which===15) { $fields[] = new \type_model\Type_Member($id,'',true); $types->intern_structure($fields); }
            elseif ($which===16) { $fields[] = new \type_model\Type_Member($id,'x',true); $fields[] = new \type_model\Type_Member($id,'x',true); $types->intern_structure($fields); }
            elseif ($which===17) { $fields[] = new \type_model\Type_Member(999,'x',true); $types->intern_structure($fields); }
            elseif ($which===18) { $types->set_representation($id,$types->intern_void()); $modes[] = \type_model\PASS_VALUE; $types->intern_signature($id,$parameters,$modes); }
            elseif ($which===19) { $types->set_representation($id,$types->intern_void()); $parameters[]=$id; $modes[]=99; $types->intern_signature($id,$parameters,$modes); }
            elseif ($which===20) { $types->set_representation($id,$types->intern_void()); $types->field_for($id,0); }
            elseif ($which===21) { $types->set_representation($id,$types->intern_structure($fields)); $types->field_for($id,0); }
        } catch (\InvalidArgumentException $e) { $bad=true; } catch (\LogicException $e) { $bad=true; }
        Probe::check($bad);
    }
}
