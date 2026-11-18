# Abstract classes and bodyless methods

Doc Status: supporting

The [inheritance contract](../../../specs/inheritance_v0_2.md) owns semantics;
the [frontend contract](../../specs/compiler_frontends.md#abstract-classes-and-methods)
records the admitted spellings and scope.

The record and method declarations retain their written abstract flags. A bodyless
method has a null body, not an invented empty scope. Source dependency identity
belongs to the function declaration so signature observers do not require a body.
Only actual bodies register preparation jobs. Parameter diagnostics and signature
qualification use the existing declaration machinery.

Method preparation reuses virtual signature/override validation. Record preparation
checks abstract obligations with a temporary nearest-declaration name set while
walking the existing base chain. One readiness boolean caches successful validation
until the existing nominal/layout invalidation clears it. There is no retained
flattened inherited-method inventory or copied foreign body. This relies on current
source-record replacement and ancestry invalidation; it does not add finer-grained
record mutation or claim a preparation-time speedup.

Native emission writes pure virtual members without definitions. Abstract shared
owners retain normal copy, release, access and weak-lock support, but omit their
allocation union and make factory. Concrete descendants own allocation and cleanup;
base-view conversions preserve the existing control block. Abstract base constructors
remain available to descendant construction. An explicitly abstract source class
with only concrete methods needs no extra native virtual member merely to prohibit
construction: preparation enforces that source restriction.

Empty method prototype output is skipped before adding a newline. This keeps
retained and fresh source bytes identical when external observers of a virtual
method disappear.

## Verification

Run from the repository root:

```sh
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/abstract_methods_native.py --memcheck
```

Verified across cpp/php/js on Clang and GCC:

- Pure dispatch, inherited implementations, abstract intermediates and re-abstraction.
- Mandatory overrides, exact signatures and rejection of invalid declarations,
  abstract construction and fixed calls to abstract bodies.
- Base constructors, shared allocation identity, mutation through aliases, checked
  downcasts, weak expiry and cleanup, with Valgrind checks.
- Retained versus fresh diagnostics/output after body/default/signature/base-field
  edits, new obligations, source deletion/restoration and failed-build retries.
- No-op preparation/emission/native object reuse; body edits isolated to the changed
  implementation; header-only changes rebuild dependent objects while unrelated
  objects remain reused. Abstract declarations create no phantom body jobs.

Existing virtual-method, ancestor-call, object-access, class, frontend lifecycle,
failure-cleanup, preparation-work and compiler-style checks also passed.
Interfaces, abstract structs, protected-method expansion, callable variance,
broader reference returns, unique and borrows remain deferred.
