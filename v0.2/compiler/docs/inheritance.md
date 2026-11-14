# First single-base struct slice

Doc Status: supporting

The [inheritance contract](../../../specs/inheritance_v0_2.md) owns semantics.
This bounded implementation supports native base storage, inherited instance
fields and construction for the existing `struct` surface across cpp/php/js.
It does not implement the full normative inheritance contract.

PHP and JS spell the relationship `struct Child extends Base`. C++ spells it
`struct Child : Base` or `struct Child : public Base`. Constructors initialize
the base with first-statement `parent::__construct(...)` (PHP), `super(...)`
(JS), or `: Base(...)` before the body (C++). An authored child constructor
without that syntax initializes the base with no supplied arguments, applying
supported defaults. Without an authored child constructor, the original base
constructor owns the effective parameter/default/access contract.

The declaration owns one optional written base reference. Layout preparation
resolves one non-owning base definition through existing symbol watches, before
preparing bodies. Inherited fields and constructor declarations remain at their
original owner; lookup follows the base link without a flattened member index.
Base changes invalidate dependent layouts and callers through those watches.
Reading `this` watches its record, so ordinary method bodies also invalidate when
an inherited field changes type or access. Replacing the selected base replaces
that layout's subscriptions; unrelated methods remain reusable.

Preparation checks cycles, field conflicts, visibility, constructor access,
arity and conversions. Private/private field-name reuse retains separate storage;
other inherited property hiding rejects. Protected instance access requires an
accessing descendant and a receiver of that accessing type or a descendant.
Current-instance access is unavailable in base arguments.

Lowering emits native public inheritance. An explicit or implicit base
initialization becomes a braced constructor initializer, for example:

```cpp
scpp_records::Child::Child(scpp::int64 var_start_0)
    : scpp_records::Base{var_start_0, 3}
{
    // Authored child constructor body.
}
```

Braces preserve the required left-to-right argument/conversion boundaries
without constructing or copying a complete temporary base. Header publication
requires the base layout first and completes resource-bearing base dependencies
through the existing split layout/owner headers. Runtime adapters in base
initializers use qualified names, including when the application namespace is
disabled: constructor-body imports are not in scope there. C++ performs memberwise copies,
base/member construction and exception unwinding. Exact-type shared/weak owners
retain their existing allocation and destruction protocol; no vtable is added.
For every effective authored constructor contract, the payload's own source emits
its allocation factory once and collects complete parameter types. This applies
equally to authored and inherited constructors. Empty shared/weak handle users
need only factory declarations; they do not emit inherited factory definitions.

Deferred: inherited ordinary-method/static-member lookup, protected ordinary
methods/static members, virtual/abstract declarations and overrides, interfaces,
shared-handle upcasts, base-value slicing and explicit parent method calls beyond
construction. New class ownership defaults and destructor syntax are not added.

Verification:

```sh
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/extends_native.py --memcheck
```

The test builds compiler-matched runtime archives and generated C++ with Clang
and GCC. It covers cross-unit layouts, all flavors, access rejection, constructor
contracts, checked base-argument failure order, namespace resolution, retained
versus fresh output after errors and recovery, and exact-type ownership cleanup.
ASan/UBSan instrument generated code; the unchanged runtime archive is not
instrumented. LeakSanitizer is disabled because the sandbox uses ptrace; optional
separate Valgrind runs check all leaks. This is correctness verification, not a
performance measurement or a proof of future native compiler thread safety.
