# Legacy core runtime: Rust feasibility and unsafe inventory

Doc Status: planning

Date: 2026-10-29

Scope: side-discussion review only; no runtime port or backend decision authorized.

## Finding

The reviewed legacy runtime algorithms and defined behaviors appear expressible in Rust. Most do not inherently require project-written `unsafe`. The strongest candidates for unsafe encapsulation are the existing raw-pointer iteration machinery, shared mutable access and projected ownership, manually managed inline storage, and native interoperability.

This is a bounded implementation review, not proof of complete semantic equivalence, performance equivalence, or ABI compatibility. In particular, reproducing the C++ public API, `std::exception_ptr`, C++ inheritance layout, or C++ coroutine handles literally requires a C++ bridge or a different representation. Adding `unsafe` does not turn those into native Rust facilities.

## Baseline and coverage

Reviewed the legacy tree at repository root `runtime/`, with HEAD `f6969af6e92f72e02ec55476ab6772557eb1028e`. This is the currently available legacy implementation, including subsequent amendments; it is not claimed to be an untouched historical v0.1 release.

Inventory roots: `runtime/include/` and `runtime/public/`; entry points include `scpp/runtime.hpp` and `core/runtime_core.cpp`. Reviewed central storage, ownership, iteration, exception, string, numeric, asynchronous and utility implementations, plus representative generated adapters. Generated overloads were classified by their owning implementation, not individually audited.

Excluded module implementations and their forwarding/adaptation headers, including JSON, filesystem, network, database, UI, process and task modules. Folder location alone is insufficient: for example `scpp/json/from_json.hpp` includes `modules/json/json.hpp` and belongs to the excluded module surface. Host adapters such as FastCGI are outside this core review. Core `async_core` remains included; `core/resource.hpp` and `core/stdio.hpp` are recorded as boundary cases rather than silently excluded. The UTF-8 support used by core strings is included.

Paths below are relative to the repository root. “Safe” means no project-written unsafe is intrinsically needed; standard-library internals may use unsafe. Safe alternatives are design options, not approved replacements or claims of equal allocation cost.

## Family inventory

