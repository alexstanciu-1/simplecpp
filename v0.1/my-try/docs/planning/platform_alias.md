# V-D09 / V-D12 qualified aliases and platform contracts
Doc Status: supporting

Completed 2026-10-20 on dev002. The two approved issues close: **33/64 fully closed
(51.5625%)**, up from 31/64 (48.4375%), **+3.125 percentage points**. Zero partial
repairs and 31 other original issues remain. This is issue-count progress, not an
engineering-effort estimate.

## Contracts and ownership

Core exposes `microtime(): string` and `microtime(bool): float`, both free and
through the explicit `php::` qualifier. The zero-argument form returns formatted
wall-clock time; true returns epoch seconds. False preserves the existing
`std::logic_error`, rather than changing the static return type or runtime behavior.
The [normative contract](../../../../specs/builtins/datetime/microtime.md) makes no
monotonic-time promise.

The runtime function metadata's optional `qualified_aliases` indexes the original
owned callable identities. Shared expression preparation observes type lookup
before selecting an alias. Any visible type takes precedence; its missing method
cannot fall back to the alias. Empty lookup observations make later type additions
invalidate unchanged callers. The existing argument preparation and backend
binding paths remain authoritative. No fake `php` type or C++ emitter name switch
was added. Catalog reset reinstalls indexes from retained metadata.

Filesystem restores `fs_is_windows(): bool` and the
[v0.1.77 normative contract](../../../../specs/builtins/filesystem/fs_is_windows.md).
It reports whether the target C++ filesystem's preferred separator is backslash,
not the generator host. It performs no path classification or normalization.

The [getenv contract](../../../../specs/builtins/process/getenv.md) now explicitly
preserves absent false, present empty strings and copied byte values. Embedded NUL
names throw `scpp::ValueError` before lookup, preventing prefix truncation.
Lookup/case behavior otherwise remains the target platform's environment behavior.
Runtime edits are confined to this validation and the restored filesystem query.

## Verification

The snapshot is `/home/alex-ai/platform-alias/20261020-01` on
`alex-ai@dev002.travelfuse.ro`, based on `d1d1750e`. See
[results](platform_alias_results.json), [audit](platform_alias_audit.json),
[raw evidence](platform_alias_evidence.json.gz), and the
[initial](platform_alias_source_initial.json) / [final](platform_alias_source_final.json)
source manifests. All 6,311 snapshot files passed both remote and local hash audits.
The accepted suites contain 132 commands and 37 native executions, including
baseline capture. Local implementation/test bytes match the final remote snapshot.

- Baseline captures all five missing source forms (including the unchanged strict
  fixture) and records that NUL names were not rejected. Baseline source-driver
  additions followed initial snapshot creation; its baseline branch is retained
  in the final driver. Historical failures are not candidate passes.
- All four microtime call forms execute with exact static result types. Native
  checks validate eight-digit fractional text, epoch bounds, preserved false-mode
  exceptions and once-only bool argument evaluation with source mutation.
- The original `strict_strings_005_php_microtime_static_call.phs` executes unchanged
  and prints `ok`. Separate source function/type shadowing and a synthetic
  `clock_probe` qualifier execute, proving the mechanism is not a `php` name branch.
- Thirteen invalid source forms reject with no completed C++ output. Nine invalid
  alias configurations reject without replacing startup configuration, including
  duplicate/reserved/path qualifiers and aliases on member contracts.
- One retained cycle adds a source `php` type, removes its method, retries without
  edits, removes the type, and compares repaired/no-op/fresh output. The unchanged
  caller executes both as the runtime alias and the source method returning 7.
- Native platform checks verify Linux false and the target separator expression.
  Synthetic caller-policy tests exercise both branches and preserve POSIX
  backslashes. These are not Windows execution evidence.
- Isolated child environments prove missing, empty, populated and copied values,
  NUL rejection despite an existing prefix name, and once-only name effects.
- Production filesystem/core publication builds and executes cold and warm. The
  warm build compiles and links nothing. Focused regressions cover callables,
  observed lookup, preparation recovery, member ownership, methods/static members,
  environment/shell behavior, filesystem operations and runtime snapshots.

The new native runtime/source probes use ASan/UBSan. Existing provider regressions
retain their own runner settings. All execution was on dev002 with the shared
12-slot runner; PHP CLI fallback was used because CLI/FPM versions differ. No full
matrix or native rebuild of the compiler itself was run. Three initial candidate
attempts caught test-harness mistakes (configuration lifecycle, multi-file entry
selection and C++ observer names/includes); their logs remain in the evidence.
No production workaround was added for those mistakes.

## Remaining boundaries

No blocker remains for V-D09/V-D12. Other production aliases, general namespace
support, actual Windows execution and broader process/shell semantics remain
separate. Original ledger findings and earlier progress counts remain historical.

Implementation commit: `2f4f1bd1` — qualified runtime aliases and platform contracts.
