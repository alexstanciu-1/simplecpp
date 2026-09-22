<?php
declare(strict_types=1);

/*
 * Role: Concrete preparation requests, separate from retained type/instance rows.
 * Used by: Concrete_Preparation; Preparation_Queue
 * Flow: discovered dependency -> selected fixed task -> accepted type or callable
 */
namespace resolve_types;

/** The concrete task categories coordinated before signature/local preparation. */
enum preparation_kind: string {
    case application = 'application';
    case record = 'record';
    case member = 'member';
}

/** One exact request; waiting and ready membership belong to the preparation owner. */
final class preparation_request {
    public function __construct(public readonly string $key, public readonly preparation_kind $kind,
        public readonly \instantiate\application_task|record_task|\instantiate\member_task $task)
    {
    }
}
