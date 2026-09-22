<?php
declare(strict_types=1);

/*
 * Role: Resource obligations and call effects, separate from object lifecycle.
 * Used by: package import; checked calls; allocation flow analysis
 * Flow: validated provider contracts -> fixed semantic effects -> per-body state facts
 */
namespace type_model;

/** An allocation owner starts empty and must be empty at every normal object lifetime end. */
enum resource_kind: string {
    case allocation = 'allocation';
}

/** These effects concern the allocation, never the lifetime of its containing object. */
enum allocation_effect_kind: string
{
    case acquire = 'acquire';
    case release = 'release';
    case transfer = 'transfer';
    case inspect = 'inspect';
    case mutate = 'mutate';
    case observe = 'observe';
}

/** Zero-based semantic parameter positions; transfer requires a distinct, same-type destination. */
final class allocation_effect
{
    public function __construct(public readonly allocation_effect_kind $kind, public readonly int $owner,
        public readonly ?int $destination = null)
    {
        if (($owner < 0) || (($kind === allocation_effect_kind::transfer) !== ($destination !== null))
            || (($destination !== null) && (($destination < 0) || ($destination === $owner)))) {
            throw new \InvalidArgumentException('Invalid allocation effect positions');
        }
    }
}
