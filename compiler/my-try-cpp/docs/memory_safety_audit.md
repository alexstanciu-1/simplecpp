# Memory safety audit — 2026-10-26
Doc Status: supporting

Scope: handwritten compiler startup, discovery, source loading, tokenization,
in-memory command inputs, concrete middleware ownership, and the directly used
runtime JSON ownership and task joining paths. This is not a whole-runtime audit
or a proof of memory safety. The initial audit made no production changes; the two API findings were subsequently hardened as described below.

## Remediation

Both API findings below are resolved. `token_sequence::source()` now returns an
owning `token_buffer` snapshot by value. It shares immutable bytes, survives a
temporary sequence or replacement of an lvalue sequence, and costs one small
wrapper allocation per call without copying the source bytes. No production
scanner hot-path call sites currently use this accessor.

`token_jobs::execute()` now rejects null callbacks before dispatch, including
empty batches. Regression coverage checks both batch cases, then successfully
runs the retained work with a valid callback. Source ownership tests exercise
the original temporary-reference reproducer and lvalue replacement.

Remediation verification: all 13 tests passed normally (3.08 seconds) and under
ASan/UBSan with leak detection (7.76 seconds). The runtime package was reused;
normal dependency packages were unchanged by the sanitizer build.

Other borrowed APIs and the coordinator-only concurrency contract are unchanged.

## Original findings (resolved)

### P2: Borrowed token source can outlive its generation

`middleware/src/token_sequence.cpp:113` returns `const token_buffer &` into owned
`token_data`, without restricting calls on temporaries. This compiles:

```cpp
const auto &source = scan_tokens(token_buffer::from_text(string("echo 1;"))).source();
source.present();
```

The sequence dies at the first semicolon; binding the returned reference does not
extend its lifetime. ASan reports heap-use-after-free at the second statement.
Current production callers were not found to use this pattern. The vulnerability
is an exposed C++ lifetime hazard, not a demonstrated source-input exploit.

Recommendation: return an owning token_buffer snapshot when ownership must escape,
or ref-qualify the borrowing accessor and provide an owning overload for rvalues.
Ref-qualification alone does not protect a reference retained past destruction or
replacement of an lvalue owner. File-record and batch-result references likewise
need a clear lifetime contract; stable addresses during growth do not survive
owner destruction/reset.

### P2: Null scanner callback is accepted until worker invocation

`middleware/src/token_jobs.cpp:45` validates jobs but not the function pointer;
line 67 invokes it. An otherwise valid nonempty batch with `execute(1, nullptr)`
crashes under ASan. The normal compiler always supplies `scan_tokens`, so this
is an API validation gap rather than a current CLI failure.

Recommendation: reject a null callback before starting work, including for an
empty batch, and add a focused rejection test.

## Remaining contracts and limits

- Lifecycle mutation is coordinator-owned, not synchronized against arbitrary
  callers. Concurrent reset, scan/load/tokenize, notifications or readers can
  race if callers violate that ownership. The header explicitly documents
  startup/reset exclusion; the same rule should cover all mutations. No such
  overlap was found in the current command flow. ASan/UBSan do not detect data
  races; The subsequent ThreadSanitizer checks below passed for the supported coordinator-owned flow.
- Literal/raw-byte middleware APIs rely on valid borrowed C++ pointers and lengths.
  Current compiler call sites use literals or bounded live buffers. These APIs
  are trusted native boundaries, not generally memory-safe pointer interfaces.
- Stdin enforces the 32-bit source limit but can still consume substantial memory
  before rejection. File loading can read a source larger than the tokenizer
  supports before the token builder rejects it. These are memory-budget concerns,
  not observed buffer overflows. Allocation-failure injection was not performed.

## Positive evidence

- Compact-token narrowing follows range checks. Span checks use subtraction to
  avoid overflow; scanner lookahead returns a distinct EOF sentinel.
- Immutable token generations retain their source snapshot. Copies share ownership;
  replacing a file buffer does not invalidate a separately retained generation.
- JSON nodes retain shared document storage; views do not borrow parser locals.
- Copy-disabled opaque owners and copy/swap implementations avoid accidental
  duplicate deletion. Private candidates protect publication on ordinary failures.
- Read/token callbacks use distinct work records; ordinary worker exceptions and
  partial thread-launch failures join started workers before propagation.
- Stdin reads bounded chunks, preserves embedded NUL and checks read errors.

## Verification

Fresh ASan/UBSan run with leak detection: **13/13 tests passed**, 8.01 seconds.
A deterministic stress probe scanned **20,000 random byte strings**, lengths
0–1023, seed 12345, with no sanitizer findings. This supplements existing grammar,
length-limit and lifecycle tests; it is not exhaustive fuzzing.

Two intentionally invalid API-use probes reproduced the findings above. Probe
source and logs are in `build/memory-audit/`: `probe.cpp`, `borrow.log`,
`callback.log`, `random.log`, `suite.log`. These are audit build artifacts, not
files emitted by the stdin/inline-source commands.

Conclusion: the exercised pipeline passes the current dynamic checks, but the
C++ API does not enforce memory safety for every legal caller. The two identified API hazards have now been hardened; other native lifetime
and coordinator contracts remain caller responsibilities.


## ThreadSanitizer follow-up — 2026-10-26

An isolated Clang ThreadSanitizer build instrumented the compiler, middleware and
linked runtime libraries. **12/12 compatible tests passed** in 9.24 seconds,
including discovery, source reading, token lifecycle rollback, normal worker
execution and opt-in profiled execution. No race reports were emitted.

The scalar allocation-counter test was excluded: its replacement `new/delete`
operators conflict at link time with TSan's interceptors. It remains covered by
the normal and ASan/UBSan suites. No race suppressions were added.

All three sample projects also passed discovery, loading and tokenization at
**16 jobs**, once in normal mode and once with profiling: six successful runs,
no TSan reports. The large sample reached 16 active tokenizer callbacks in both
modes. Sanitizer timings are not performance benchmarks. These finite executions
do not prove that every possible schedule is race-free, and do not make concurrent
lifecycle mutations supported.

Reproduce the isolated suite from the repository root:

```sh
python3 compiler/my-try-cpp/tools/sanitizers.py --mode thread
```

Then run each sample through the instrumented benchmark, using `normal` and
`profile` in turn:

```sh
compiler/my-try-cpp/build/thread-sanitizer/compiler/tokenization-profile \
  compiler/my-try-cpp/samples/my-try/phpp 16 profile
```

Build products and evidence remain under `build/thread-sanitizer/`:
`validation.log` and `sample-validation.json`. The normal dependency packages
were fingerprinted unchanged; this build uses its own runtime and middleware
publications and does not touch the ASan/UBSan build directory.
