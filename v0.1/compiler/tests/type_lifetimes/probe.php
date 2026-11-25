<?php
declare(strict_types=1);
namespace lifetime_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    private static function runtime(int $kind): \type_model\Lifecycle_Operation {
        return \type_model\Lifecycle_Operation::runtime('provider','id','link','ccc',$kind);
    }
    private static function full_policy(): \type_model\Lifetime_Policy {
        $p = new \type_model\Lifetime_Policy(); $p->copy = 2; $p->cleanup = 1;
        $p->construction = 2; $p->assignment = 2; $p->expiring = 3; return $p;
    }
    private static function rejects(\type_model\Lifetime_Policy $policy, array $ops /** vector<\type_model\Lifecycle_Operation> */): bool {
        try { $contract = new \type_model\Lifetime_Contract($policy,$ops); } catch (\InvalidArgumentException $error) { return true; }
        return false;
    }
    private static function bad_source(int $role, int $member_role, int $body): bool {
        $members /** vector<\type_model\Lifecycle_Member> */ = [];
        $members[] = new \type_model\Lifecycle_Member(2,0,null,$member_role);
        try { \type_model\Lifecycle_Operation::source(1,'src',$role,$members,0,'ccc',$body); }
        catch (\InvalidArgumentException $error) { return true; }
        return false;
    }
    public static function run(): void {
        $names /** vector<string> */ = ['default_construct','destroy','copy_construct','move_construct','copy_assign'];
        $source_flags /** vector<bool> */ = [false,false,true,true,true];
        $create_flags /** vector<bool> */ = [true,false,true,true,false];
        $ops /** vector<\type_model\Lifecycle_Operation> */ = [];
        for ($i /** int */ = 0; $i < 5; ++$i) {
            $role = $i + 1;
            Probe::check(\type_model\Lifecycle_Roles::parse($names[$i]) === $role);
            Probe::check(\type_model\Lifecycle_Roles::name($role) === $names[$i]);
            Probe::check(\type_model\Lifecycle_Roles::has_source($role) === $source_flags[$i]);
            Probe::check(\type_model\Lifecycle_Roles::creates_destination($role) === $create_flags[$i]);
            $order = \type_model\Lifecycle_Roles::composition($role,false);
            Probe::check((int)$order->member_kind === $role);
            Probe::check($order->reverse_members === ($role === 2));
            Probe::check($order->body_before_members === ($role === 2));
            $operation = Probe::runtime($role); $ops[] = $operation;
            Probe::check($operation->imported);
            Probe::check($operation->kind === $role);
            Probe::check($operation->provider === 'provider');
            Probe::check($operation->provider_id === 'id');
            Probe::check($operation->link_name === 'link');
            Probe::check($operation->calling_convention === 'ccc');
            $members /** vector<\type_model\Lifecycle_Member> */ = [];
            $members[] = new \type_model\Lifecycle_Member(2,0,$operation,$role);
            $composed = \type_model\Lifecycle_Operation::source(3,'composed',$role,$members,4,'ccc',0);
            Probe::check(!$composed->imported);
            Probe::check($composed->member_count() === 1);
            Probe::check($composed->member_at(0)->operation === $operation);
            Probe::check($composed->repeat === 4);
            Probe::check($composed->type_id === 3);
            Probe::check($composed->member_at(0)->type_id === 2);
        }
        $copy_order = \type_model\Lifecycle_Roles::composition(3,true);
        Probe::check((int)$copy_order->member_kind === 1);
        $assign_order = \type_model\Lifecycle_Roles::composition(5,true);
        Probe::check((int)$assign_order->member_kind === 0);
        Probe::check(!$assign_order->body_before_members);
        Probe::check(!Probe::bad_source(3,1,11));
        Probe::check(Probe::bad_source(3,3,11));
        Probe::check(Probe::bad_source(5,5,11));
        Probe::check(!Probe::bad_source(4,3,0));
        Probe::check(Probe::bad_source(4,1,0));
        $no_members /** vector<\type_model\Lifecycle_Member> */ = [];
        $assignment = \type_model\Lifecycle_Operation::source(1,'assign',5,$no_members,0,'ccc',11);
        Probe::check($assignment->body_symbol_id === 11);
        Probe::check((int)$assignment->order()->member_kind === 0);
        $primitive = new \type_model\Lifecycle_Member(1,0,null,3);
        Probe::check($primitive->operation === null);
        $member_error = false;
        try { $bad = new \type_model\Lifecycle_Member(1,0,$ops[0],2); }
        catch (\InvalidArgumentException $error) { $member_error = true; }
        Probe::check($member_error);
        $none /** vector<\type_model\Lifecycle_Operation> */ = [];
        $policy = new \type_model\Lifetime_Policy();
        $empty = new \type_model\Lifetime_Contract($policy,$none);
        Probe::check(!$empty->has_operation(3));
        Probe::check((int)$empty->policy()->copy === 0);
        $policy->copy = 1; $policy->construction = 1; $policy->assignment = 1; $policy->expiring = 1;
        $value = new \type_model\Lifetime_Contract($policy,$none);
        $policy->copy = 0;
        Probe::check((int)$value->policy()->copy === 1);
        $view = $value->policy(); $view->copy = 0;
        Probe::check((int)$value->policy()->copy === 1);
        $full = new \type_model\Lifetime_Contract(Probe::full_policy(),$ops);
        for ($i /** int */ = 0; $i < 5; ++$i) {
            Probe::check($full->has_operation($i+1));
            Probe::check($full->operation($i+1) === $ops[$i]);
            $missing /** vector<\type_model\Lifecycle_Operation> */ = [];
            for ($j /** int */ = 0; $j < 5; ++$j) { if ($j !== $i) { $missing[] = $ops[$j]; } }
            Probe::check(Probe::rejects(Probe::full_policy(),$missing));
            $extra /** vector<\type_model\Lifecycle_Operation> */ = []; $extra[] = $ops[$i];
            Probe::check(Probe::rejects(new \type_model\Lifetime_Policy(),$extra));
        }
        $ops[] = $ops[0];
        Probe::check(Probe::rejects(Probe::full_policy(),$ops));
        $fallback = new \type_model\Lifetime_Policy(); $fallback->expiring = 2;
        Probe::check(Probe::rejects($fallback,$none));
        $fallback->copy = 1;
        Probe::check(!Probe::rejects($fallback,$none));
        $fallback->copy = 9;
        Probe::check(Probe::rejects($fallback,$none));
        $missing_error = false;
        try { $empty->operation(1); } catch (\LogicException $error) { $missing_error = true; }
        Probe::check($missing_error);
        $categories /** vector<string> */ = ['copy','cleanup','construction','assignment','expiring'];
        $lengths /** vector<int> */ = [3,2,3,3,4];
        for ($i /** int */ = 0; $i < 5; ++$i) {
            for ($j /** int */ = 0; $j < $lengths[$i]; ++$j) {
                Probe::check(\type_model\Lifetime_Policies::decode($categories[$i],\type_model\Lifetime_Policies::encode($categories[$i],$j)) === $j);
            }
        }
        Probe::check(\type_model\Lifetime_Policies::encode('construction',1) === 'zero');
        Probe::check(\type_model\Lifetime_Policies::encode('assignment',2) === 'call');
        Probe::check(\type_model\Lifetime_Policies::encode('expiring',2) === 'copy');
    }
}
