# Data initialization: adopted hybrid and stage boundary
Doc Status: planning

Recorded 2026-10-05. Hybrid direction and general stage boundary adopted;
notation/interface standards complete; shared syntax and callable model implemented.
Factory protocol selection, input views/non-escape and construction remain pending.
Language authority remains [the top-level standard](../../../../specs/data_initialization.md).
This note records lowering choices and implementation gates. The lifetime rules and
exact interface/input API have now been consolidated into the top-level standard;
this note does not claim an implemented borrowing facility or replace that standard.

## Adopted hybrid

Keep recognized list/keyed initialization interfaces, typed ordered inputs, known
construction targets and runtime factories. Combine these with v0.1's temporary
inputs and direct population of destination storage, rather than requiring a second
owning heap container for every literal.

- `initializer_list<T>` is read-only, call-scoped factory input, not a
  retainable owning container. Keyed input is a sequence of typed key/value entries.
- Backing storage remains alive throughout the synchronous factory call. It is
  compiler-managed; no source native interior references/pointers are exposed.
- Factories may read/iterate and copy values into their result or other owning
  storage. Copies follow the value type's ordinary copy/identity rules.
- A factory must not retain or return the input view, store it in escaping state,
  capture it in an escaping closure, or expose element references. Copying a view
  does not extend its backing lifetime. Suspension/async retention is not admitted.
- The compiler must enforce non-escape before enabling user-defined factories.
  Documentation-only restrictions are insufficient. Unsupported forwarding/storage
  paths must reject until their lifetime safety can be established.
- Empty inputs need no backing allocation. Small literals can use bounded local
  temporary storage; large literals may need heap backing. Thresholds and concrete
  representations remain backend decisions, not language semantics.
- Factories construct final destination storage directly and reserve capacity from
  the input count where supported. Typed inputs avoid compulsory mixed boxing.
- The first path may copy temporary elements into the destination. Moving/consuming
  input is a separate contract; zero-copy construction is not promised.
- Preserve single evaluation and source order, including key before value. Do not
  reproduce v0.1's ordinary variadic-call spelling as an ordering guarantee.

This supersedes the earlier proposal that factories could cheaply retain a shared,
immutable owning initializer sequence. Retaining values now requires explicit owning
copies. Shared dynamic payloads remain shared even when the entry itself is copied.

## General stages, specialized lowering

User direction: parsing, collection and preparation should remain general-scoped,
without container-family construction algorithms or C++ allocation policy. They
provide data for S2S. No vector/hash/mixed/dynamic name-switch should be introduced
into generic stage engines to implement the factory protocol.

Confirmed boundary: being independent
of container implementations cannot mean being unaware of source syntax, types,
interface contracts and lifetime validity. The existing
[preparation/emission boundary](preparation_emission_audit.md#result-and-boundary)
and the agreed known-target/non-escape requirements put these semantic guarantees
before generation. Moving them into C++ emission would be an architectural change,
not merely the hybrid allocation choice. No such relocation is implemented here.

Agreed stage responsibilities:

| Owner | General responsibility | Excluded implementation detail |
| --- | --- | --- |
| Parsing | Preserve generic data-construction shape, ordered expressions, optional explicit target and source locations | Select container factory, allocate storage, execute validation |
| Collection | Register ordinary declarations, interface implementations, names and scopes | Build containers, choose C++ helpers |
| Shared semantic preparation | Resolve target and interface binding, input/result types, conversions, copy permission and call-scoped non-escape validity through general contracts; track dependencies | Reserve/hash/insert algorithms, C++ storage layout, stack thresholds, PCH policy |
| C++ S2S | Consume settled facts; emit sequenced temporaries, backing-storage lifetime and direct selected calls | Infer target from keys, discover interfaces or postpone source lifetime errors to Clang |
| Runtime factories | Construct instances, validate data and apply receiver key/duplicate policies | Resolve source types or compiler scopes |

Built-in contracts should supply the same interface/type information that opted-in
user declarations supply. Concrete factory bodies own family-specific algorithms.
Built-in identity/contracts originate in the registry; source-derived capabilities
remain with completed declaration facts and dependency-aware semantic queries.
Do not conflate collection capabilities with fat/inline/handle representation or
parameter passing modes. No representation alone grants construction or iteration.
The exact generic contract representation and bounded non-escape mechanism require
model discussion before code; this note does not authorize a general borrow checker.
If a wide cross-owner refactor is necessary, stop and discuss it first.

## Interface documentation checkpoint

Step 2 is complete in [the language standard](../../../../specs/data_initialization.md#3-recognized-opt-in-interfaces):
`list_initializable<TSelf, TValue>`,
`key_value_initializable<TSelf, TValue, TKey = string>`, static factories returning
the exact target, `initializer_list<T>` and `initializer_entry<TValue, TKey = string>`,
call-scoped non-escape, built-in shape/empty/duplicate policies and mixed-only storage.
The earlier retainable owning-sequence proposal is superseded. Compiler data model
and lowering details still need the normal pre-implementation review; this does not
reopen the agreed API or mark any catalog program as working.

## C++ compilation and proof gates

Use PCH for stable runtime declarations. Prefer fixed-signature typed input views
over variadic packs, literal-length template parameters and unique per-literal
helper classes. Sequence evaluations explicitly; use expression wrappers only
where required by the surrounding expression/lazy evaluation context.

Before declaring the slice complete, prove ordering, duplicate delivery, failure
cleanup, nested defaults and copied dynamic identity; reject view escapes, including
indirect copies/forwarding that would outlive backing storage. Check incremental
invalidation when interface or lifetime contracts change. Compare allocation/copy
counts and focused PCH-backed `-O0`/`-O2` C++ compile costs against v0.1-shaped
construction when performance validation is requested. No performance numbers or
native/compiler support are established by this documentation change.
