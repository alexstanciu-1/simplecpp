# Whole accounting app: concrete template-free C++ experiment
Doc Status: planning

2026-10-18. This converts the existing small accounting app's generated C++ in an
isolated directory. It does not modify the application source, generator, compiler
or production runtime. No lowering time is included in native compilation results.

[Measured timing table](RESULTS.md) · [Converted entry point](converted/main.cpp) ·
[Concrete representations](converted/concrete.hpp) · [Converter](convert.py)

## Scope

Input: `examples/accounting_workload/.prism/generated`, 15 C++ translation units and
associated headers. The conversion discovers and emits 44 concrete shared-handle
representations, 24 vector/hash representations and the concrete factories actually
requested by this application. Source hashes and the complete specialization inventory
are in manifest.json; `converted/` contains the reviewable native result.

All original accounting function/method bodies and checks are retained, with bounded
mechanical replacements for representation names, generic calls and initializers:

- Ordinary and explicitly int64 integers become one concrete signed-64 wrapper;
  this matches the two representations used by this app. Arithmetic preserves the
  original integer division/remainder and signed-native operations. Zero divisors
  still throw. Arbitrary widths and general numeric conversions are outside scope.
- Vectors emit typed arrays, copy/move operations, geometric growth, checked indexing
  and typed iterators. Hashes emit ordered entries and an open-addressed lookup index,
  including tombstones, removal and reinsertion. They are real hash lookups, not a
  linear-search substitution. Container storage copies independently; shared element
  copies preserve pointee identity.
- Shared handles use one concrete atomic reference-counted control block with a
  destruction callback. Per-class factories and admitted derived-to-base conversions
  are concrete functions. Original virtual methods and inheritance remain in C++.
- Strings are concrete owning byte buffers with copying, concatenation, equality,
  integer formatting and the used UTF-8 codepoint length operation. Invalid UTF-8
  falls back to byte length, as in the original runtime. This is not std::string's
  storage strategy; no runtime-throughput claim is made.
- Return-type identity tags become concrete empty tag structs. The one nonescaping
  local std::function becomes its directly stored, typed C++ closure. Capture-by-value
  and calls remain; general escaping/type-erased callables are not implemented.
- The generated, unused CLI argv local becomes a read-only argument view. This does
  not implement general mixed values. The app does not inspect or mutate argv.

There are no C++ templates in the converted application or its three newly compiled
support units. The preprocessed audit includes system-header exposure. Ordinary
C++ new/delete, exceptions, RTTI, virtual dispatch, classes and automatic cleanup
remain. Existing platform/libstdc++ binaries are still linked by the C++ driver;
“template-free” concerns these compilation inputs, not how platform libraries were
originally implemented.

This is a bounded generated-artifact converter, not a general C++ parser or a
production specialization pass. It fails on unknown selected generic forms, and
validation/auditing applies to this fixed accounting input. A future production
backend should consume prepared semantic facts rather than parse generated C++.

## Behavioral proof and limits

Every measured executable must produce exactly:

```
accounting workload: invoice/payment/trial-balance checks passed
CHECKS passed=998 failed=0
ACCOUNTING_WORKLOAD_PASS
```

The unchanged business checks cover money, allocation, dates, ledger validation,
sales, purchasing, inventory, depreciation policies, banking, closing and reporting.
Additional probes run against both the original runtime and concrete representations:
300-element vector/hash growth, independent copies and assignments, self-assignment,
key removal/reinsertion order, signed integer keys, aliasing, bounds failures,
shared pointee mutation/replacement, early returns, exception cleanup, zero division,
and valid/invalid UTF-8 length behavior.

ASan and UBSan instrument the full concrete app and support plus these probes.
An instrumented owner counter is zero after both the probes and the full application.
LeakSanitizer is disabled because prior checks established it is unavailable under
this environment's ptrace setup; zero shared-owner count is not a general leak proof.

The exact production runtime diagnostic metadata/exception class hierarchy is not
reproduced: local runtime_failure carries a reason string. Source checks comparing
business error codes remain intact. Weak references, runtime reflection/shared-self
queries, cyclic ownership handling, arbitrary allocation failures and concurrent
application mutation are not covered. No compatibility with existing runtime object
layouts or ABI is claimed. Both comparison builds disable call-depth guards.

## Native timing method

Clang 18, C++23, PIC, debug information, O0 and O2. Three serial native rebuilds per
variant/optimization level, with deterministic shuffled variant order. Warm filesystem;
objects are freshly compiled in each measured round, with no compiler/object cache.
The 15-file application partition and aggregate project-header exposure are retained
for both representations. No unity build or LTO is introduced.

Compared variants:

1. **original**: original generated C++ with a reusable runtime-only PCH, matching
   the existing runtime-PCH approach.
2. **eager**: the same C++ with `-fpch-instantiate-templates` during PCH creation/use.
   This tests one relevant optimization; it is not a claim of the best possible C++
   configuration or explicit-instantiation/PCH-codegen strategy.
3. **concrete**: converted C++ with no PCH. All three new support units are compiled
   and timed separately on every round. No template compilation is hidden in them.

Report application compilation separately from support compilation and linking.
The original runtime shared library is reused unchanged, with its hash recorded;
rebuilding the existing entire runtime is not part of these numbers. PCH creation
is recorded separately. Concrete full-native totals include all new support cost;
application-only numbers assume that support can be reused. Emission, dependency
checking, test execution and the source compiler's semantic work are excluded.

Serial totals measure native work for this execution schedule; they are not an
optimal Ninja-parallel build wall-time estimate. Small timing differences should not
be overinterpreted. The representation also simplifies unused runtime machinery and
changes storage implementations, so this is not an isolated measurement of template
syntax while holding every implementation detail fixed.

## Reproduce

Run from the repository root, with the existing generated accounting baseline and
runtime library available:

```
python3 specs/planning/results/accounting_template_free_2026_10_18/convert.py
python3 specs/planning/results/accounting_template_free_2026_10_18/validate.py
python3 specs/planning/results/accounting_template_free_2026_10_18/benchmark.py --native-debug --variants original eager concrete --output /tmp/accounting-debug-reproduction.json
```

`base.hpp`/`base.cpp` own the nongeneric scalar/string/shared-ownership support.
`convert.py` owns the experimental type inventory, concrete specialization emission
and bounded artifact transformation. `probes.cpp` supplies shared behavioral probes.
`validation_extra.json` records diagnostics and the per-unit template audit.
`results.json` contains commands, per-unit times, support times and exact app outputs.

Native debug information is now opt-in for this harness (`--native-debug`). The recorded historical `results.json` above used `-g`; the default output for new runs is `no_debug_results.json` so those results are preserved. Source diagnostics and runtime checks remain enabled.
