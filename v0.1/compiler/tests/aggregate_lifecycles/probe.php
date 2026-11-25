<?php
declare(strict_types=1);
namespace aggregate_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    private static function bind(\type_model\Type_Store $types, string $name, \type_model\Lifetime_Contract $life): int {
        $id=$types->declare_type($name,'fixture'); $shape=$types->intern_opaque(8,8);
        $types->set_representation($id,$shape);
        $types->bind_definition($id,new \type_model\Named_Definition($name,'fixture',$types->representation_by_id($shape),$life,null,'',false,false,true));
        return $id;
    }
    public static function run(): void {
        $types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));
        $policy=new \type_model\Lifetime_Policy(); $policy->copy=1; $policy->construction=1; $policy->assignment=1; $policy->expiring=1;
        $none /** vector<\type_model\Lifecycle_Operation> */ = [];
        $plain=Probe::bind($types,'plain',new \type_model\Lifetime_Contract($policy,$none));
        $owner=$types->declare_type('record','fixture'); $members /** vector<int> */ = [$plain,$plain]; $bodies=new \resolve_types\Lifecycle_Bodies();
        $life=\resolve_types\Lifecycle_Composition::derive($types,$owner,$members,0,$bodies); $p=$life->policy();
        Probe::check((int)$p->copy===1); Probe::check((int)$p->construction===1); Probe::check((int)$p->assignment===1); Probe::check((int)$p->expiring===1); Probe::check((int)$p->cleanup===0);
        $fields /** vector<\type_model\Type_Member> */ = [new \type_model\Type_Member($plain,'a',true),new \type_model\Type_Member($plain,'b',true)];
        $shape=$types->intern_structure($fields); $types->set_representation($owner,$shape);
        $types->bind_definition($owner,new \type_model\Named_Definition('record','fixture',$types->representation_by_id($shape),$life,null,'',false,false,true));
        $complete=\resolve_types\Lifecycle_Composition::complete_operation($types,$owner,\type_model\LIFECYCLE_COPY);
        if ($complete===null) { throw new \RuntimeException('Missing primitive complete copy'); }
        Probe::check($complete->member_count()===2); Probe::check($complete->member_at(1)->index===1); Probe::check($complete->member_at(0)->operation===null);
        Probe::check(\resolve_types\Lifecycle_Composition::complete_operation($types,$owner,\type_model\LIFECYCLE_MOVE)===null);
        $bodies->destructor=42;
        $custom=\resolve_types\Lifecycle_Composition::derive($types,$owner,$members,0,$bodies);
        Probe::check((int)$custom->policy()->construction===2); Probe::check((int)$custom->policy()->expiring===2);
        $destroy=$custom->operation(\type_model\LIFECYCLE_DESTROY);
        Probe::check($destroy->member_count()===0); Probe::check($destroy->body_symbol_id===42); Probe::check($destroy->order()->body_before_members);
        $managed=Probe::bind($types,'managed',$custom); $nested /** vector<int> */ = [$managed,$plain,$managed]; $clean=new \resolve_types\Lifecycle_Bodies();
        $outer=\resolve_types\Lifecycle_Composition::derive($types,$owner,$nested,1000000,$clean);
        $d=$outer->operation(\type_model\LIFECYCLE_DESTROY);
        Probe::check($d->member_count()===2); Probe::check($d->member_at(0)->index===2); Probe::check($d->member_at(1)->index===0); Probe::check($d->repeat===1000000);
        Probe::check($d->member_at(0)->operation===$destroy); Probe::check($d->calling_convention==='ccc');
        $blocked=new \type_model\Lifetime_Policy(); $blocked->construction=1;
        $noncopy=Probe::bind($types,'noncopy',new \type_model\Lifetime_Contract($blocked,$none)); $one /** vector<int> */ = [$noncopy];
        $bodies->destructor=0; $bodies->copy=77; $bodies->assignment=88;
        $override=\resolve_types\Lifecycle_Composition::derive($types,$owner,$one,0,$bodies);
        Probe::check((int)$override->policy()->copy===2); Probe::check((int)$override->policy()->assignment===2);
        Probe::check($override->operation(\type_model\LIFECYCLE_COPY)->member_at(0)->kind===\type_model\LIFECYCLE_DEFAULT);
        Probe::check($override->operation(\type_model\LIFECYCLE_ASSIGN)->member_count()===0);
        Probe::check((int)$override->policy()->expiring===2);
        $copyable=Probe::bind($types,'customcopy',$override); $move_members /** vector<int> */ = [$plain,$copyable];
        $move=\resolve_types\Lifecycle_Composition::derive($types,$owner,$move_members,0,$clean)->operation(\type_model\LIFECYCLE_MOVE);
        Probe::check($move->member_at(0)->kind===\type_model\LIFECYCLE_MOVE); Probe::check($move->member_at(0)->operation===null);
        Probe::check($move->member_at(1)->kind===\type_model\LIFECYCLE_COPY); Probe::check($move->member_at(1)->operation===$override->operation(\type_model\LIFECYCLE_COPY));
        $blocked->construction=0; $unavailable=Probe::bind($types,'unavailable',new \type_model\Lifetime_Contract($blocked,$none)); $bad /** vector<int> */ = [$unavailable];
        $rejected=false; try { \resolve_types\Lifecycle_Composition::derive($types,$owner,$bad,0,$bodies); } catch (\RuntimeException $error) { $rejected=true; } Probe::check($rejected);
        $ctor=new \resolve_types\Lifecycle_Bodies(); $ctor->constructor=9;
        $denied=\resolve_types\Lifecycle_Composition::derive($types,$owner,$bad,0,$ctor);
        Probe::check((int)$denied->policy()->construction===0); Probe::check((int)$denied->policy()->expiring===0);
        $rejected=false; try { \resolve_types\Lifecycle_Composition::derive($types,$owner,$members,-1,$clean); } catch (\InvalidArgumentException $error) { $rejected=true; } Probe::check($rejected);
        Probe::check((int)$policy->copy===1); Probe::check((int)$clean->constructor===0); Probe::check((int)$life->policy()->cleanup===0);
    }
}
