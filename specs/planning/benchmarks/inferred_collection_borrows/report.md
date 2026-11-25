# Inferred collection borrows for count readers

Doc Status: planning
Measured on 2026-11-25 at `73d7e392e`. The experiments used isolated compiler copies.

## Proposed contract

By-value calls must preserve snapshots, source-visible copy and destruction effects, and failures from user code. A read-only body establishes only that the callee does not write its parameter. It does not establish that constructing and destroying the private parameter was unobservable.

The proposed additional permission is narrow. The compiler may omit the physical storage for a private collection copy when it proves that the only observable difference could be an allocation failure for that omitted storage. Allocation failures for operations that still execute must propagate normally. Without this permission, even the integer count-reader experiment can change program outcome under memory pressure. This permission was subsequently adopted in the [native reference safety contract](../../../native_reference_safety.md#private-collection-copies-at-calls).

Eligibility should come from the collection representation and its transitive key/value copy and destruction capabilities. Keep the copy if those effects are unknown. A nonthrowing destructor can still print, and a collection being countable says nothing about the effects of copying it. The first measured callers supplied one exact-type named local to a synchronous free function returning an integer. Additional arguments, general forwarding and arbitrary callbacks need separate proofs. [Destructor contract](../../../destructors_v0_2.md) · [Hash copy guarantees](../../../hash_slice.md) · [Native error boundary](../../../exceptions_v0_2.md)

## Prototype and cross-file recovery

The isolated compiler recognized a single return whose prepared operation was collection count and whose operand resolved to the by-value parameter. Eligible direct calls targeted a separate native entry accepting a const reference. The source function retained its ordinary by-value native entry for callable adaptation. Both the vector and hash used the existing count lowering.

The extra entry had a stable declaration across body edits. A provider edit from `return count($items);` to `clean($items); return count($items);` made the extra entry call the ordinary by-value implementation, creating a private copy. Restoring the reader selected the borrowed implementation again. Incremental and fresh artifacts matched at all three revisions. With a two-entry vector, both the direct call and typed callback returned 2, then 0, then 2. The caller retained both entries after each pair of calls. A separate two-parameter snapshot test passed the same collection in both positions, cleared the mutable argument and observed 2 through the by-value argument.

This approach avoids a new caller dependency on the callee body. It introduces an extra symbol for every matching declaration, including fallback-only functions. Code size, full-build cost, and alternative dependency-based lowering were outside this experiment. The prototype deliberately admitted effectful elements to test the limits of the body proof.

## Observable counterexamples

A native link-time probe counted the copy constructor for a live 4,096-entry vector or hash. Ordinary by-value calls made exactly one copy per invocation. Explicit and inferred borrows each made zero. Failing the next ordinary `operator new` at the native call boundary produced `std::bad_alloc` on the by-value path. Both borrowed calls succeeded. Source catch handles language exceptions. An uncaught standard exception can still change the executable's exit status.

A separate source program counted a vector of one inline record whose authored destructor printed `D`. The production compiler printed `D[1D]DD`. The deliberately unrestricted prototype printed `D[1]DD`. The missing destructor belongs to the private parameter copy. This difference occurs during ordinary successful execution and rules out using the callee proof alone.

## Measurement method

Native timings used dev002, CPU 23, Clang 18, libc++, mold, separate translation units, `-O2 -g0`, and no LTO. The harness called the generated provider functions on the same populated integer vector or integer-keyed integer hash. It checked the sum of all results. Each run rotated the order of the three forms, discarded one warm-up round and measured 15 rounds. Each round made 100,000 calls per form at 16 entries and 10,000 at 4,096. Copy counting and failure injection used a separate executable. The two timing runs used the same uninstrumented native binary.

Each cell below gives the two run medians in nanoseconds per call. Absolute timings varied between runs on the same pinned CPU.

| Reused input | By value | Explicit const reference | Inferred borrow |
| --- | ---: | ---: | ---: |
| Vector, 16 entries | 27.18 / 54.91 | 1.34 / 2.43 | 1.34 / 2.47 |
| Hash, 16 entries | 114.77 / 221.01 | 1.34 / 2.25 | 1.34 / 2.26 |
| Vector, 4,096 entries | 1,992 / 2,841 | 1.35 / 2.46 | 1.34 / 2.52 |
| Hash, 4,096 entries | 16,749 / 26,342 | 1.33 / 2.28 | 1.33 / 2.26 |

Preparation used PHP 8.5, Xdebug off, one pinned CPU, 16 alternating baseline/candidate pairs, 60 warm-up preparations and reused parsed bodies and published symbols. Each timed sample contained 6,000 body preparations, or 300 for the 100-assignment control. The first prototype repeated signature eligibility during body preparation. The reduced check used the prepared return shape and bound parameter identity, leaving signature checks at entry selection. Both variants added a temporary body flag.

| Function body | Initial paired median change | Reduced baseline / candidate µs | Reduced paired median change | Reduced paired IQR |
| --- | ---: | ---: | ---: | ---: |
| Single vector count | +11.47% | 10.12 / 10.85 | +6.65% | +5.21% to +9.85% |
| Single hash count | +13.29% | 10.11 / 10.78 | +7.12% | +5.52% to +8.73% |
| Unaffected single scalar return | +6.08% | 14.27 / 14.56 | +2.27% | +1.34% to +3.62% |
| Unaffected 100-assignment body | −0.09% | 389.55 / 388.91 | +0.30% | −0.10% to +1.58% |

These percentages describe re-preparation of one body. They exclude parsing, emission, native compilation and linking. The next performance question is whether the compiler can derive the count-reader fact on demand from the owning body's existing prepared return expression, avoiding an unconditional check on unrelated bodies. [Raw paired samples](dev002.csv)

## Production check

The implementation queries the callee's already-prepared return expression during emission. It adds no per-body preparation flag or traversal. The published integer strategy with trivial copy semantics supplies the element capability. Vector values and both hash keys and values must satisfy it. On dev002 the instrumented production-generated entries again made one copy per ordinary by-value call and zero for explicit and inferred borrowing on 4,096-entry integer collections. Only the ordinary by-value entry failed when its copy allocation was forced to fail. The inline-record destructor test retained the original trace. [Regression fixture](../../../../compiler/tests/count_reader_native.py)

## Single literal-index readers

On dev002 the same copy-constructor probe exercised generated readers returning
the element at integer key or index zero from a 4,096-entry collection. Across
100 calls per form, both ordinary by-value entries made one copy per call.
Explicit and inferred borrows made zero. Forced failure of the next allocation
raised `std::bad_alloc` only for the by-value entries. The regression fixture
separately checks an empty vector and a missing hash key against their native
exception type and message. A cross-file edit that writes the selected element
changes the return value while preserving the caller's original element.

The indexed-reader proof runs during lowering. With the preparation method above,
16 alternating pairs on CPU 23 gave the following per-body timings. The paired
percentage uses each pair's ratio before taking the median.

| Prepared body | Baseline µs | Candidate µs | Paired median change |
| --- | ---: | ---: | ---: |
| Single vector indexed read | 14.492 | 15.045 | +2.16% |
| Single hash indexed read | 14.794 | 14.730 | −3.69% |
| Unrelated scalar return | 14.310 | 14.198 | −0.66% |
| Unrelated 100-assignment body | 387.721 | 387.544 | +0.06% |
