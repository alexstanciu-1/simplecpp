# Native compiler refresh — 2026-10-20
Doc Status: planning

Candidate base: `2a302b1a`, with authored portability repairs captured by the
source hashes. This rebuilds the compiler through the existing PHP → PHS → C++
workflow; generated output is not edited. The native validation driver replaces
the normal entrypoint.

## Reproduction

```sh
python3 compiler/my-try/tools/native_validate.py \
  --target-checkout "$PWD" --candidate-revision 2a302b1a \
  --results /tmp/prelower-native-compiler-20261020 \
  --no-stan --types-only --chapter14 --jobs 12 --no-pch
```

Use `--resume` only for an existing result directory. Every attempt retains its
own logs. The successful resumed gate, its exact source hashes and separate
PHP-host prelowering regression results are retained alongside this note.

## Repairs

- Explicit supported nullable annotations, typed collection literals and locals,
  checked enum-occurrence downcasts, and an integer Storage index boundary.
- Declaration-local enum-name helpers, following the existing Node_Kind_Name
  pattern. The structural generator cannot reflect foreign enum declarations.
- Explicit initialization and scope for negative defaults, nullable branches and
  do-loop state. Required typed parameters replace unsupported method defaults;
  payload/payload_for follows the existing representation/representation_for API.
- Explicit branches for nullable guards. Native overloaded boolean operators can
  evaluate both operands; PHP short-circuit assumptions caused empty-nullable
  access in six record-related artifact cases after compilation first succeeded.

All changes stay in existing compiler owners. No type-family special cases,
new retained facts, runtime semantics or prelowering fallback are introduced.

## Scope and evidence

Local raw logs: `/tmp/prelower-native-compiler-20261020/logs-13/`, including earlier
attempts and a keep-going Ninja diagnostic pass. The first successful build was
attempt 12; its execution gate exposed the nullable guard issue. Attempt 13
contains the repaired native execution gate. The final no-change compiler build
is checked by the same validator.

The compiler build uses the existing default O0/debug/PCH configuration. The
Chapter 14 fixtures explicitly test both PCH and no-PCH. This is correctness
evidence, not a compile-time benchmark or a cumulative language certification.
STAN and parked LLVM are explicitly skipped; warnings remain. FPM is unavailable
in the sandbox and the shared runner uses its documented CLI fallback.

The separate O0 prelowering regression uses the PHP-hosted compiler. It does not
certify native execution of the prelowering engine; that remains a distinct
follow-up proof. The native artifact gate currently exercises ordinary emission.

## Result

Native gate: **PASS** (233 converted files, 14 artifact parity projects,
28 generated executions, 27 standalone headers, 5 incremental and 7 provider
build scenarios). The no-change compiler rebuild reports no work.

Separate O0 prelowering regression: **all eight phases PASS**, including metadata,
strict contracts, public options, provider/PCH boundaries, header closure and
allocation/copy counters, source lifecycle, layout caching and accounting.
Accounting ordinary/prelowered behavior is identical: 998 checks passed, zero
failed; all 16 prelowered application units have zero template declarations and
instantiations. Five focused PHP suites also pass (runtime inventory/bindings/
protocols and guarded operators/mixed).

The native executable is
`/tmp/prelower-native-compiler-20261020/phpp/.prism/build/main` (validation request
driver, not the normal CLI entrypoint). Its SHA-256 is in `summary.json`.
