# Explicit ancestor instance calls

Doc Status: supporting

The [inheritance contract](../../specs/inheritance_v0_2.md#explicit-parent-member-access)
owns semantics. The [frontend contract](../../specs/compiler_frontends.md#explicit-ancestor-member-access)
records `parent::method()` in PHP, `super.method()` in JS and an actual ancestor type
qualifier such as `Base::method()` in C++. No special `Parent` alias is introduced.

The existing call node retains written parent qualification separately from its
prepared `ancestor_dispatch` fact. PHP/JS lookup starts at the lexical declaring
record's immediate base. C++ type qualification uses normal type/import lookup and
must name a strict ancestor for instance calls. The selected declaration stays at
its original owner. Preparation observes the lexical ancestry and each traversed
method symbol, including absent nearer declarations; it does not flatten inherited
methods or retain foreign bodies. A selected stable symbol supplies the signature,
defaults, visibility and ordinary argument conversions/reference checks.

The existing invocation emitter consumes fixed dispatch. It uses the current
`scpp_self` payload without creating a shared owner or adapter. A virtual method call
qualifies its selected implementation, for example:

```cpp
(scpp_self).scpp_records::Node::scpp_virtual_evaluate(1)
```

Ordinary methods retain free-function lowering, passing the same current payload
through C++'s ordinary derived-to-base reference conversion. There is no RTTI or
payload copy. The outer instance-call path remains responsible for retaining a
shared receiver across argument effects. Calls inside the selected ancestor body
still use ordinary virtual dispatch; the explicit selection applies only to this
call. Existing argument capture selection handles order, conversion failures and
lifetimes without a second parent-specific argument emitter.

An instance target requires a current instance. Base-initializer arguments cannot access it. PHP's
`parent::__construct(...)` and JS's `super(...)` remain separate constructor syntax;
a first-statement parent method call follows implicit base construction instead.
Private ancestor members reject under existing access rules. Protected methods use the lexical access check. A selected static method goes directly
through the ordinary binder and can run inside a static caller. Only an instance call
records fixed ancestor dispatch.

Run from the repository root:

```sh
XDEBUG_MODE=off python3 -B compiler/tests/parent_methods_native.py --memcheck
```

The suite verifies all three flavors on Clang and GCC:

- Ancestor implementation selection, three-level inherited lookup, parent defaults,
  virtual calls inside that body, ordinary/void calls and writable-reference arguments.
- Current-object mutation, receiver survival when an argument clears its final source
  owner, weak expiry and concrete cleanup under Valgrind.
- Checked conversion success/failure and single evaluation; a failure prevents entry
  into the parent body. Namespace/import targets and standalone output compile natively.
- Missing/inaccessible methods, free/static contexts, missing/unrelated bases and arity
  errors; repeated failed retained builds preserve successful artifacts and recover to
  fresh output. Constructor-body calls work; base-argument calls reject and recover.
- Base-body/default edits, nearer override insertion/removal, valid reparenting,
  declaration deletion/restoration and header-only layout changes. Persistent Ninja
  builds prove no-op reuse, body-edit caller-object reuse, and dependent rebuilds with
  unchanged caller C++ bytes/timestamps. Unrelated native objects stay reusable.
- C++ explicit grandparent selection bypasses a nearer override.

The existing nominal AST replacement limitation remains: unchanged caller C++ and
native objects do not imply zero caller reanalysis after a declaration edit. These
checks make no compile-time or execution-speed claim.
