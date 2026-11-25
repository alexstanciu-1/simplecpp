<?php
declare(strict_types=1);
namespace lifecycle_contracts_test;
final class Probe {
    public static function operation(int $mode): \type_model\Lifecycle_Operation {
        if ($mode < 6) {
            $provider = 'p'; $id = 'id'; $link = 'copy'; $convention = 'ccc'; $role = 3;
            if ($mode === 1) { $provider = 'q'; } if ($mode === 2) { $id = 'other'; }
            if ($mode === 3) { $link = 'other'; } if ($mode === 4) { $convention = 'fastcc'; } if ($mode === 5) { $role = 4; }
            return \type_model\Lifecycle_Operation::runtime($provider,$id,$link,$convention,$role);
        }
        $type_id = 1; $link = 'copy'; $convention = 'ccc'; $role = 3; $repeat = 0; $body = 0;
        if ($mode === 7) { $type_id = 2; } if ($mode === 8) { $link = 'other'; }
        if ($mode === 9) { $convention = 'fastcc'; } if ($mode === 10) { $repeat = 2; }
        if ($mode === 11) { $body = 1; } if ($mode === 12) { $role = 4; }
        $member_type = 3; $member_index = 0; $member_role = 3;
        if ($mode === 13) { $member_type = 4; } if ($mode === 14) { $member_index = 1; }
        if ($body !== 0) { $member_role = 1; }
        $members /** vector<\type_model\Lifecycle_Member> */ = [new \type_model\Lifecycle_Member($member_type,$member_index,null,$member_role)];
        if ($mode === 15) { $members[] = new \type_model\Lifecycle_Member(4,1,null,3); }
        if ($mode === 16) { $members[0] = new \type_model\Lifecycle_Member(3,0,Probe::operation(0),3); }
        if ($mode === 17) { $members[0] = new \type_model\Lifecycle_Member(3,0,Probe::operation(1),3); }
        if ($mode === 18) { $members[0] = new \type_model\Lifecycle_Member(3,0,Probe::operation(6),3); }
        if ($mode === 19) { $members[0] = new \type_model\Lifecycle_Member(4,1,null,3); $members[] = new \type_model\Lifecycle_Member(3,0,null,3); }
        if ($mode === 20) { $role = 4; $members[0] = new \type_model\Lifecycle_Member(3,0,null,4); }
        return \type_model\Lifecycle_Operation::source($type_id,$link,$role,$members,$repeat,$convention,$body);
    }
    public static function lifetime(\scpp\Json_View $row, int $changed_role, bool $reverse): \type_model\Lifetime_Contract {
        $policy = new \type_model\Lifetime_Policy();
        $policy->copy = $row->at(0)->integer(); $policy->cleanup = $row->at(1)->integer();
        $policy->construction = $row->at(2)->integer(); $policy->assignment = $row->at(3)->integer(); $policy->expiring = $row->at(4)->integer();
        $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        for ($index = 1; $index < 6; $index++) {
            $role = $index; if ($reverse) { $role = 6 - $index; }
            $required = false;
            if ($role === 1) { $required = (int)$policy->construction === 2; }
            elseif ($role === 2) { $required = (int)$policy->cleanup === 1; }
            elseif ($role === 3) { $required = (int)$policy->copy === 2; }
            elseif ($role === 4) { $required = (int)$policy->expiring === 3; }
            else { $required = (int)$policy->assignment === 2; }
            if ($required) {
                $link = 'role' . $role; if ($role === $changed_role) { $link = 'changed'; }
                $operations[] = \type_model\Lifecycle_Operation::runtime('p','id' . $role,$link,'ccc',$role);
            }
        }
        return new \type_model\Lifetime_Contract($policy,$operations);
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index); $equal = $fixture->member('equal')->boolean(); $matches = true;
            if ($fixture->member('kind')->text() === 'operation') {
                $a = Probe::operation($fixture->member('left')->integer()); $b = Probe::operation($fixture->member('right')->integer());
                $matches = ($a !== $b) && (\type_model\Lifecycle_Contracts::operation($a,$b) === $equal);
                if (!\type_model\Lifecycle_Contracts::operation($a,$a)) { $matches = false; }
            } else {
                $left = Probe::lifetime($fixture->member('a'),0,false);
                $right = Probe::lifetime($fixture->member('b'),$fixture->member('change')->integer(),$fixture->member('reverse')->boolean());
                $matches = ($left !== $right) && (\type_model\Lifecycle_Contracts::same($left,$right) === $equal);
                if (!\type_model\Lifecycle_Contracts::same($left,$left)) { $matches = false; }
            }
            echo $matches ? "true\n" : "false\n";
        }
    }
}