| Family and implementation evidence | Rust implementation route | Unsafe assessment |
| --- | --- | --- |
| Integers, float, bool, null and sentinels: `core/integers.cpp`, `scpp/int_t.hpp`, `float_t.hpp`, generated numeric adapters | Concrete newtypes, explicit checked arithmetic/conversion, enums or zero-sized sentinels; generated concrete entry points | **Safe.** Preserve overflow, division, shifts, rounding, NaN and invalid-cast behavior explicitly; Rust defaults are not the specification. |
| Operators, comparisons, casts, presence/coalescing and generated overloads: `scpp/cast.hpp`, `operators/`, `scpp/generated/` | Generated concrete functions/traits and compiler-resolved conversions | **Safe.** Rust does not supply C++ implicit conversion/overload resolution. Lowering must resolve the operation, rather than expecting Rust to reproduce C++ overload selection. |
| Nullable, result, false/bool result carriers: `scpp/nullable.hpp`, `result_core.hpp`, `result_or_false.hpp`, `result_or_bool.hpp` | Explicit enums with separate absent, false, success and error states | **Safe.** Preserve extraction failure behavior and distinctions; do not collapse states into a convenient but different `Option`/`Result` model. |
| Strings and UTF-8: `core/string_support.hpp`, `scpp/support/string_representation.hpp`, `support/utf8.hpp` | Owned bytes and byte slices, validated UTF-8 views for Unicode operations | **Safe algorithms.** Legacy invalid-UTF-8 fallback and embedded NUL handling must remain possible; Rust `String` alone cannot represent all inputs. External pointer-plus-length ingestion needs unsafe validation/ownership encapsulation. |
| Vector, fixed array, row arena, bitset, work queue: corresponding `scpp/*_t.hpp` | `Vec`, arrays, integer IDs, bit operations, queues | **Safe storage and operations.** `row_arena_t` uses one-based indices over vector storage, not a raw allocation arena. Escaping interior references and live mutation require the separate access/iteration design below. |
| Custom Swiss-style hash: `scpp/support/hash_t.hpp` | Preserve control bytes, fingerprints, tombstones, packed entry storage, probing and selectable bucket-index widths using safe vectors and an enum | **Safe algorithm possible.** Keep the custom implementation; no substitution with Rust `HashMap` is proposed. Unchecked indexing, manual allocation or SIMD could introduce optional unsafe later, but are not required by this implementation's algorithm. |
| Mixed: `scpp/support/mixed_t.hpp`, `.cpp` | Rust enum with owned/shared/weak table variants and explicit copy/move/borrow behavior | **Safe representation possible.** A literal manual tagged union needs unsafe access/drop. The current scalar-borrow and lifecycle protocol still needs explicit enforcement; using an enum alone does not implement it. |
| Dynamic: `scpp/support/dynamic_value.hpp`, `.cpp` | Enum with scalar/string payloads and shared custom table storage | **Safe representation possible.** Preserve container identity and mutation visibility. Both mixed and dynamic must continue to use the custom hash-owned table machinery. Shared mutable access is a separate issue. |
| Initializers, entries, keyed paths, index-write, collections and foreach helpers | Owned temporary buffers, explicit path traversal, checked operations, generated callback signatures | **Safe algorithms.** Preserve evaluation order, copy/identity behavior, failure atomicity and iterator rules. Raw erased initializer buffers may need unsafe if retained literally. |
| Unique, shared, weak, inline value: `scpp/unique_p.hpp`, `shared_p.hpp`, `weak_p.hpp`, `value_p.hpp` | `Option<Box<T>>`, `Arc`/`Weak` or appropriate single-thread owners, inline fields | **Safe ownership primitives.** Custom clone/assignment and empty-after-transfer semantics are needed. Raw adoption (`reset(T*)`) requires unsafe ownership validation; safe `Box` transfer does not. Shared mutable dereference cannot be translated directly to unrestricted `&mut T`. |
| Shared-self, object casts and source identity: `scpp/shared_self.hpp`, `object_cast.hpp`, `source_self.hpp` | Generated object/type metadata, owner handles and checked projections; explicit identity | **Safe redesign possible; unsafe likely for literal owner-plus-subobject-pointer representation.** Rust traits/`Any` alone do not reproduce arbitrary C++ inheritance or aliasing `shared_ptr`. Native C++ layout remains an interoperability issue. |
| Live iteration: `scpp/iteration_preservation.hpp`, `vector_iteration.hpp`, `table_iteration.hpp`, `value_iteration.hpp` | Explicit cursor state, subscriptions, staged preservation and retained storage | **Unsafe for a literal raw-pointer intrusive implementation.** Safe owner/ID/weak-handle designs are possible but must prove lifetime, allocation and reentrancy properties. Neither ordinary borrowed iterators nor an eager snapshot is equivalent. |
| String-key interning: `scpp/util/global_string_pool.hpp`, `.cpp` | One shared interner, stable IDs, owned string storage and explicit synchronization | **Safe redesign possible.** Current pool stores pointers into map keys; IDs or shared owned strings avoid that representation. Concurrent mutation needs synchronization, not an unsafe global variable. |
| Stable hashing, diagnostics, memory estimates: `scpp/stable_hash.hpp`, `support/dbg.*`, `support/var_dump.*`, `memory_accounting.hpp` | Explicit byte encoding, wrapping arithmetic where required, safe traversal and capacity accounting | **Safe algorithms.** Keep persisted hashes distinct from implementation-defined/native identity hashes. Estimates change with layout; pointer display is not permission to dereference pointers. |
| Callable erasure and transfer permissions: `scpp/callable_t.hpp`, `task_permissions.hpp` | Owned closure objects/trait objects with explicit permission metadata | **Safe representation possible.** Metadata approval is not proof of Rust `Send`/`Sync`; captures and synchronization must independently justify transfer. Manual erased vtables or unchecked transfer would require unsafe and stronger invariants. |
| Async core: `scpp/async_core.hpp`, `.cpp` | Future/state-machine tasks, ready/timer queues, explicit cancellation and continuations | **Safe executor possible.** Safe `Wake` and pinned owned futures avoid a mandatory raw-waker implementation. Literal raw handles, custom pin projection or manual frame lifecycle need unsafe. C++ coroutine-frame ABI requires a bridge. |
| Errors and exception capture: `scpp/runtime_error.hpp`, `core/failure.cpp`, `core/exception_info.cpp` | Owned error records and explicit propagated control flow | **Safe semantic redesign possible.** C++ exception capture/rethrow is not Rust `Result` or panic interoperability. Retaining native exceptions needs a C++ shim; source exception semantics also need compiler lowering. |
| Concrete public adapters: `scpp/detail/concrete_access.hpp`, `string_access.hpp`, `error_value_access.hpp`, `exception_info_access.hpp`, `runtime/public/scpp/` | Concrete Rust types, or an explicitly designed external ABI | **Unsafe if preserving erased inline placement storage.** Typed fields can eliminate this mechanism. Rust types do not automatically match the existing C++ standard-library object layouts. |
| Core resource/stdio boundary: `core/resource.hpp`, `core/stdio.hpp` | Owned Rust file state and explicit closed/readable/writable flags, or a libc bridge | **Safe replacement possible for behavior after compatibility checks.** Retaining `FILE*`, raw native-handle adoption and libc calls requires unsafe FFI encapsulation; buffering/position behavior must be checked before replacing it. |
| Native diagnostic/process boundary: `scpp/runtime_error.hpp`, `scpp/detail/process_memory.hpp` | Safe file parsing and available Rust diagnostic APIs, or platform FFI | **Conditional unsafe.** `/proc` parsing is safe; retaining `backtrace`, `dladdr` and `getrusage` calls needs an FFI boundary. Exact native frame formatting is platform-specific. |

