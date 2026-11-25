# Lowering audit of the 104,605-byte access-log application

On tf-dev002 the current application took 3.094 s to build from fresh compiler invocation through executable. Sharing the final call sequence in each report and comparing string fields directly with literal bytes took 0.910 s. This is an experiment on one application with unusually repetitive source. Splitting a single source into parallel native units remains deferred.

## What LLVM receives

The application unit is 364,986 bytes, plus a 205-byte entry adapter. In 31 report functions, 601 branch leaves call the same observation function. Within any one report the destination owner and the numeric event field are the same across leaves. The string key changes. The current lowering emits an immediately invoked lambda in each leaf. It retains the dashboard owner, constructs an owning key, reads the checked event field, and calls the observation function. Nested predicates contain 413 more lambdas that copy a string field, construct a literal string, and compare the two.

The table counts LLVM function definitions before and after ordinary optimization. An LLVM `invoke` is a call with an unwind destination. The counts indicate the amount of exception-aware control flow LLVM processes. They do not measure exceptions at runtime. See [LLVM exception handling](https://llvm.org/docs/ExceptionHandling.html).

| Emission | Definitions before → after | Invokes before → after | Application object text bytes |
| --- | ---: | ---: | ---: |
| Current | 1246 → 566 | 2463 → 2412 | 199244 |
| Block per statement call | 645 → 252 | 2463 → 2412 | 174380 |
| One call after each report's branches | 645 → 252 | 753 → 702 | 88108 |
| Direct byte comparison | 834 → 387 | 2050 → 1999 | 172204 |
| Shared call and direct byte comparison | 233 → 73 | 340 → 289 | 59932 |
| Borrow field, keep owning literal | 833 → 386 | 2050 → 1999 | 180204 |
| Ordinary shared helper | 648 → 255 | 669 → 619 | 82172 |
| Forced-noinline shared helper | 648 → 255 | 669 → 619 | 82172 |

## Build and execution measurements

| Series / variant | C++ KB incl. entry | PHP | Frontend | LLVM optimizer | LLVM codegen | Link | Invocation → EXE | Shared / full CPU ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| audit / baseline | 365.2 | 102 | 308 | 1314 | 1329 | 28 | 3094 | 0.686 / 4.031 |
| audit / blocks | 349.6 | 102 | 254 | 875 | 1167 | 28 | 2526 | 0.697 / 4.057 |
| audit / tail | 209.7 | 103 | 177 | 425 | 423 | 27 | 1245 | 0.684 / 4.051 |
| audit / literal | 315.4 | 102 | 242 | 1178 | 1163 | 27 | 2769 | 0.684 / 3.165 |
| audit / combined | 159.9 | 102 | 100 | 276 | 294 | 28 | 910 | 0.691 / 3.208 |
| followup / baseline | 365.2 | 103 | 312 | 1317 | 1269 | 28 | 3119 | 1.221 / 6.556 |
| followup / borrowed | 316.5 | 103 | 252 | 1188 | 1154 | 28 | 2734 | 1.214 / 6.011 |
| followup / helper | 216.1 | 104 | 178 | 405 | 422 | 29 | 1193 | 1.229 / 6.380 |
| followup / helper-noinline | 216.1 | 104 | 177 | 400 | 414 | 28 | 1195 | 1.225 / 6.404 |

Fresh PHP 8.5, Clang 18.1.3, libc++, mold, ordinary `-O2 -g0`, one application unit and the usual entry adapter. Five shuffled fresh builds per variant after warm-up. Times are in ms. Invocation → EXE includes PHP startup, lowering, probe transformation and publication, both object builds, and the link against the existing runtime archives. PHP is the compiler's internal interval. Frontend, optimizer and machine-code generation are from a separate traced application compilation. The link column is a separate five-run median of the native driver plus mold. These columns explain the elapsed time and have different measurement boundaries. The runner records link time within each build on future runs. See [Clang time tracing](https://clang.llvm.org/docs/UsersManual.html#cmdoption-ftime-trace).

Runtime values are median process CPU per in-memory workload pass across seven shuffled processes pinned to logical CPU 8, each with 24 measured passes. The shared corpus exercises the common access-log work. The full corpus also enables the dashboard targeted by the rewrites. Compare variants within each series because the two baseline execution times differed. In the first series, every variant requested 871,200 bytes in 12,697 calls to scalar operator new on the shared workload and 2,219,146 bytes in 52,324 calls on the full workload. This counter covers the application timing region and does not measure peak RSS.

## Which changes translate into compiler rules?

1. **Statement calls.** When the result is discarded and the generated snapshots belong to that statement, emit the ordered declarations followed by the call in a compound statement. Preserve scope and destructor timing. The block-only experiment keeps every construction, move and checked read. It isolates the compile cost of asking LLVM to inline 601 lambdas. Expression-valued calls and loop updates need their own placement/lifetime analysis. Start at [call staging in the C++ emitter](../../../compiler/05_backend/cpp/emit.php#L2949).
2. **String equality with a known literal.** For a concrete string field and a literal, borrow the field for the duration of comparison. The full byte-comparison probe checks length then compares exactly that many bytes. It avoids materializing both operands and preserves embedded NUL semantics. The field access still performs its owner check. The simpler follow-up borrows the field and keeps the existing owning literal and operator. A local differential check against the runtime equality operator also passed 1,089 pairs covering empty, short and long strings, embedded NUL, high bytes and same-length last-byte differences. Benchmark predicates themselves used short ASCII literals. A production implementation belongs with the typed string equality operation and its narrow runtime dependency. Verify representable length, evaluation count, temporary lifetime, and the source contract for any observable construction failures before extending eligibility. See [binary lowering](../../../compiler/05_backend/cpp/emit.php#L2557) and the [evaluation contract](../../../specs/expression_evaluation.md).
3. **Repeated terminal calls.** For the tail-join probe, each matching leaf selects a constant pointer and length. The common tail constructs the original arguments in order and calls once. Unmatched paths skip the tail. The ordinary-helper alternative shares the same setup among leaves by passing two existing owner references and inert literal data into one of three private typed helpers. Inside the helper the sequence remains retain dashboard, construct key, read checked event field, call. This avoids control-flow restructuring and almost matches the joined form on its own. For compiler support, require identical callee, conversions, ownership, continuation and cleanup boundaries, plus a proven lack of intervening effects. An early return, different cleanup scope, mutating argument or throwing expression moved across an effect can invalidate the transformation. Source value arguments require their left-to-right snapshots. See [call semantics](../../../specs/compiler_entry_v0_2.md#explicit-mutable-reference-parameters).

Every build matched the complete expected small, shared and full reports. A fourth deterministic, 1,500-record input checked the dashboard suffix against an independent calculation across status, latency and byte-count boundaries; the prefix of that fourth report was outside its oracle. These checks validate this fixture. The transformations recognize this emitted shape in the probe only. The run does not establish a general-purpose optimizer or the same improvement for other applications.

Reproduce from this directory with the matching staged runtime and samples using `python3 -B lowering_audit.py V02_ROOT lowering-audit.json --php /usr/bin/php8.5`. Add `--followup` and a separate output filename for the second series. [First series](../results/lowering-audit-dev002.json) · [Follow-up series](../results/lowering-followup-dev002.json) · [Separate link measurements](../results/lowering-link-dev002.json)
