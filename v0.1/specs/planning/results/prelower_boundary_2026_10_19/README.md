# In-place runtime representation boundary proof
Doc Status: supporting

Date: 2026-10-19. Source baseline: `62d81f67`, plus the proof committed with this
report. Reproduce with:

```bash
python3 compiler/my-try/tests/prelower_boundary.py --results /tmp/FRESH_BOUNDARY_RESULTS
```

## Result

Direct and opaque representations had identical allocation, requested-byte,
deallocation, payload construction/copy/move/destruction counters in all 12 measured
phases, in four configurations: `-O0`, `-O2`, ASan+UBSan (`-O1`), and `-O0` with
optional copy elision disabled. All builds use `-g0`, separate application/support
objects, no LTO and no PCH. See [complete counters and toolchain](summary.json).

| Operation | Direct allocations / copies / moves | Opaque allocations / copies / moves |
|---|---:|---:|
| Mutable/readonly element borrow | 0 / 0 / 0 | 0 / 0 / 0 |
| Nested element borrow | 0 / 0 / 0 | 0 / 0 / 0 |
| Aliasing append with growth | 1 / 1 / 1 | 1 / 1 / 1 |
| Nested container copy | 2 / 2 / 0 | 2 / 2 / 0 |
| Shared handle lifecycle, including payload creation | 1 / 0 / 0 | 1 / 0 / 0 |
| Owned result, including population and destruction | 2 / 0 / 3 | 2 / 0 / 3 |

These counts describe the instrumented payload, not copies of pointer-sized borrow
carriers. The baseline table is `-O0`; every configuration compares against its own
direct result. Source-required work remains visible: nested container copying still
allocates/copies, and creating a shared payload still allocates.

A negative control adds a heap-allocated payload copy to a borrowed read. The harness
detects the added allocation and copy. Thus the zero-overhead comparison is sensitive
to the specific regression we want to prevent.

## Mechanism and ownership

A single test emitter generates lifecycle forwarding for every concrete owner from
test binding data: runtime integer, an over-aligned instrumented value, vector of
that value, nested vector and shared handle. It does not implement container growth,
copying, ownership or error algorithms; those execute in the existing runtime types.
There are no family branches in the lifecycle/operation emitter. Operation expressions
are fixture bindings, not a proposed production metadata schema or parallel registry.

A native layout probe obtains sizeof/alignment under the same compiler configuration.
Generated opaque owners use those numbers and support-side static assertions verify
both. The over-aligned payload exercises alignment 64, not merely pointer alignment.
Original runtime objects are constructed in the owner's byte storage. Out-of-line
special members operate on that object; no separately allocated implementation exists.

Mutable/readonly borrowed carriers contain the actual runtime element address.
Support functions cast to the actual runtime type, never to an unrelated facade.
The nested test independently asks the original runtime for its element address,
compares identity, mutates through the borrow, and observes the mutation through the
readonly path. Container copies remain independent. Borrows are not used after
invalidating structural changes. Shared copies/moves preserve payload identity and
expected use counts, with final payload destruction.

A test-only by-value provider function exercises owned-result construction directly
in destination storage, without default construction followed by assignment. This
also passes with optional copy elision disabled. It is not an integration test of a
production external provider.

A payload copy throws after one successful copy; both paths destroy that partial
copy, release its container allocation and preserve the original values/live count.
Runtime negative-index errors retain their code, operator and message prefix. Error
trace allocation counts are outside measurement because trace contents differ by
call stack. ASan/UBSan passed; leak detection is disabled for sandbox compatibility.
The fixture separately checks final live payload counts and reports deallocations.

The application preprocessing check confirms runtime vector/shared definitions are
absent from the opaque application unit. Standard support headers may still contain
templates; this is not a claim of entirely template-free preprocessed C++.

The existing shared test runner owns all native actions. No production registry,
prepared facts, AST, publication/cache or backend behavior was changed. Raw build and
execution logs are in `/tmp/prelower-boundary-proof-final`; the committed JSON retains
the toolchain, measured layouts and full counter comparisons.

## What this establishes, and remaining work

This proves the proposed storage/borrow mechanism can preserve the original runtime
object and avoid extra allocations or payload copies for these boundaries. It does
not prove all runtime template families or the current compiler's lowering path.
Counters intercept ordinary/aligned C++ new/delete; they are not a process-wide malloc
profiler. Payload copy instrumentation is test-only; runtime integer/handle internals
are not modified. Shared ownership is checked through runtime use counts.

Next integration still belongs to the existing type/call bindings and prepared
value-boundary/lifetime facts. In particular:

- Define target layout artifact orchestration, identity and invalidation through the
  existing coordinator/native-build owners. This host-executed probe is not a
  production protocol or cross-compilation solution.
- Map prepared places/reference calls to borrowed carriers, including generated
  source-function boundaries, without changing source reference admission/lifetimes.
- Test real provider interfaces, other template families, noncopyable ownership and
  lifecycle availability/noexcept traits before enabling them through general bindings.
- Verify existing publication/cache reuse and measure compilation costs after that
  integration; this proof measures behavior, not compile-time improvement.

No compiler-native rebuild or source-language semantic change is claimed.
