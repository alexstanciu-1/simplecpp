# Writable collection parameters

Doc Status: supporting

Every currently published vector/hash specialization, including application-defined elements, can be a writable parameter of a
function or ordinary method. The existing reference binder owns admission: the argument
must name a local or parameter and have the exact canonical type. Aliases such as
vector<int>/vector<int64> identify the same type; element conversions never authorize
binding a different specialization. Temporary results, properties, indexed elements and
global storage cannot bind. Reference defaults and reference returns remain deferred.
Constructors retain their existing by-value-only rule.

```php
function update(vector<int> &$values, hash<int,string> &$counts): void {
    $values[] = 8;
    $counts["items"] = count($values);
}
```

The reference borrows the caller's named storage for the call. Element mutation, append
and whole-container assignment affect that slot. Forwarding a reference parameter keeps
the same slot; forwarding a by-value parameter mutates only the callee's copy. Two
reference parameters can alias the same slot. Whole-container replacement does not
invalidate the slot: runtime assignment handles its old storage lifetime. No element or
property reference is introduced, so reallocation does not create a borrowed element.

A by-value vector or hash parameter owns a local value. Assigning another collection
to that parameter replaces the local value and leaves the caller’s collection intact.
Whole-collection reassignment is an ordinary operation and emits no warning.

Preparation and lowering are unchanged: canonical identity and ast_local_binding checks,
existing storage-write invalidation, existing passing_mode and native Type& parameters.
The implementation removes the collection-only declaration prohibition. No new AST
fields, prepared records, ownership graph, metadata, generated output or ABI are needed.
There is no traversal of collection contents during preparation.

Verification: tests/collection_references.php and tests/collection_references_native.py
cover all six published specializations and three flavors. Proofs include mutation,
replacement, forwarding, by-value isolation, two aliased parameters, int aliases, method
write-back, constructor copy isolation, rejected targets/types/defaults, and vector append.
Cross-file body changes, reference-to-value signature changes, incompatible signatures,
deletion, retry without edits and recovery are compared with fresh emitted bundles and
executed natively. Unchanged caller ASTs, no-op preparation and lowering purity are checked.

## Verification snapshot

2026-11-14: 90 native fixtures passed across three flavors and six specializations.
Eight focused PHP regressions passed: reference parameters, vector operations, value
passing, vector copies, hash operations, carrier values, boxed scalars and enum methods.
Compiler style checks and diff whitespace checks passed.

Medians across six fresh single-unit fixtures, milliseconds:

| Flavor | PHP compiler | Native wall | Clang frontend | LLVM backend | Link command | Compiler + native |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| php | 2.81 | 242.17 | 30.87 | 39.92 | 61.00 | 245.51 |
| cpp | 2.56 | 214.26 | 27.42 | 35.03 | 56.00 | 216.77 |
| js | 2.56 | 236.81 | 29.32 | 39.51 | 61.50 | 239.12 |

PHP compiler covers frontend/preparation/emission, excluding process startup and fixture
setup. Compiler + native adds those measured stages; it is not a separately measured CLI
wall time. O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime, fresh app objects with
Clang traces. Direct Ninja wall includes qualification overhead; Clang components do not
sum to wall time. These fixture medians are not a controlled flavor performance comparison.
Sources, fixtures, executable output, build logs and traces: `/tmp/scpp-collection-references-proof/`.

## Application-defined elements

Concrete application vector/hash definitions now use this same binder and native `Type&`
signature. Removing their temporary declaration restriction is the complete production
change; no new AST structures, type flags, generated code or preparation passes are needed.
Element conversion still does not authorize a reference conversion. A reference borrows
the caller's container slot, not its buffer, so replacing the container is safe and visible
to all aliases of that slot. Inline element copies remain independent; shared fields keep
identity. Constructors remain by-value only.

`application_collection_references_native.py` covers vector and both hash key types in
all flavors with Clang and GCC. It checks functions/methods, forwarding, aliased arguments,
replacement, by-value isolation, shared element fields, temporary/property rejection and
incompatible specializations. Fifteen incremental revisions per flavor compare fresh
artifacts, including reference-to-value signature edits, deletion, failed retry and recovery.
No-op runs prepare no bodies and rebuild no native objects.

The separate [mixed/dynamic coverage review](boxed_collection_coverage.md) checks which
parts of this common path already work for boxed containers.

Application-reference fixture timings (2026-11-18), single measurements, milliseconds:

| Flavor | PHP initial | PHP no-op | Native wall | Frontend | LLVM backend | Link |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| php | 14.69 | 1.84 | 481.06 | 220.11 | 414.74 | 62.00 |
| cpp | 15.11 | 1.73 | 401.03 | 175.80 | 362.21 | 59.00 |
| js | 15.47 | 1.77 | 390.90 | 175.85 | 341.49 | 59.00 |

O2/g0, Ninja8, mold, no PCH/LTO, prebuilt libc++ runtime, direct Ninja qualification.
Frontend/backend are cumulative trace work across translation units, not additive wall
components. This larger fixture exercises three collection specializations, shared fields,
functions and methods; it is not a regression comparison against tiny earlier fixtures.
Raw local results: `/tmp/scpp-app-refs-proof3/`; boxed audit: `/tmp/scpp-boxed-coverage2/`.
