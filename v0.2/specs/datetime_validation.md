# Datetime batch validation

Doc Status: supporting
Date: 2026-10-29
Contract: [datetime_module.md](datetime_module.md)
Backlog: [runtime_datetime_batch.md](planning/runtime_datetime_batch.md)

## Reuse and ownership review

Donor files: `runtime/include/modules/datetime/datetime.hpp` and `.cpp` at repository
revision `46d3ebf31`. Retained concepts are system/steady clock selection, fixed-width
ASCII parsing, Gregorian leap validation, UTC calendar arithmetic, checked host UTC
conversion branches and result-versus-exception separation.

The new private provider removes dependencies on old wrappers and copies of input
strings. Duration conversion widens before scaling; long sleeps use bounded chunks.
UTC formatting checks both the four-digit year range and host representation, and
writes fixed ASCII fields instead of locale-sensitive streams. The result/string
adapters are emitted by Rust; private provider code contains no language layer or
source compiler dependency. There is one module build helper and no reverse edge
from core to module. This stayed within the agreed runtime/tooling ownership area.

The top-level UTC contracts now explicitly state years 0000–9999. Existing source
callable metadata remains unchanged; native publication does not imply compiler
integration. Local-time functions and compatibility wrappers remain deferred.

## Validation evidence

- Full default Release CTest: 33/33 passed before adding the final timezone fixture.
- Full UBSan CTest: 33/33 passed. Final datetime-focused runs, including timezone and
  locale facets plus additional rational-conversion boundaries: 5/5 in both builds.
- Optional production module enabled in a fresh build: nine relevant datetime and
  existing subset-publication tests passed. The latter keep their explicit base
  publication fixtures rather than accidentally inheriting a selected module.
- Rust tooling debug tests: 24 passed; Cargo workspace Release tests and doctests
  passed. Clippy with warnings denied and rustfmt checks passed.
- Focused AddressSanitizer+UBSan native-provider and generated-consumer runs passed
  with Clang/libstdc++, leak detection disabled for this environment. Primary builds
  use Clang/libc++; no Windows or 32-bit execution is claimed.
- `tests/build_cutover.py`: isolated v0.2 source export builds and tests without the
  old runtime; unchanged-build and dependency invalidation proofs pass. Production
  builds with tests disabled pass both without and with datetime. The datetime demo
  prints `2024-02-29T12:34:56Z`; core symbols contain no datetime definitions. Disabling
  the option removes the production datetime target and the core build still passes.
- Frozen G1 and scalar input fingerprints were updated for optional datetime metadata.
  Existing artifact hashes and rejection expectations were independently compared
  with HEAD and retained unchanged. Existing runtime-family parity tests pass.

## Focused behavioral proofs

`tests/datetime/semantics.cpp` exercises signed/unsigned duration boundaries, scaling
whose intermediate exceeds 64 bits, truncation, negative-to-unsigned rejection and
bounded sleep chunk selection without issuing enormous waits. Calendar tests include
known epoch values, year bounds, leap centuries/year zero, invalid components, every
embedded-NUL position, trailing text and broad UTC round trips on the supported host.

`consumer.cpp` exercises all eight concrete functions, exact result identities,
monotonic nondecrease without a resolution guarantee, nonpositive/short sleeps,
owned success/error results, allocation failures and input preservation. A custom
locale time-formatting facet and a separate process with `TZ=UTC+11` cannot affect
fixed UTC output. Wall-clock calls are not asserted to be monotonic.

`publication.py` separately generates and compiles signed-clock, uint64-only-clock,
format-only and parse-only consumers. It checks prepared selections, standalone
public include closure without templates, freshness, and compiler rejection of
unselected/local-time calls and implicit native arguments. Rust preparation tests
cover missing types/carriers, unknown/duplicate/empty operations, missing/invalid
policy and header conflicts.

## Costs and limits

A local Release observation: 100,000 native parses took 1,448 microseconds and zero
C++ allocations. The public successful parser borrows its input and allocates once
for the existing result_int64 storage. Clock/small-sleep calls allocate no C++ objects
in the measured fixture. Error messages and formatted strings own allocations; failure
propagates. These are focused observations, not comparative performance claims.

The format path retains host UTC conversion limits; parser calendar support is broader
on hosts with restricted time_t/gmtime ranges. Host library internal state is not
measured by the C++ allocation counter. LeakSanitizer and unexecuted platforms remain
outside these proofs. Huge sleep requests retain their huge duration; chunking avoids
conversion overflow, it does not cap the requested wait.
