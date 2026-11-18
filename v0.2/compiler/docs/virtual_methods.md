# Concrete virtual class methods

Doc Status: supporting

The [inheritance contract](../../../specs/inheritance_v0_2.md) owns semantics.
This bounded implementation supports public concrete class instance virtual methods,
mandatory overrides, final implementations and inherited ordinary-method lookup.
It does not implement abstract types/methods, interfaces, unique ownership, borrows,
protected virtual methods, callable variance, reference-return virtuals, explicit
parent calls, authored destructors or struct virtual methods.

PHP supports both `#[\Virtual] public function evaluate(): int { ... }` and
`public virtual function evaluate(): int { ... }`; replace Virtual/virtual with
Override/override for an inherited contract. Attributes take no argument list.
Duplicate markers, including attribute plus modifier, reject. Cpp uses leading
`virtual` and trailing `override`/`final`; js uses leading modifiers. An override
is mandatory when declaring a replacement. Inheriting a body unchanged needs no
marker. Ordinary methods remain nonvirtual and cannot be hidden by a descendant.
Final is admitted on overriding implementations; final types remain deferred.

Record_Syntax normalizes written intent on the existing method declaration.
Function_Preparation settles all signatures before Method_Preparation validates
inherited contracts. Parameter/result types, passing modes, nullability and default
positions must match exactly; names/default values may differ. Calls select defaults
from the receiver's static contract. Invalid combinations are diagnosed before C++
emission. No flattened method inventory or copied base contract is introduced.

The method declaration caches completion and participates in existing signature
watches. Invalidations clear completion; lookup observes each traversed type and
method symbol, including missing nearer methods. Calls retain the selected stable
symbol. They do not retain every derived implementation. Existing publication and
generation retirement own watch cleanup. Failed preparation preserves the last
successful bundle and pending work; recovery compares against fresh compilation.

Virtual implementations emit native C++ member declarations and out-of-line member
bodies. Ordinary methods retain their existing free-function lowering. Native calls
reuse the existing receiver-retention and argument-conversion path, replacing only
the invocation target. Signature forward declarations are shared with constructors;
virtual method declarations are published by the owning record header.

Polymorphic payloads have an implicit public nonthrowing virtual destructor and
explicit defaulted copy/move operations, preserving ownership-bearing field moves.
The existing runtime control block, concrete allocation callbacks and typed shared
views remain unchanged. Native C++ owns dispatch and subobject construction/cleanup;
there is no extra runtime dispatch allocation or compiler-defined vtable.

Verification:

```sh
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/virtual_methods_native.py --memcheck
```

The suite runs cpp/php/js on Clang/GCC with persistent Ninja builds. It verifies
base dispatch, inherited fallback, three-level overrides, static defaults, both PHP
spellings, invalid markers/contracts and repeated error/recovery. Body-only changes
rebuild only the implementation object; override removal/restoration rebuilds affected
native consumers while unrelated units remain reused. A separate base-field
addition/removal verifies header-only rebuilds: caller C++ bytes/timestamps stay
unchanged, both dependent caller objects rebuild, and the unrelated object survives.
A source class named `scpp` verifies globally qualified runtime return types in both
project and standalone output. No-op builds reuse preparation,
emission, objects and the executable. Existing nominal declaration replacement may
still reprepare/reemit dependent source text after a body edit; unchanged emitted bytes
preserve file timestamps and native objects. This is not a claim of body-only AST reuse.

Virtual method edits also add/remove shared-view adapter demand, including successful
source deletion/restoration. Each revision matches fresh output. Native probes verify
empty-receiver checks before argument effects, receiver survival across replacement,
shared result signatures, standalone output, virtual destruction, real payload copy/move
semantics, weak expiry and concrete field cleanup. Optional Valgrind runs check leaks
and invalid memory access. Existing class, extends and shared-upcast suites protect
prior behavior.

On the tested 64-bit Clang/GCC targets, strong/weak handles remain 16 bytes and the
control block remains 24 bytes. The ownership fixture allocation grows from 48 to
56 bytes with polymorphic payloads. These are storage observations, not execution
or compile-time speed measurements.
