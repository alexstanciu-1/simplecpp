<?php
declare(strict_types=1);

/*
 * Role: Verified backend target configuration.
 * Used by: LLVM_Toolchain; LLVM_Backend; lowering and native build
 * Flow: fixed inputs -> owned contracts -> read-only consumers
 */

namespace prepare_backend;
// <scpp-imports>
use function scpp\string_byte_from_int as string_byte_from_int;
use function scpp\enum_name as enum_name;
use function scpp\lock_empty as lock_empty;
use function scpp\lock_try as lock_try;
use function scpp\lock_release as lock_release;
use function scpp\lock_transfer as lock_transfer;
use function scpp\process_spawn as process_spawn;
use function scpp\process_poll as process_poll;
use function scpp\process_output as process_output;
use function scpp\process_stop as process_stop;
use function scpp\process_close as process_close;
use function scpp\sequence_map as sequence_map;
use function scpp\sequence_filter as sequence_filter;
use function scpp\keyed_map as keyed_map;
use function scpp\keyed_filter as keyed_filter;
use function scpp\string_byte_len as string_byte_len;
use function scpp\string_byte_starts_with as string_byte_starts_with;
use function scpp\string_byte_ends_with as string_byte_ends_with;
use function scpp\string_utf8_is_valid as string_utf8_is_valid;
use function scpp\string_codepoint_at as string_codepoint_at;
use function scpp\compat\substr as substr;
use function scpp\compat\strpos as strpos;
use function scpp\compat\strrpos as strrpos;
use function scpp\same_exception as same_exception;
use function scpp\string_byte_at as string_byte_at;
use function scpp\take_nullable as take_nullable;
use function scpp\take_false as take_false;
use function scpp\take_bool as take_bool;
use function scpp\compat\str_starts_with as str_starts_with;
use function scpp\compat\str_ends_with as str_ends_with;
use function scpp\compat\strlen as strlen;
use function scpp\string_byte_slice as string_byte_slice;
// </scpp-imports>

/**
 * @compiler-api Fixed backend facts shared with lowering, emission and native build.
 * All readonly fields are readable: keys identify exact revisions; target/layout,
 * CPU and features guide output. Consumers must not supply local target defaults.
 * Only backend preparation establishes verified support; construction does not.
 */
final class backend_configuration
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
