# Datetime module reuse batch

> Status update (2026-10-29): Rust implementation and toolchain retired.
> This note is historical context; current owners are the PHP compiler and PHP API
> generator. See [compiler status](../../compiler/README.md).

Doc Status: planning
Status: D0–D6 complete; eight-function module delivered and validated.
Date: 2026-10-29
Donor reviewed at: 46d3ebf31 (repository revision, not a claim that this commit introduced datetime).

## Recommendation

Port the eight clock/sleep/UTC functions first through the sole Rust API generator,
with a separately selectable native datetime target. This is a useful module reuse
proof without requiring new value families. The user approved this scope; D0–D6 are complete; see [validation evidence](../datetime_validation.md).

## Authority and inspected evidence

- Top-level contracts: `specs/builtins/datetime/dt_*.md`, especially UTC parse/format,
  monotonic precision and sleep; `specs/runtime_module_metadata.md`.
- Layering: `specs/architecture/runtime_layering.md` requires modules to depend on
  core and forbids module-to-language dependencies.
- Donor: `runtime/include/modules/datetime/datetime.hpp` and `.cpp`.
- Registration: `runtime/metadata/modules/datetime.json` contains 11 functions and
  depends on core. This is source callable metadata, distinct from v0.2 concrete
  native publication metadata; do not introduce a second source catalog authority.
- Regression ideas: `tests/runtime/native/test_datetime.cpp`, strict datetime PHS
  tests and the per-builtin test matrices. Legacy native tests also import PHP
  wrappers; adapt relevant assertions without importing that dependency graph.

These paths are repository-root relative. Existing top-level contracts govern;
this planning note neither expands source support nor changes semantics.

## Delivered surface

Preserve the runtime namespace `scpp::dt` and donor function names. Use explicit
concrete v0.2 types, borrowed const input references and owned return values.

| Function | Native result / input | Existing v0.2 dependency |
| --- | --- | --- |
| now_unix_seconds | int64 | int64 |
| now_unix_millis | int64 | int64 |
| monotonic_millis | int64 | int64 |
| monotonic_micros | uint64 | publish existing uint64 |
| monotonic_nanos | uint64 | publish existing uint64 |
| sleep_millis | void; int64 input | int64 |
| format_iso_utc | string; int64 input | string, int64 |
| parse_iso_utc | result_int64; string input | string, result_int64, error_t |

The 3 remaining module functions (format_local, format_local_now, parse_common_local)
are deferred. PHP time/date/strtotime adapters and microtime are separate surfaces;
microtime is core/PHP-owned rather than one of the 11 datetime module registrations.
No hashes, mixed/dynamic, records, resources, callback machinery or new carriers are
needed. No compiler integration or general plugin/package manager is included.

## Reuse and issues to resolve

Reuse clock selection, calendar validation, fixed-position parsing, UTC conversion
platform branches and error separation. Replace old wrappers/native_value string
copies with concrete v0.2 adapters and borrowed byte views. Keep native chrono/time
and implementation templates private. Preserve module/core dependency direction.

1. UTC formatting promises exactly 20 ASCII bytes independent of locale. Donor uses
   locale-sensitive stream formatting and an unchecked int64-to-time_t cast. Review
   and repair these locally instead of copying them unchanged.
2. Four-digit year domain is not fully explicit. Parser currently admits year 0000;
   formatter can emit more than four year digits on hosts supporting distant dates.
   Approved clarification: accept years 0000–9999 and throw on formatting
   outside that range or host UTC representability. Preserve host failure behavior;
   do not promise support for all pre-epoch timestamps on every platform.
3. Duration casts may overflow before the donor's count check. Check conversions at
   their owning boundary, reuse private checked numeric helpers where applicable,
   and test synthetic edge inputs rather than relying on the current host clock.
4. Local-time deferral is deliberate: mktime(-1) conflates failure and a valid
   timestamp; DST gaps/overlaps and host normalization need a separate reviewed
   policy. These are observed donor risks, not new language rules.
5. Parsing invalid text returns result_int64 error; formatting representability
   failures throw. Allocation failures propagate. Do not collapse these into false
   or empty strings. Preserve explicit-length handling of embedded NUL.

## Ownership and bounded implementation

Rust preparation owns selected module operations, concrete signatures, dependencies
and artifact/build inventory. Emission consumes those facts. The reusable datetime
C++ provider owns behavior. CMake links an optional datetime target to the concrete
core, with no reverse dependency. Publish only selected capabilities; reject unknown
operations and unmet type/carrier dependencies before emission.

First examine the current publication model for a small module-owned extension.
Do not build a general module framework speculatively. If that requires a wide
refactor across compiler/catalog/build owners, report the boundary before proceeding.
Existing source metadata remains untouched until source compiler integration is in scope.

## Completed backlog

| ID | Action | Acceptance |
| --- | --- | --- |
| D0 | Resolve UTC year/range clarification and freeze selected surface | Contract amendments, donor provenance, explicit exclusions |
| D1 | Add bounded Rust module preparation and publication | Requested-operation and dependency rejection, deterministic inventory |
| D2 | Adapt private clock/sleep provider | Clock distinction, checked conversion, nonpositive sleep, precision contracts |
| D3 | Adapt UTC parser/formatter | Epoch, pre-epoch where supported, leap rules, year bounds, malformed/NUL inputs, locale-independent output |
| D4 | Wire separate native module target and standalone consumer | Core-only build has no datetime dependency; selected export links without old tree |
| D5 | Prove contracts and cost | Release/UBSan, supported focused sanitizers, error/allocation paths, borrowed parse inputs and representative timings |
| D6 | Consolidate docs and commit after validation | Existing generation parity retained, freshness/export checks, recorded platform limits |

Clock tests must not assume wall-clock monotonicity, unique successive readings or
precise scheduler timing. Sleep tests use bounded small requests. Avoid concurrent
process-global timezone/locale mutation; use isolated test processes when needed.

## Approved decisions

- Eight-function scope; local-time functions deferred.
- Four-digit UTC years 0000–9999; format failure outside that range.
- First consumer is native; source compiler integration is a later batch.

The implementation goal covers D0–D6. Successful validation is followed by a commit
under the user's standing preference; pushing remains a separate requested action.
