<?php
declare(strict_types=1);
namespace identity_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    private static function integer(string $name): \type_model\Named_Definition {
        $p=new \type_model\Lifetime_Policy(); $p->copy=1; $p->construction=1; $p->assignment=1; $p->expiring=1;
        $ops /** vector<\type_model\Lifecycle_Operation> */ = [];
        return new \type_model\Named_Definition($name,'',\type_model\Representation::integer(32),new \type_model\Lifetime_Contract($p,$ops),true,'',false,false,true);
    }
    public static function run(): void {
        $context=new \type_model\Type_Context('c','p','t'); $types=\type_model\Type_Store::fresh($context);
        $ids=new \instantiate\Instance_Identities($types->lineage); $word=Probe::integer('word'); $other=Probe::integer('other');
        $args /** vector<\instantiate\Template_Argument> */ = [new \instantiate\Template_Argument($word)];
        $values /** vector<\instantiate\Template_Argument> */ = [new \instantiate\Template_Argument($word,'0')];
        $empty /** vector<\instantiate\Template_Argument> */ = [];
        Probe::check($ids->allocate($types,1,$args)===1); Probe::check($ids->allocate($types,1,$args)===1);
        Probe::check($ids->size()===1); Probe::check($ids->next_id()===2);
        Probe::check($ids->allocate($types,1,$values)===2); Probe::check($ids->allocate($types,2,$args)===3);
        Probe::check($ids->allocate($types,1,$empty)===4);
        $different /** vector<\instantiate\Template_Argument> */ = [new \instantiate\Template_Argument($other)];
        Probe::check($ids->allocate($types,1,$different)===5);
        $ordered /** vector<\instantiate\Template_Argument> */ = [$args[0],$different[0]];
        $reverse /** vector<\instantiate\Template_Argument> */ = [$different[0],$args[0]];
        Probe::check($ids->allocate($types,1,$ordered)===6); Probe::check($ids->allocate($types,1,$reverse)===7);
        $candidate_types=$types->fork(); $candidate=$ids->fork(); Probe::check($candidate->allocate($candidate_types,1,$args)===1);
        Probe::check($candidate->allocate($candidate_types,3,$args)===8); Probe::check($ids->next_id()===8); Probe::check($ids->size()===7);
        $wrong=\type_model\Type_Store::fresh($context); $failed=false;
        try { $ids->allocate($wrong,1,$args); } catch (\LogicException $error) { $failed=true; } Probe::check($failed);
        Probe::check($wrong->type_count()===0);
        $limit=new \instantiate\Instance_Identities($types->lineage,4294967295);
        Probe::check($limit->allocate($types,1,$args)===4294967295); Probe::check($limit->allocate($types,1,$args)===4294967295);
        $failed=false; try { $limit->allocate($types,2,$args); } catch (\OverflowException $error) { $failed=true; } Probe::check($failed);
        Probe::check($limit->next_id()===4294967296); Probe::check($limit->size()===1);
        $parent=false; try { $limit->allocate($types,2,$args); } catch (\RuntimeException $error) { $parent=true; } Probe::check($parent);
        $family=false;
        try { $limit->allocate($types,2,$args); }
        catch (\LogicException $error) { $family=false; }
        catch (\Exception $error) { $family=true; }
        Probe::check($family);
    }
}
