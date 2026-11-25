<?php
declare(strict_types=1);
namespace prepare_backend;

/** Explicit provenance only; verification and export publication belong to their producers. */
final class Backend_Configuration
{
    public function __construct(
        public readonly string $backend_key,
        public readonly string $target_triple,
        public readonly string $data_layout,
        public readonly string $cpu,
        public readonly string $features,
        public readonly string $abi_key,
        public readonly string $runtime_key,
    )
    {
        if (($backend_key === '') || ($target_triple === '') || ($data_layout === '') || ($abi_key === '') || ($runtime_key === '')) {
            throw new \InvalidArgumentException('Backend configuration requires explicit backend, target, layout, ABI and runtime identities');
        }
    }
}
