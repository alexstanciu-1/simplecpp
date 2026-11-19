# Checked shared-object access

Doc Status: supporting

Semantics belong to [checked object access](../../../specs/inheritance_v0_2.md#checked-shared-object-access-and-type-tests);
the implemented spellings and class, interface and shared struct scope are in the
[frontend contract](../../specs/compiler_frontends.md#checked-shared-class-access).

`ast_object_access_expression` owns the operand and written target reference. Common
`Object_Access_Preparation` resolves the target through existing type/import lookup,
admits the relationship and records the required target identity and whether a native
runtime type check is needed. It watches both ancestry chains, including inherited
polymorphism. No descendant inventory or foreign executable bodies are retained.
Invalidation clears the occurrence facts through the existing body owner.

For an explicitly shared struct, resolving the bare struct name selects its existing
canonical shared handle. Same-type and upward casts require presence; upward tests
only inspect presence. Both use the existing static pointer adjustment. A checked downcast along a struct
base chain requires virtual dispatch on the declared source (possibly inherited).
It uses the same RTTI adapter as a class downcast. An abstract marker alone supplies
no RTTI. Unrelated structs and struct/interface cross-casts reject during preparation.

Interface overlap reuses the same admission owner: unrelated classes still reject,
but class/interface and interface/interface pairs can overlap without a known
implementer. A final class cannot acquire an unimplemented interface through a descendant;
both directions of that impossible relationship reject during preparation. Existing
record watches observe implements edits and inherited source polymorphism; no new
retained fields or implementer index is needed.

`Cpp_Object_Access` expands two concrete adapter patterns. A downward or cross-cast operation uses
`dynamic_cast<Target*>(source.view)`; same-type/upward views use ordinary pointer
conversion. A cast takes one shared handle by value, keeping the allocation alive during
the check. It transfers that temporary hold into the result and clears the local
parameter. Failure calls the runtime's `throw_invalid_object_cast`; parameter cleanup
releases the temporary hold. A test takes an internal `const&` parameter and returns
whether the pointer is present. Its sole argument remains alive through the call; no
user callback or handle escape occurs inside the test, so no temporary retain is needed. Neither changes a caller's named handle, copies the payload or
allocates an object/control block. The original concrete allocation owns destruction.

The existing adapter engine now accepts concrete body-only type dependencies. They
participate in both specialization identity and declaration ordering. This matters for
two tests with identical source/bool signatures but different targets. RTTI requirements
also participate in identity; downward fragments diagnose disabled RTTI explicitly.
Same/upward fragments require no RTTI. There is no C++ template conversion framework.

Private per-source demand, cross-unit deduplication, retained-output reuse and obsolete
fragment retirement remain owned by the adapter/bundle engine. Do not recreate these
protocols in the cast pattern. Changed native flags invalidate native commands; they do
not change source semantic facts. The tests exercise flag rejection and recovery in the
persistent Ninja graph, without silently overriding `-fno-rtti`.

Run `XDEBUG_MODE=off python3 -B v0.2/compiler/tests/object_access_native.py --memcheck`
from the repository root. It checks cpp/php/js on Clang and GCC, including:

- Required results, optional absence, same/up/down views, deeper descendants, sibling
  mismatch, precedence, namespace/import targets and unsupported inputs.
- Single evaluation, allocation identity, alias mutation, exact strong-count changes,
  failed-cast cleanup, last-owner transfers, weak expiry and Valgrind leak/error checks.
- Template-free concrete bodies, distinct body-only target identities, runtime provider
  includes, project/standalone builds and RTTI-disabled rejection/upward success.
- Retained/fresh equivalence through invalid edits/retries, inherited polymorphism,
  ancestry and target deletion/restoration; cached-consumer demand and last-use removal.
- No-op preparation/emission/native reuse, implementation-body native reuse, header-only
  layout rebuilds with unchanged caller C++ bytes/timestamps and unrelated object reuse.

As before, replacing nominal declaration ASTs can invalidate callers' preparation even
when their emitted bytes and native objects remain unchanged. These tests do not claim
zero caller reanalysis for such edits or native parallel-preparation safety. Interface
inheritance, struct casts, unique, borrows, weak casts and automatic
narrowing remain deferred. No performance improvement is claimed without measurements.

The interface extension is verified with
`XDEBUG_MODE=off python3 -B v0.2/compiler/tests/interface_access_native.py --memcheck`.
It covers class/interface views in both directions, independent interface cross-casts,
absent/mismatching handles, shared control-block identity, adjusted pointers, weak expiry,
temporary transfer and failed-cast cleanup. Retained tests add/remove a runtime
implementer's capability without editing the callers, change source layouts and
polymorphism, delete/restore targets and consumers, and verify fresh equivalence,
adapter retirement, no-op reuse and native depfile rebuilding on both toolchains.