## Unsafe obligations if current mechanisms are retained

1. **Erased inline storage and manual unions.** Prove size/alignment, valid active variant, initialization before reads, exactly-once destruction, and correct transfer. Never byte-copy an owning object merely because its public wrapper is a byte array. Replacing this representation with enums/typed fields can remove the obligation.
2. **Shared mutation and borrowed scalar/field access.** Define who can access each location, for how long, and through what operations. C++ `T &deref() const` cannot become a safe Rust function returning freely aliasable `&mut T`. A place/handle API with short accesses may avoid unsafe; an `UnsafeCell` implementation needs a carefully restricted interface.
3. **Intrusive cursors and retirement.** Prove subscription nodes remain at stable addresses, detach before destruction, retain source storage, and cannot be accessed after cancellation/retirement. Preserve prepare/commit/cancel behavior on failure. Mutation and callbacks must not invalidate an active Rust reference.
4. **Projected owners and raw adoption.** A projected pointer must refer into the allocation kept alive by its owner. Weak upgrade and type checks must precede access. Raw ownership must use the correct allocator and be adopted exactly once; keep source pointers invalidated where ownership transfers.
5. **Native ABI/OS boundaries.** Validate pointer ranges, encoding, struct layout, resource ownership and thread restrictions. Translate errors at the boundary. Keep C++ exception handling on the C++ side rather than assuming Rust can catch/rethrow foreign exceptions.
6. **Optional low-level executor/storage optimizations.** Raw wakers, manual allocation, unchecked indices, pin projections and unsafe `Send`/`Sync` implementations require separate proofs. They are not prerequisites for a first functional runtime port.

`UnsafeCell` permits interior mutation but does not remove mutable-reference exclusivity or permit data races. `RefCell` is not automatically an equivalent substitute either: holding its borrow across a source callback can introduce a panic where Simple C++ permits reentrant access. See the [Rust UnsafeCell contract](https://doc.rust-lang.org/std/cell/struct.UnsafeCell.html).

For asynchronous execution, [Rust's Wake API](https://doc.rust-lang.org/std/task/trait.Wake.html) provides a safe alternative to hand-building a raw waker. At native boundaries, follow the [Rust FFI and unwinding rules](https://doc.rust-lang.org/nomicon/ffi.html); foreign exception interoperability is not established by enabling unsafe.

## Semantics and compatibility requiring separate proof

- Runtime implementation language and compiler output target are independent choices. Rust compiler/lowering does not require a Rust runtime. A Rust runtime supporting ordinary C++ consumers still needs a usable C++ facade and ABI design.
- Keep source assignment, copy, transfer, destructor ordering, aliasing and exception behavior explicit. Native Rust assignment/drop/move cannot simply replace every corresponding source operation.
- Preserve checked numeric behavior and byte-string rules; safe Rust can still implement the wrong language semantics.
- Preserve hash ordering/index transitions, mixed versus dynamic copy/identity rules, iterator mutation visibility, rollback and storage retirement.
- Preserve concurrency and task-transfer constraints. Reference counting does not by itself make the payload thread-safe.
- Allocation-failure behavior needs its own review. Ordinary infallible Rust allocation paths may abort; that does not reproduce every C++ catchable allocation failure or staged rollback path. Unsafe alone does not solve this.
- Native layout, exact allocation counts, stack traces and timings are separate compatibility/performance questions. No equivalence is claimed here.
- Existing C++ undefined behavior is not a behavior to reproduce. A Rust unsafe implementation must satisfy Rust's own validity and aliasing rules.

## Suggested follow-up, not implementation authorization

Start with safe concrete numerics, carriers, byte strings and the custom hash algorithm. Before committing to a complete port, prototype the harder ownership/iteration model: live aliases, mixed scalar borrowing, mutation during traversal, nested callbacks, owner retirement, object projections and transfer. Test observable behavior against legacy fixtures and normative contracts, not just whether Rust accepts the program.

Maintain an unsafe ledger per abstraction: reason, invariant, callers allowed to rely on it, failure/destruction paths and validation. Use Miri and sanitizers where applicable once code exists; neither replaces the invariant proof. This review found no fundamental behavioral blocker in the inspected core, but does not certify every generated signature or establish that a full Rust port is preferable to retaining the C++ runtime.
