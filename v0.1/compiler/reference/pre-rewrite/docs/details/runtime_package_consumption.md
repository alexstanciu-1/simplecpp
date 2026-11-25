# Consuming prepared runtime packages
Doc Status: supporting

The PHP compiler now consumes one optional prepared provider through
[`Package_Adapter`](../../src/01_prepare_inputs/load_runtime/package_adapter.php).
Preparation remains a separate runtime-version operation. The compiler does not
regenerate wrappers for an application or source file.

```sh
php src-runtime-preparation/main.php
php src/main.php --runtime-package generated-runtime --output /tmp/example ./example.phs
```

For example, `return runtime_abs(42);` calls the scalar operation selected in
[`scalars.json`](../../src-runtime-preparation/definitions/scalars.json).
This bridge uses `std::abs(std::int64_t)`; the most-negative integer is outside
that C++ function's valid domain. It is a small ABI demonstration, not the planned
string conversion/error-handling surface.

Resident callers pass `runtime_package_path:` to `Compiler_Session`. Omitting it
keeps the existing catalog-only configuration. Relative paths resolve from the
working directory. The CLI also forwards the option to increment simulation.


The adapter owns package format validation and leases. Normalized semantic/type and
callable contracts belong to [type_model](../../src/04_analyze/type_model/calls.md),
shared with source producers. Its private [import handlers](../../src/01_prepare_inputs/load_runtime/calls.md#adapter-responsibilities)
separate storage, structural records, lifecycle, callable passing and language roles.

## Ownership and queries

Only the adapter interprets the generated JSON shape. It returns immutable
compiler-owned records in a `Runtime_Package`:

| Query | Contract |
|---|---|
| `storage_for(type_id)` | Measured size, alignment and storage kind, including native records |
| `type_for(type_id)` | Provider-local identity, integer facts and a scalar/opaque definition or normalized record declaration |
| `callables()` | Exposed names, exact named parameter/result references and normalized value/address arguments and direct/caller-storage results |
| `module_for(kind)` | Ordinary, full-LTO or ThinLTO implementation path |

These queries describe target-dependent facts. A string storage query does not
authorize the compiler to construct, copy, borrow or destroy a string yet.
The adapter rejects unsupported **exposed** call patterns; unexposed object
operations remain preparation data until their consumption contract is agreed.

Local definitions may map a measured integer to an existing language definition
using `language_type`. Width and signedness must agree exactly. Semantic integer
widening is the compiler's existing conversion rule, applied before ABI lowering.
The adapter does not infer signedness or conversions from a type's name.

Collection joins provider declarations into the normal symbol store. Each symbol
has a source frontend or a provider declaration. Provider signatures have no AST
or body; source-body phases select only symbols with bodies. Shared resolution,
argument checking and backend preparation handle both origins. LLVM emission
reads prepared link names and preserves `signext`/`zeroext` on declarations and
calls. Optional `noundef` optimization promises are not propagated.

No source name, C++ type name or fixture identity selects an implementation in
the compiler. New types, constructors, read-only methods and integer functions within the supported contracts are JSON additions.
New calling/lifetime patterns need a real implementation of their contract.

## Validation, replacement and linking

The adapter reserves `.prepare.lock` for reading through the entire compile/link
request. It verifies the pointer, manifest, all advertised artifact fingerprints,
module variants, target metadata and recorded compiler binary. A compatible
package currently requires the same Clang binary and exact target triple/data
layout as the compiler. This deliberately narrow compatibility rule is explicit;
cross-version bitcode compatibility is not assumed.

Native building links ordinary provider bitcode once with the package's C++
driver and the compiler's configured linker. Provider files are protected from
native output replacement. Only header-defined implementations and the selected
driver's C++ runtime are supported; extra libraries and multiple packages are deferred.

The accepted backend retains the normalized provider snapshot independently of
the existing backend primitive configuration. Identical verified manifest content
and the same language catalog reuse that snapshot. A changed provider requests
full selection through the common compiler pipeline before any worker runs.
The package is the invalidation unit for this slice. Fine-grained provider edits
and persistent compiler caches are deferred. Content hashes verify bytes; entity
identities remain full readable encodings and allocated project symbol IDs.

## Proof and next boundary

[`runtime_abi.php`](../../tests/integration/runtime_abi.php) exercises
real preparation, source arguments/results, integer widening, narrow ABI
attributes, ordinary native execution, one source-body increment, provider-body
invalidation, failure isolation and package locking. Its isolated LTO experiment
compiles emitted caller modules to full/ThinLTO bitcode, verifies cross-module
call elimination, then links retained callers against a changed provider body.
Production linking remains ordinary; this proves the artifact flow, not production
LTO selection, caching or performance.

[Inline storage, construction and call-scoped borrowing](inline_runtime_storage.md)
were first implemented for imported opaque types with an explicit, verified
no-cleanup contract. `language_type` introduces their named definitions into a composed catalog.
Constructors use hidden caller storage; read-only methods receive borrowed addresses.
The [cleanup extension](runtime_cleanup.md) also imports types requiring destruction;
`lifecycle_operations()` supplies validated implicit operations separately from source callables.
[Copy construction](runtime_copy_construction.md) supports same-type local declarations
with explicit bindings. Assignment and owned aggregate passing in source-defined
functions remain unsupported.
[`runtime_inline.php`](../../tests/integration/runtime_inline.php) proves
this path with two distinct layouts, native execution and a body-edit increment.

[Runtime cleanup](runtime_cleanup.md) is implemented for the supported inline path.
[Literal construction and echo](runtime_string_literals.md) are now implemented
through generic span, void-result and language-binding contracts. Conversions and
input follow.

[Conversion selection](conversion_selection.md) adds explicit-purpose bindings and
owned inline results from ordinary runtime functions through the same caller-storage
contract. Provider conversion calls retain ordinary signature dependencies.

## Record arguments

Imported signatures use the same exact named references for scalar, opaque and
record types. The record join first materializes normalized source/provider record
inputs. Signature workers then resolve imported names through `Definition_View`,
and their join validates exact definition identity before accepting canonical
signature IDs. The adapter never allocates canonical record member ranges.

Free functions can borrow an existing provider-record local using the declared
call-scoped const-address contract. Checking selects a local borrow, lifetime
analysis requires an initialized live local, and lowering/emission pass its existing
slot address through the common pointer ABI. Equal source/provider field shapes do
not authorize nominal type substitution. See the [record borrowing proof](../../tests/integration/runtime_record_borrows.php).
