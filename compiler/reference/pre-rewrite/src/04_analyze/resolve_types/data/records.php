<?php
declare(strict_types=1);

/*
 * Role: Structural definition work units and private outputs.
 * Used by: Record_Preparation; Record_Join
 * Flow: fixed inputs -> owned contracts -> read-only consumers
 */

namespace resolve_types;

use type_model\record_declaration;

/** Selected source or normalized provider declaration and immutable language definitions. */
final class record_task
{
    public function __construct(public readonly \collect_symbols\symbol_record|record_declaration $input,
        public readonly \type_model\Type_Catalog $catalog, public readonly \resolve_symbols\Resolution_Set $names,
        public readonly ?\instantiate\instance_context $instance = null,
        public readonly ?\instantiate\Instance_View $instances = null,
        public readonly ?Definition_View $definitions = null,
        public readonly ?\collect_symbols\Symbol_Store $symbols = null)
    {
    }
}

/** Private normalized result retaining its exact selected task. */
final class record_result {
    public function __construct(public readonly record_task $task, public readonly record_declaration $declaration)
    {
    }
}
