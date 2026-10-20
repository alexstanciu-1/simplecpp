# Nonempty runtime ABI initializer checkpoint
Doc Status: supporting

Date: 2026-10-19. Branch: `v0.2/prelower_templates`. Base: `627a35d1`.

The opt-in runtime ABI backend now emits nonempty list and keyed initializers
through the existing selected factory, prepared entry conversions, operation
requests and native layout cache. `CPP_Data_Construction` owns one shared sequence
for direct and ABI emission. `CPP_Initializer_Buffer` generates a nonmovable inline
owner of the original runtime `initializer_buffer<Element>`; the engine has no
container-family cases. Buffer length is a call argument, not specialization identity.
No semantic registry, AST pass or runtime storage algorithm was added.

Buffer construction precedes all entries. Within each entry, the key is evaluated
and converted before the value; append precedes destruction of those local snapshots.
The selected factory runs after all entries. List append retains the native lvalue
copy and keyed append retains its aggregate prvalue. Original runtime RAII handles
partial-buffer destruction, including conversion, append and factory exceptions.

## Reproduction and evidence

```bash
python3 compiler/my-try/tests/prelower_expressions.py --initializers --results FRESH_DIRECTORY
```

The fixture uses compiler-generated programs, registered provider metadata and a
tracked payload. Coverage includes list/keyed initialization, runtime integer wrappers,
nested containers, a 40-entry heap-backed input, ordered key/value side effects,
per-entry allocation/copy/move/live-object traces, copy-failure sweeps through every
successful copy position, failure to allocate the buffer before evaluating entries,
checked key/value conversion failures, and duplicate-key factory failure cleanup.

Incremental checks cover no-op fragment identity, cached fragment replay after an
entry edit, removal and restoration, emission failure recovery, configuration
switching without semantic version changes, fresh output equality and warm native
builds with zero compilations/links. Changing literal length preserves every
specialization artifact.

At O0, O2 and ASan/UBSan (`-O1 -g0 -fno-elide-constructors`), the successful
initializer workload has the same counters and evaluation trace in both modes:

| Allocations | Bytes | Frees | Payload constructions | Copies | Moves | Destructions |
|---:|---:|---:|---:|---:|---:|---:|
| 39 | 15,488 | 39 | 41 | 159 | 48 | 248 |

These include required runtime copies and heap allocations; initializer transport
adds none. Copy failures, buffer allocation failure and duplicate-key failure also
have exact parity. All tracked payloads are destroyed after each scenario.

**Diagnostic limitation:** checked integer conversion throws `scpp::runtime_error`,
which unconditionally captures a native stack trace. Additional bridge frames can
increase diagnostic allocations, including at `-g0`. Debug-symbol settings do not
disable this capture. We do not claim zero extra allocations for these diagnostics.
Changing capture policy belongs to a separate runtime/debugging decision.

The fixture therefore also runs with test-only `backtrace` interposition returning
zero frames. All counters, including conversion-failure allocations, must then
match exactly across both backends and all three build configurations. Unfiltered
runs still execute libc capture, retain all counters in a separate summary, and
must match payload lifetime and evaluation traces; conversion diagnostic allocation
counts are checked for balanced frees but not equality. No runtime source is changed.

See [isolated-capture counters](summary.json) and
[unfiltered counters](unfiltered-summary.json). Raw build/run logs are in
`/tmp/prelower-initializers-04`. The existing initialization regression ran in
`/tmp/prelower-initializer-regression` (five PHP suites, 48 generated programs and
one native input-lifetime proof). Scoped conversion of the four touched backend
files and accounting recipe replay ran in `/tmp/prelower-initializer-portability`.
The original expression proof ran in `/tmp/prelower-expressions-initializer-regression`.
The shared runner used CLI fallback because FPM sockets were unavailable.

## Scope

This proves the selected initialization contract, not all template operations or
whole-accounting compile-time performance. Source-type template layout bootstrap,
broader call/reference/receiver transport and iteration remain deferred. Runtime
error diagnostic capture is unchanged. The compiler itself was not rebuilt natively.

[Owning guide](../../../../compiler/my-try/docs/architecture/cpp_template_prelowering.md)
