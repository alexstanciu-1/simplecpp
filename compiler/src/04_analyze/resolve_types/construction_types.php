<?php
declare(strict_types=1);
namespace resolve_types;
/** Worker-owned diagnostic channel over an immutable accepted type snapshot.
 * Resolving a type does not grant default-construction or lifetime permission.
 */
final class Construction_Types {
    /** Internal assembly: use for_snapshot() to bind the fixed semantic readers. */
    public function __construct(private readonly Type_Resolution $snapshot, private readonly \instantiate\Bindings $reader) {}
    public static function for_snapshot(Type_Resolution $snapshot): Construction_Types {
        $annotations = new Annotation_Types($snapshot->names,new Definition_View($snapshot->catalog,$snapshot->types));
        return new Construction_Types($snapshot,new \instantiate\Bindings($annotations,$snapshot->catalog,$snapshot->instances->view()));
    }
    public function diagnostic(): ?Annotation_Diagnostic { return $this->reader->annotations->diagnostic(); }
    public function resolve(Callable_Input $input, int $name_node): int {
        if (!$input->owner->is_source()) { throw new \LogicException('Construction lookup requires a source owner'); }
        $this->reader->annotations->bindings($input->owner);
        if ($input->instance !== null) {
            if ($this->snapshot->instances->context_for($input->callable_id) !== $input->instance) { throw new \LogicException('Construction lookup requires its accepted instance'); }
        }
        $definition = Annotation_Types::definition($input->context(),$name_node,'construction',$this->reader);
        $id = $this->snapshot->types->find_type($definition->name,$definition->namespace_name);
        if ($id === 0) { $this->reader->annotations->fail($input->owner,$name_node,'Construction type has no prepared value contract'); }
        if ($this->snapshot->definition_for($id) !== $definition) { $this->reader->annotations->fail($input->owner,$name_node,'Construction type has no prepared value contract'); }
        return $id;
    }
}
