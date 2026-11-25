<?php
declare(strict_types=1);

/*
 * Role: Fixed inputs and accepted evidence for complete source lifecycle exports.
 * Used by: prepare_backend\Export_Verification; Export_Worker; Export_Join
 * Flow: current analysis associations -> private checks -> retained verification.
 */
namespace analyze_lifetimes;

/** No stores or mutable coordinator state enter an export worker. */
final class export_task {
    /** @param array<int, Analyzed_Body> $bodies Reachable custom bodies, keyed by concrete callable ID. */
    public function __construct(public readonly \type_model\source_lifecycle_operation $operation,
        public readonly \type_model\named_type_definition $definition, public readonly array $bodies,
        public readonly ?ownership_result $ownership)
    {
    }
}

/** Evidence is bound to the exact complete plan and current accepted analyses. */
final class export_verification {
    public function __construct(public readonly export_task $task)
    {
    }
}
