<?php
declare(strict_types=1);
namespace resolve_types;
/** Complete aggregate permissions/plans; no syntax evaluation, ABI or per-element expansion. */
final class Lifecycle_Composition {
    private static function life(\type_model\Type_Store $types, int $id): \type_model\Lifetime_Contract {
        $definition = $types->definition_for_type($id);
        if ($definition->lifetime === null) { throw new \LogicException('Aggregate member requires a value lifetime'); }
        return $definition->lifetime;
    }
    public static function derive(\type_model\Type_Store $types, int $id, array $members /** vector<int> */,
        int $repeat, Lifecycle_Bodies $bodies): \type_model\Lifetime_Contract {
        if (!$types->is_declared($id)) { throw new \LogicException('Aggregate lifecycle requires declared identity'); }
        if ($repeat < 0) { throw new \InvalidArgumentException('Negative aggregate repetition'); }
        $construct = \type_model\CONSTRUCTION_ZERO; $copy = \type_model\COPY_VALUE;
        $assignment = \type_model\ASSIGNMENT_VALUE; $cleanup = \type_model\CLEANUP_NONE;
        foreach ($members as $type) {
            $policy = Lifecycle_Composition::life($types,$type)->policy();
            if (((int)$policy->construction === \type_model\CONSTRUCTION_UNAVAILABLE) || ($construct === \type_model\CONSTRUCTION_UNAVAILABLE)) { $construct = \type_model\CONSTRUCTION_UNAVAILABLE; }
            elseif ((int)$policy->construction === \type_model\CONSTRUCTION_CONSTRUCT) { $construct = \type_model\CONSTRUCTION_CONSTRUCT; }
            if (((int)$policy->copy === \type_model\COPY_UNAVAILABLE) || ($copy === \type_model\COPY_UNAVAILABLE)) { $copy = \type_model\COPY_UNAVAILABLE; }
            elseif ((int)$policy->copy === \type_model\COPY_CONSTRUCT) { $copy = \type_model\COPY_CONSTRUCT; }
            if (((int)$policy->assignment === \type_model\ASSIGNMENT_UNAVAILABLE) || ($assignment === \type_model\ASSIGNMENT_UNAVAILABLE)) { $assignment = \type_model\ASSIGNMENT_UNAVAILABLE; }
            elseif ((int)$policy->assignment === \type_model\ASSIGNMENT_CALL) { $assignment = \type_model\ASSIGNMENT_CALL; }
            if ((int)$policy->cleanup === \type_model\CLEANUP_DESTROY) { $cleanup = \type_model\CLEANUP_DESTROY; }
        }
        if (((int)$bodies->constructor !== 0) && ($construct !== \type_model\CONSTRUCTION_UNAVAILABLE)) { $construct = \type_model\CONSTRUCTION_CONSTRUCT; }
        if ((int)$bodies->copy !== 0) {
            if ($construct === \type_model\CONSTRUCTION_UNAVAILABLE) { throw new \RuntimeException('Custom copy construction requires supported field default initialization'); }
            $copy = \type_model\COPY_CONSTRUCT;
        }
        if ((int)$bodies->destructor !== 0) { $cleanup = \type_model\CLEANUP_DESTROY; }
        if ((int)$bodies->assignment !== 0) { $assignment = \type_model\ASSIGNMENT_CALL; }
        if (($construct === \type_model\CONSTRUCTION_ZERO) && ($cleanup === \type_model\CLEANUP_DESTROY)) { $construct = \type_model\CONSTRUCTION_CONSTRUCT; }
        $expiring = \type_model\EXPIRING_VALUE;
        if (((int)$bodies->destructor !== 0) || ((int)$bodies->copy !== 0) || ((int)$bodies->assignment !== 0)) {
            $expiring = $copy === \type_model\COPY_UNAVAILABLE ? \type_model\EXPIRING_UNAVAILABLE : \type_model\EXPIRING_COPY;
        } else {
            foreach ($members as $type) {
                $member = Lifecycle_Composition::life($types,$type)->policy();
                if ((int)$member->expiring === \type_model\EXPIRING_UNAVAILABLE) { $expiring = \type_model\EXPIRING_UNAVAILABLE; break; }
                if (((int)$member->expiring === \type_model\EXPIRING_CONSTRUCT)
                    || (((int)$member->expiring === \type_model\EXPIRING_COPY) && ((int)$member->copy === \type_model\COPY_CONSTRUCT))) { $expiring = \type_model\EXPIRING_CONSTRUCT; }
            }
        }
        $policy = new \type_model\Lifetime_Policy(); $policy->construction=$construct; $policy->copy=$copy;
        $policy->assignment=$assignment; $policy->cleanup=$cleanup; $policy->expiring=$expiring;
        $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        if ($cleanup === \type_model\CLEANUP_DESTROY) { $operations[]=Lifecycle_Composition::operation($types,$id,$members,\type_model\LIFECYCLE_DESTROY,$repeat,(int)$bodies->destructor); }
        if ($copy === \type_model\COPY_CONSTRUCT) { $operations[]=Lifecycle_Composition::operation($types,$id,$members,\type_model\LIFECYCLE_COPY,$repeat,(int)$bodies->copy); }
        if ($construct === \type_model\CONSTRUCTION_CONSTRUCT) { $operations[]=Lifecycle_Composition::operation($types,$id,$members,\type_model\LIFECYCLE_DEFAULT,$repeat,(int)$bodies->constructor); }
        if ($assignment === \type_model\ASSIGNMENT_CALL) { $operations[]=Lifecycle_Composition::operation($types,$id,$members,\type_model\LIFECYCLE_ASSIGN,$repeat,(int)$bodies->assignment); }
        if ($expiring === \type_model\EXPIRING_CONSTRUCT) { $operations[]=Lifecycle_Composition::operation($types,$id,$members,\type_model\LIFECYCLE_MOVE,$repeat,0); }
        return new \type_model\Lifetime_Contract($policy,$operations);
    }
    public static function complete_operation(\type_model\Type_Store $types, int $id, int $role): ?\type_model\Lifecycle_Operation {
        \type_model\Lifecycle_Roles::require_role($role); $definition=$types->definition_for_type($id);
        if ($definition->representation->kind() !== \type_model\REPRESENTATION_STRUCTURE) { throw new \LogicException('Complete source operation requires a record'); }
        $life=Lifecycle_Composition::life($types,$id);
        if ($life->has_operation($role)) { $existing=$life->operation($role); if ($existing->imported) { return null; } return $existing; }
        $policy=$life->policy(); $primitive=false;
        if ($role===\type_model\LIFECYCLE_DEFAULT) { $primitive=(int)$policy->construction===\type_model\CONSTRUCTION_ZERO; }
        elseif ($role===\type_model\LIFECYCLE_COPY) { $primitive=(int)$policy->copy===\type_model\COPY_VALUE; }
        elseif ($role===\type_model\LIFECYCLE_ASSIGN) { $primitive=(int)$policy->assignment===\type_model\ASSIGNMENT_VALUE; }
        elseif ($role===\type_model\LIFECYCLE_DESTROY) { $primitive=(int)$policy->cleanup===\type_model\CLEANUP_NONE; }
        if (!$primitive) { return null; }
        $members /** vector<int> */ = [];
        for ($index=0;$index<$definition->representation->member_count();$index++) { $members[]=$types->field_for($id,$index)->type_id; }
        return Lifecycle_Composition::operation($types,$id,$members,$role,0,0);
    }
    /** Select one constituent per direct field/element type; repeat stays compact. */
    private static function operation(\type_model\Type_Store $types, int $id, array $members /** vector<int> */,
        int $role, int $repeat, int $body): \type_model\Lifecycle_Operation {
        $plan /** vector<\type_model\Lifecycle_Member> */ = []; $order=\type_model\Lifecycle_Roles::composition($role,$body!==0);
        $member_role=(int)$order->member_kind;
        if ($member_role!==\type_model\LIFECYCLE_NONE) {
            foreach ($members as $index=>$type) {
                $life=Lifecycle_Composition::life($types,$type); $selected=$member_role;
                if ($member_role===\type_model\LIFECYCLE_MOVE) { if ((int)$life->policy()->expiring===\type_model\EXPIRING_COPY) { $selected=\type_model\LIFECYCLE_COPY; } }
                if ($role===\type_model\LIFECYCLE_DESTROY) { if (!$life->has_operation($selected)) { continue; } }
                $plan[]=Lifecycle_Composition::member($life,$type,$index,$selected);
            }
        }
        $ordered /** vector<\type_model\Lifecycle_Member> */ = [];
        if ($order->reverse_members) { $position=q_count($plan); while ($position>0) { $position=$position-1; $ordered[]=$plan[$position]; } }
        else { $ordered=$plan; }
        $link='__scpp_lifecycle_type_' . $id . '_' . \type_model\Lifecycle_Roles::name($role);
        return \type_model\Lifecycle_Operation::source($id,$link,$role,$ordered,$repeat,'ccc',$body);
    }
    private static function member(\type_model\Lifetime_Contract $life, int $type, int $index, int $role): \type_model\Lifecycle_Member {
        if ($life->has_operation($role)) { return new \type_model\Lifecycle_Member($type,$index,$life->operation($role),$role); }
        return new \type_model\Lifecycle_Member($type,$index,null,$role);
    }
}
