<?php
declare(strict_types=1);
namespace resolve_types;
/** Storage operation semantics after accepted family/element materialization; no ABI inference. */
final class Storage_Signatures {
    public static function signature(Callable_Input $input, Definition_View $definitions): Signature_Request {
        $instance = $input->instance;
        if ($instance === null) { throw new \LogicException('Storage callable requires its concrete instance'); }
        if ($instance->argument_count() !== 1) { throw new \LogicException('Storage callable requires one element argument'); }
        $argument = $instance->argument_at(0);
        if ($argument->value !== null) { throw new \LogicException('Storage element must be a type argument'); }
        $function = $input->owner->provider()->storage_function(); $family = $function->family; $element = $argument->type;
        $owner = $definitions->find_type(Storage_Definitions::name($family,$element),string_byte_from_int(0).'element_storage');
        if ($owner === null) { throw new \LogicException('Storage callable requires its prepared concrete owner'); }
        $storage = $owner->element_storage;
        if ($storage === null) { throw new \LogicException('Stale storage callable owner'); }
        if (($storage->family !== $family) || ($storage->element !== $element)) { throw new \LogicException('Stale storage callable owner'); }
        $role = $function->role; $receiver_passing = \type_model\PASS_BORROW_MUTABLE;
        if ($role === \type_model\STORAGE_COUNT) { $receiver_passing = \type_model\PASS_BORROW_CONST; }
        $parameters /** vector<\type_model\Named_Definition> */ = [$owner]; $passing /** vector<int> */ = [$receiver_passing];
        if ($role === \type_model\STORAGE_ALLOCATE) { $parameters[] = $family->counter; $passing[] = \type_model\PASS_VALUE; }
        else if ($role === \type_model\STORAGE_PUSH) {
            $parameters[] = $element; $passing[] = Storage_Signatures::element_passing($element);
        } else if ($role === \type_model\STORAGE_TRANSFER) { $parameters[] = $owner; $passing[] = \type_model\PASS_BORROW_MUTABLE; }
        $result = $family->void_type; if ($role === \type_model\STORAGE_COUNT) { $result = $family->counter; }
        return new Signature_Request($input,0,$result,$parameters,$passing);
    }
    /** Check fixed provenance without constructing a second request. */
    public static function matches(Signature_Request $request, Definition_View $definitions): bool {
        $instance = $request->input->instance;
        if ($instance === null) { return false; }
        if ($instance->argument_count() !== 1) { return false; }
        $argument = $instance->argument_at(0); if ($argument->value !== null) { return false; }
        $symbol = $request->input->owner; if ($symbol->is_source()) { return false; }
        if ($symbol->provider()->kind() !== \collect_symbols\PROVIDER_STORAGE_FUNCTION) { return false; }
        $function = $symbol->provider()->storage_function(); $family = $function->family; $role = $function->role;
        $count = 1; if (($role === \type_model\STORAGE_ALLOCATE) || ($role === \type_model\STORAGE_PUSH) || ($role === \type_model\STORAGE_TRANSFER)) { $count = 2; }
        if (($request->return_annotation_id !== 0) || ($request->parameter_count() !== $count)) { return false; }
        $owner = $request->parameter_at(0); $storage = $owner->element_storage;
        if ($storage === null) { return false; }
        if (($storage->family !== $family) || ($storage->element !== $argument->type)) { return false; }
        if ($definitions->find_type($owner->name,$owner->namespace_name) !== $owner) { return false; }
        $result = $family->void_type; $receiver_passing = \type_model\PASS_BORROW_MUTABLE;
        if ($role === \type_model\STORAGE_COUNT) { $result = $family->counter; $receiver_passing = \type_model\PASS_BORROW_CONST; }
        if (($request->definition !== $result) || ($request->passing_at(0) !== $receiver_passing)) { return false; }
        if ($role === \type_model\STORAGE_ALLOCATE) { return ($request->parameter_at(1) === $family->counter) && ($request->passing_at(1) === \type_model\PASS_VALUE); }
        if ($role === \type_model\STORAGE_PUSH) { return ($request->parameter_at(1) === $argument->type) && ($request->passing_at(1) === Storage_Signatures::element_passing($argument->type)); }
        if ($role === \type_model\STORAGE_TRANSFER) { return ($request->parameter_at(1) === $owner) && ($request->passing_at(1) === \type_model\PASS_BORROW_MUTABLE); }
        return true;
    }
    private static function element_passing(\type_model\Named_Definition $element): int {
        if ($element->representation->kind() === \type_model\REPRESENTATION_INTEGER) { return \type_model\PASS_VALUE; }
        return \type_model\PASS_BORROW_CONST;
    }
}
