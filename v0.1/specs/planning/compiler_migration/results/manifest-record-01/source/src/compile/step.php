<?php
declare(strict_types=1);

/*
 * Role: Small contracts for one compiler step execution.
 * Used by: main_<process>.php owners; Compiler_Session; Phases
 * Call map (ordered caller-driven lifecycle):
 *   Step::init(); Runnable_Step::run() [if supported]; Step::finalize(); Step::result()
 * Queries: status() and supports_run() are always available.
 */

namespace compile;
// <scpp-imports>
use function scpp\json_quote as json_quote;
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

enum step_status
{
    case created;
    case ready;
    case running;
    case processed;
    case finished;
    case failed;
}

// Markers preserve each process's concrete output and storage APIs.
interface Step_Result {
}

interface Step_Store {
}

interface Step
{
    public function init(): void;
    public function finalize(): void;
    public function result(): Step_Result;
    public function status(): step_status;
    public function supports_run(): bool;
}

interface Runnable_Step {
    public function run(): void;
}

interface Store_Providing_Step {
    public function store(): Step_Store;
}
