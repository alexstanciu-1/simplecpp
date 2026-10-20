# Native prelowering and accounting acceptance — 2026-10-20
Doc Status: planning

## Configuration and reproduction

The compiler executable is built at **O2 without debug information**. Generated
application, middleware, layout probes and application runtime objects stay at
**O0 without debug information or application PCH**. The compiler's own PCH is a
separate build input and remains enabled. The existing native validation driver
replaces the normal CLI entrypoint; it selects the public lowering option on the
existing Compiler owner and uses the production publication/build owners.

```sh
python3 compiler/my-try/tools/native_validate.py \
  --target-checkout "$PWD" --candidate-revision 069ade60 \
  --results /tmp/prelower-native-compiler-20261020 \
  --no-stan --types-only --prelower-accounting --jobs 12 --no-pch \
  --compiler-optimization O2 --resume
# The same accounting gate can also run standalone:
python3 compiler/my-try/tests/prelower_accounting_acceptance.py \
  --native-compiler /tmp/prelower-native-compiler-20261020/phpp/.prism/build/main \
  --results /tmp/native-prelower-accounting-20261020
```

Omit `--resume` for a fresh compiler workspace. The ordinary project release
preset is O3, so the validation-only `native_compiler_o2.py` proxy records the
actual Clang arguments and selects exactly O2. This changes no production build
preset or generated compiler source. An initial release-preset attempt was
stopped when that mismatch was identified; it is not O2 evidence.

## Proof boundary

The accounting acceptance can still run in its original PHP/public-CLI mode.
With `--native-compiler`, it additionally compares ordinary and concrete
publication manifests and every artifact byte against PHP output, publishes and
builds both modes natively, verifies cold/warm behavior, and uses the existing
complete application-closure audit. The native request driver repeats concrete
preparation/emission in the same compiler and requires identical output.

An unsupported callable-storage request must fail explicitly, leave publication
non-ready and be refused by the native builder. A valid concrete program must
repair publication and execute successfully. Runtime PCH must still reject for
template-free application input. These checks do not introduce a fallback or
expand the supported runtime surface.

This is acceptance evidence, not a controlled compiler-performance benchmark.
Native compiler execution, generated-program execution and PHP/native artifact
parity are separate checks. Legacy STAN and parked LLVM remain excluded.

## Findings during native execution

The first concrete artifact-assembly run exposed reads of missing hash entries
through `?? ''`. The structural target emitted `.at(...)` before coalescing.
The existing specialization worker now initializes its local assembly buckets
explicitly and guards optional reads; output identities and ordering are unchanged.

The native proof also required two harness corrections: a never-published failed
revision reports “no ready revision,” and accounting's PHP/native publishers must
select the same runtime modules. Artifact bytes already matched before the module
selection correction; the differing manifest listed extra bootstrap runtime sources.
The request harness now accepts an explicit module list, retaining its existing
single-module Chapter 14 requests.

## Result

**PASS.** Final evidence: `logs-17` under the compiler workspace above; candidate
base `069ade60` plus the source/harness hashes retained here. All 233 compiler/proof
PHP files convert and the native O2 executable builds. The final no-change compiler
rebuild reports no work.

- Native ordinary and prelowered accounting: **998 passed, 0 failed**, identical output.
- PHP/native publication: identical manifests and every artifact byte in both modes.
- All **16** prelowered application units: zero template declarations and zero
  Clang class/function instantiations across the complete transitive input.
- Native strict rejection, failed-publication refusal, PCH rejection, repair and
  same-session concrete replay all pass.
- Both native warm builds: **0 compiled, 0 linked, 0 PCH built**.

`summary.json` records the final executable hash and acceptance result.
`compiler_configuration.json` verifies actual O2/no-debug object commands across
resumed builds. `ordinary_o2_gate.json` is the earlier ordinary Chapter 14 gate
(14 parity projects, 28 runs, 27 standalone headers and publication/cache scenarios),
before the focused artifact-assembly repair and module-list harness extension.
The final native accounting gate covers ordinary and concrete emission after those
changes. Build warnings remain. This does not certify every runtime/template
combination or repeat the full historical native language suite.
