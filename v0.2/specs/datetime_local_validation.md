# Local datetime completion validation

Doc Status: supporting
Date: 2026-10-29
Contract: [datetime_module.md](datetime_module.md)
Backlog: [runtime_datetime_local_batch.md](planning/runtime_datetime_local_batch.md)

## Delivered slice and provenance

The native module now publishes all eleven donor module functions, including
format_local, format_local_now and parse_common_local. Reuse was reviewed against
`runtime/include/modules/datetime/datetime.cpp` at repository revision `4db60739d`.
The earlier native slice is documented in [datetime_validation.md](datetime_validation.md).

The token switch, escaping and host-local conversion model are retained. A local
refactor gives UTC/local parsing one calendar-field validator, and formatting/failure
checks one checked host-conversion owner. ASCII to_chars replaces stream formatting;
year arithmetic widens before adding 1900. The valid -1 fix checks host-normalized
calendar fields rather than rejecting the sentinel unconditionally. Host DST gap
normalization and overlap selection remain the approved policy.

Rust operation plans now own argument vectors, including format_local's two inputs.
Both parsers share result transport; selected native functions still require explicit
type/carrier dependencies. The metadata binding is now native_datetime and records
local_dst=host_normalize_and_select. No source callable catalog or language adapter
was changed. Core/module dependency direction is unchanged.

## Evidence

- Release CTest: 37/37 passed.
- UBSan CTest: 37/37 passed.
- Final local tests after strengthening year-width, result-independence and error
  allocation checks: 3/3 in both Release and UBSan.
- Rust tooling debug suite: 24 passed; focused preparation regression passed after
  adding exact two-argument and unsupported DST-policy checks. Cargo workspace
  Release tests/doctests passed; Clippy with warnings denied passed.
- Focused ASan+UBSan generated local consumer passed in three separate UTC0,
  EST5EDT,M3.2.0/2,M11.1.0/2 and UTC+11 processes (Clang/libstdc++, leak detection
  disabled). Primary Release/UBSan builds use libc++ on Linux. No Windows, 32-bit or
  LeakSanitizer proof is claimed.
- Source-only export/build tests passed: isolated source without legacy runtime,
  freshness/rebuild invalidation, production tests-disabled builds, datetime module
  enabled demo, no datetime symbols in core, module disable and subsequent core build.
  The demo now also exercises common parsing and local U formatting.
- Frozen G1/scalar expected artifacts remain unchanged. Only metadata input identities
  were updated; existing rejection and artifact contracts pass.

## Behavior and boundaries

`tests/datetime/local.cpp` covers all supported tokens, escaped/literal/trailing bytes,
embedded NUL, locale independence, empty formatting, current-time formatting, owned
results, allocation failure, valid -1 in multiple zones, invalid calendar fields and
host conversion failure. UTC0 checks local year 10000 formatting to distinguish its
minimum-width Y token from the fixed UTC format's bounded year domain.

The explicit POSIX DST rule uses no timezone database. A spring-gap test admits either
host normalization direction; an autumn overlap test admits either actual occurrence
and verifies the original wall-clock fields. The runtime never sets TZ or locale.
UTC Z input yields the same epoch in all three local environments.

Standalone publication tests now include local-format-only, local-now-only and
local-parse-only consumers alongside previous clock/UTC subsets. Each compiles and
links without the old tree; required carriers are absent from format-only requests.
Public headers stay template-free; unsupported native arguments and unrelated
functions are rejected. Shared UTC tests continue to pass after the ownership refactor.

## Cost observation and remaining scope

A local Release observation measured 10,000 borrowed native local parses at 42,452 us
with zero C++ allocations. A successful public local parse allocates once for the
existing result_int64 owner. Host C-library allocations/state are outside that counter;
these observations are not a performance comparison. Format buffers and public strings
own allocations; injected failure propagates without changing input bytes.

Host representability and DST choice remain platform-dependent. Invalid or unsupported
local parsing returns structured errors; formatting throws for host conversion failures.
Error-message text is diagnostic, not a frozen ABI. PHP compatibility wrappers,
source compiler integration, explicit timezone selection and calendar arithmetic remain
outside this slice. Packed storage for mixed/dynamic is the next proposed core review;
traversal remains deferred under RT-DEBT-01.
