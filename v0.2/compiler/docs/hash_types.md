# Published hash type identities

Doc Status: supporting

This slice connects the existing typed-hash runtime to provider exports and compiler
resolution. The subsequent [hash operations slice](hash_operations.md) adds empty
construction, keyed writes and reads. Append, iteration and hash argument passing
remain deferred. Unused typed parameters provide a native proof
that source identities select the correct concrete declarations and public headers.

## Publication and ownership

`runtime/metadata/runtime.json` supplies `hash.source_type` and `hash.provider`.
The default publication now requests the existing four combinations of int64/string
values and int64/string keys. The source order is **hash<Value,Key>**. Both int and
int64 refer to the same canonical payload/key definition.

`prepareHashes` owns specialization validation and publishes each concrete identity
through the common type-export index. Hash declarations join provider assembly;
required providers are derived from the actual key/value, int64 count, bool_t result
and nullable<Value> lookup-result exports. There is no compiler-owned C++ name/header
map. A legacy publication `hashes.header`, when supplied, must agree with its provider.

The default provider is core.hashes at scpp/hashes.hpp. It can be relocated or share
a compatible provider. Sharing the carrier provider is tested. Sharing the vector
provider with the default vector-bearing carrier publication creates an effective
cycle and is rejected before output changes. Hash headers no longer include the broad
numeric umbrella; only the required provider dependencies are included.

The runtime retains its existing concrete APIs, strict input filters, private storage
algorithms and template-free public headers. Generated src/hash.cpp is built into the
default runtime archive when hash publication is present. CMake provider inventory owns
header byproducts, and tracks the new compiler renderer/template and hashes.php output.
Custom publication removal retires the hash header/source and generated identities.

## Compiler structures and resolution

- `hash_type_definition` has two native non-owning canonical links: value_type and key_type.
  Its value-conversion hook accepts contextual empty construction; other whole-hash conversions remain deferred.
- Generated `types/hashes.php` contains one shared singleton per supported published pair.
  Constructors derive canonical source names from the resolved scalar identities.
- `type_registry::generic_arity` reports two for hash. `specialize` accepts an optional
  second canonical argument; existing one-argument vector/carrier calls remain valid.
  Generated pointer-identity tests select the concrete export without source-time metadata lookup.
- `Type_Resolution` validates one/two-argument arity and resolves each child exactly once.
  It follows first_child/next_sibling directly, without allocating an argument array.
- Existing `ast_type_reference` is unchanged. Both argument nodes attach to the containing
  type node, and written source spellings remain available for diagnostics and fingerprints.

This foundation introduced compiler inventory version 23; the value slice advances it to 24. No new AST nodes, symbol indexes, watchers, queues,
prepared expression records or cleanup owners are introduced. Unchanged ASTs and stable
symbols retain their existing incremental ownership. Optional widths, bool keys/values,
nested hashes and hash payloads in other containers/carriers remain unavailable.

## Verification

`tests/hash_types.php` covers all four pairs, aliases, ordered AST ownership, missing/extra
arguments, unsupported types, and deferred value-return diagnostics in all three flavors.
Incremental checks change either argument, introduce an unsupported specialization, retry
without edits, delete and re-add the declaration, then check no-op reuse. The unchanged
consumer AST and stable symbol entry remain identical. Every successful emitted bundle is
compared with fresh compilation and executed natively.

The declaration-only test installs a synthetic observer to exercise contract invalidation;
it does not claim source hash calls or operations are supported. `tests/hash_types_native.py`
builds all 18 successful checkpoints with Clang and records frontend, LLVM and link timings.

`tools/api_generator/tests/hash_providers.py` checks provider relocation/sharing, concrete
exports, minimal includes, CMake header inventory, generated PHP registration, template-free
C++ consumers and relocated runtime implementation compilation. It also checks unsupported
key/value types, duplicate pairs, missing nullable dependencies, invalid providers/headers,
cycles, unchanged outputs after rejection, subset publication and retirement/re-addition.

Existing hash storage/consumer/freshness/boundary tests remain the runtime behavioral proof.
The hash implementation algorithms were not changed by this slice.

Final checks: 86 selected compiler/hash/provider CTests pass; existing carrier/vector/
submodule provider tests also pass. Compiler style checks cover 166 PHP files. All 18
native checkpoints pass. Generator parity captures retain 130 accepted/247 rejected
runtime cases, 19/537 scalar cases and 17/37 CLI cases. Existing corpus native artifact
sets are unchanged; only metadata plan/input digests changed with the added provider.
The production default publication additionally emits the newly requested hash header
and implementation, as intended.

Representative declaration-only native timings (milliseconds), rerun after concurrent
provider builds finished:

| Flavor | Native wall | Clang frontend | LLVM backend | Link command |
| --- | ---: | ---: | ---: | ---: |
| cpp | 123.53 | 24.84 | 13.42 | 61 |
| php | 122.90 | 25.36 | 14.28 | 59 |
| js | 120.65 | 26.07 | 14.19 | 55 |

O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime and fresh instrumented application
objects. These are single observations, not a flavor comparison or runtime hash benchmark.
Trace totals and parallel wall time are distinct. PHP fixture generation, including all
incremental/fresh checks, took 85.41 ms. Raw complete-checkpoint results are retained at
`/tmp/scpp-hash-types-native/results.json`; isolated representative measurements are at
`/tmp/scpp-hash-native-timing/results.json`.
