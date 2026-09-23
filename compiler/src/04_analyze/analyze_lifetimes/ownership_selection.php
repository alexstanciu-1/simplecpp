<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Select semantic ownership prerequisites; the queue owns execution and acceptance. */
final class Ownership_Selection {
    public function __construct(private readonly \check_bodies\Body_Set $bodies, private readonly ?\type_model\Type_Store $types) {}
    public static function lifecycle_key(int $kind, int $type): string {
        \type_model\Lifecycle_Roles::require_role($kind); $prefix = 'construct:';
        if ($kind === \type_model\LIFECYCLE_COPY) { $prefix = 'copy:'; }
        else if ($kind === \type_model\LIFECYCLE_MOVE) { $prefix = 'move:'; }
        else if ($kind === \type_model\LIFECYCLE_ASSIGN) { $prefix = 'assign:'; }
        else if ($kind === \type_model\LIFECYCLE_DESTROY) { $prefix = 'destroy:'; }
        return $prefix . $type;
    }
    private static function owns(\type_model\Named_Definition $definition): bool {
        $resource = $definition->ownership; if ($resource === null) { return false; }
        return $resource->path_count() !== 0;
    }
    public function select(): array /** vector<Ownership_Request> */ {
        $out /** vector<Ownership_Request> */ = []; $members /** hash<int> */ = [];
        foreach ($this->bodies->bodies() as $body) {
            if (($body->input->owner->owner_symbol_id !== 0) && ($body->entry_parameter_count() > 0)) {
                $members['' . $body->local_type_for(1) . ':' . $body->input->owner->symbol_id] = $body->callable_id;
            }
        }
        $types = $this->types;
        if ($types !== null) {
            for ($offset = 0; $offset < $types->type_count(); $offset++) {
                $type = $offset+1; $definition = $types->type_by_id($type)->definition;
                if ($definition === null) { continue; }
                if ($definition->representation->kind() !== \type_model\REPRESENTATION_STRUCTURE) { continue; }
                if (!Ownership_Selection::owns($definition)) { continue; }
                $life = $definition->lifetime;
                if ($life === null) { throw new \LogicException('Owning aggregate has no lifetime contract'); }
                $policy = $life->policy(); $children /** hash<int,int> */ = [];
                for ($ordinal = 0; $ordinal < $definition->representation->member_count(); $ordinal++) {
                    $child = $types->field_for($type,$ordinal)->type_id;
                    if (Ownership_Selection::owns($types->definition_for_type($child))) { $children[$ordinal] = $child; }
                }
                $roles /** vector<int> */ = [\type_model\LIFECYCLE_DEFAULT,\type_model\LIFECYCLE_COPY,\type_model\LIFECYCLE_ASSIGN,\type_model\LIFECYCLE_MOVE,\type_model\LIFECYCLE_DESTROY];
                foreach ($roles as $role) {
                    if ($role === \type_model\LIFECYCLE_COPY) { if ((int)$policy->copy === \type_model\COPY_UNAVAILABLE) { continue; } }
                    if ($role === \type_model\LIFECYCLE_ASSIGN) { if ((int)$policy->assignment === \type_model\ASSIGNMENT_UNAVAILABLE) { continue; } }
                    if ($role === \type_model\LIFECYCLE_MOVE) { if ((int)$policy->expiring !== \type_model\EXPIRING_CONSTRUCT) { continue; } }
                    $body_id /** nullable<int> */ = null; $order = \type_model\Lifecycle_Roles::composition($role,false);
                    $member_roles /** hash<int,int> */ = [];
                    if ($life->has_operation($role)) {
                        $operation = $life->operation($role);
                        if (!$operation->imported) {
                            $order = $operation->order();
                            if ($operation->body_symbol_id !== 0) {
                                $key = '' . $type . ':' . $operation->body_symbol_id;
                                if (!isset($members[$key])) { throw new \LogicException('Missing checked source lifecycle body'); }
                                $body_id = $members[$key];
                            }
                            for ($i = 0; $i < $operation->member_count(); $i++) { $member = $operation->member_at($i); $member_roles[$member->index] = $member->kind; }
                        }
                    }
                    $ordered /** vector<Ownership_Child> */ = [];
                    if ((int)$order->member_kind !== \type_model\LIFECYCLE_NONE) {
                        foreach ($children as $ordinal => $child) {
                            $member_role = (int)$order->member_kind; if (isset($member_roles[$ordinal])) { $member_role = $member_roles[$ordinal]; }
                            $ordered[] = new Ownership_Child($ordinal,Ownership_Selection::lifecycle_key($member_role,$child));
                        }
                    }
                    $final_children /** vector<Ownership_Child> */ = [];
                    if ($order->reverse_members) { for ($i = q_count($ordered); $i > 0; $i = $i-1) { $final_children[] = $ordered[$i-1]; } }
                    else { $final_children = $ordered; }
                    $dependencies /** vector<string> */ = []; $callable = 0;
                    if (take_nullable($callable,$body_id)) { $dependencies[] = 'body:' . $callable; }
                    foreach ($final_children as $child) { $dependencies[] = $child->dependency; }
                    $subject = new Ownership_Lifecycle($type,$definition,$role,$final_children,$body_id);
                    $out[] = new Ownership_Request(Ownership_Selection::lifecycle_key($role,$type),null,$subject,$dependencies);
                }
            }
        }
        foreach ($this->bodies->bodies() as $body) {
            $dependencies = $this->body_dependencies($body); $owns = false;
            foreach ($body->type_dependencies() as $row) { $definition = $row->definition; if ($definition !== null) { if (Ownership_Selection::owns($definition)) { $owns = true; } } }
            if ($owns || (q_count($dependencies) !== 0)) { $out[] = new Ownership_Request('body:' . $body->callable_id,$body,null,$dependencies); }
        }
        return $out;
    }
    private function body_dependencies(\check_bodies\Checked_Body $body): array /** vector<string> */ {
        $keys /** hash<bool> */ = [];
        for ($i = 0; $i < $body->call_count(); $i++) {
            $callee = $this->bodies->for_callable($body->call_for($i+1)->target_callable_id);
            if ($callee !== null) {
                $parameters = Resource_Locations::parameters($callee);
                $return_type = $callee->signature_for($callee->callable_id)->representation->signature_return();
                if ((q_count($parameters) !== 0) || Ownership_Selection::owns($callee->definition_for($return_type))) { $keys['body:' . $callee->callable_id] = true; }
            }
        }
        for ($i = 0; $i < $body->value_count(); $i++) {
            $value = $body->value_for($i+1);
            if (!Ownership_Selection::owns($body->definition_for($value->type_id))) { continue; }
            if ($value->kind === \check_bodies\VALUE_CALL_RESULT) { $keys['destroy:' . $value->type_id] = true; }
            if (($value->kind === \check_bodies\VALUE_DEFAULT_CONSTRUCT) || ($value->kind === \check_bodies\VALUE_RECORD_DEFAULT)) {
                $keys['construct:' . $value->type_id] = true; $keys['destroy:' . $value->type_id] = true;
            }
        }
        for ($i = 0; $i < $body->statement_count(); $i++) {
            $statement = $body->statement_at($i);
            $return_copy = ($statement->return_mode === \check_bodies\RETURN_COPY_CONSTRUCT) || ($statement->return_mode === \check_bodies\RETURN_MOVE_CONSTRUCT);
            $write_copy = ($statement->write_kind === \check_bodies\WRITE_COPY_CONSTRUCT) || ($statement->write_kind === \check_bodies\WRITE_COPY_ASSIGN);
            if (!$return_copy && !$write_copy) { continue; }
            $type = $body->value_for($statement->value_id)->type_id;
            if (!Ownership_Selection::owns($body->definition_for($type))) { continue; }
            if ($return_copy) { $role = \type_model\LIFECYCLE_COPY; if ($statement->return_mode === \check_bodies\RETURN_MOVE_CONSTRUCT) { $role = \type_model\LIFECYCLE_MOVE; } $keys[Ownership_Selection::lifecycle_key($role,$type)] = true; }
            if ($write_copy) { $role = \type_model\LIFECYCLE_COPY; if ($statement->write_kind === \check_bodies\WRITE_COPY_ASSIGN) { $role = \type_model\LIFECYCLE_ASSIGN; } $keys[Ownership_Selection::lifecycle_key($role,$type)] = true; $keys['destroy:' . $type] = true; }
        }
        $out /** vector<string> */ = []; foreach ($keys as $key => $present) { $out[] = '' . $key; } return $out;
    }
}
