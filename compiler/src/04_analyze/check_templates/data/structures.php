<?php
declare(strict_types=1);
namespace check_templates;
/** Exact template owner and fixed binding snapshot selected for symbolic checking. */
final class Definition_Task {
    public function __construct(public readonly \collect_symbols\Symbol_Record $owner,
        public readonly \resolve_symbols\Symbol_Resolution $bindings) {
        if ($bindings->owner!==$owner) { throw new \LogicException('Template task requires its exact source binding snapshot'); }
    }
}
/** Private expression result; read-only state follows the source location through projection. */
final class Expression_Type {
    public function __construct(public readonly ?Type_Term $type, public readonly bool $readonly) {}
}
