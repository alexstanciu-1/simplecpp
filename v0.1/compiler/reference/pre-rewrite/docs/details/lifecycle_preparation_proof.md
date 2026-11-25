# Whole-structure lifecycle preparation: isolated investigation
Doc Status: supporting

Status: passed native/LLVM feasibility proof, 2026-09-19. **No compiler feature or
new published-package format is implemented.** This extends the
[Clang lifecycle discussion](clang_lifecycle_composition.md) to custom bodies.

**Subsequent ownership decision:** source lifecycle composition belongs to our
compiler. This proof delegates composition to Clang, so it establishes linkage
feasibility only; it is not the selected source implementation. See the
[current boundary](clang_lifecycle_composition.md#ownership-decision).

## Conclusion

Clang can implement complete field lifecycle while calling separately compiled
LLVM functions for user-written constructor/destructor bodies. An initializer
expression can also call an LLVM function before the corresponding field is
constructed. The executable link resolves these calls in both directions.

This makes Clang preparation a credible implementation choice for automatic and
custom lifecycle operations. It does not eliminate compiler-owned type checking,
initialization/ownership analysis, borrow checking, or cleanup scheduling.

The concrete integration gap is **project modules with declared application
imports**. Existing runtime packages require all implementations to link without
unresolved application symbols. Preserve that guarantee; do not publish an open
project module as a self-contained runtime package or supply dummy hook bodies.

## Authority and scope

The selected Simple C++ [class rules](../../../../../simple_cpp_compiler/vendor/simple_cpp/generators/php/specs/rules.md)
sections 16.1 and 16.3–16.7 specify constructor/destructor declarations and bodies,
field initializers, and parent construction through a base initializer.
[Compact-layout contracts](../../../../../simple_cpp_compiler/vendor/simple_cpp/specs/compact_layout_types.md)
section 2.2 currently exclude methods from source structs. Custom source structs
therefore remain a possible extension, not an existing language capability.

This experiment uses hand-authored native declarations and LLVM test bodies.
The traced native members intentionally exceed source-struct eligibility. The
test proves the proposed linkage and lifecycle mechanism, not source parsing,
automatic source-declaration export, source classes, or S2S equivalence for every
custom lifecycle case. No PHS source or upstream implementation was changed.

The canonical C++ declaration is shared by the preparation shell and the native
reference. LLVM accesses only measured scalar field offsets and prepared calls;
it neither declares a lookalike native type nor guesses container layout.

## What ran

[Reproducible fixtures](../../src-runtime-preparation/tests/lifecycle/README.md)
live under runtime preparation. The existing `Definitions`, `Bridge`,
`Clang_Toolchain` and `Metadata` owners generate and measure the shell unchanged.
Fixture JSON exposes ordinary construct, copy, destroy and free-function roles;
assignment and vector access use native template helpers selected by that JSON.

Two aggregate shapes exercise the boundary:

- An automatic aggregate with a nested fixed array of traced fields and a real
  `scpp::vector_t<item>` field.
- A custom aggregate with an explicitly initialized, non-default-constructible
  first field, the nested array, a real vector and a scalar marker. Its native
  constructor/destructor call externally declared LLVM bodies.

The call path is:

```text
LLVM test caller
  -> metadata-selected prepared constructor
     -> LLVM initializer expression
     -> native field construction
     -> LLVM constructor body
        -> metadata-selected prepared vector operation

LLVM test caller or native final shared-owner release
  -> native object destructor
     -> LLVM destructor body (fields are still accessible)
     -> native field destruction, in reverse order
```

The LLVM constructor checks that initialization already happened, writes a marker
and appends to the vector. The destructor observes that marker, vector length and
the first field before field destruction. Direct C++ performs the same workload.
An independent expected event sequence checks both paths, rather than trusting
their agreement alone.

## Results

Clang 18.1.3, x86-64 Linux/WSL2, configured runtime include roots, C++23:

| Configuration | Original body | One body replacement | Prepared shell reused |
|---|---|---|---|
| O0 | Passed | Passed | Yes |
| O1 | Passed | Passed | Yes |
| O1 + full LTO, link O1 | Passed | Passed | Yes |
| O1 + ThinLTO, link O1 | Passed | Passed | Yes |

All 16 executions (four modes, two revisions, two paths) matched their expected
61-event trace. Assertions additionally checked vector contents and copy/assignment
independence. Field copies did not execute the ordinary custom constructor body;
destruction executed its custom body exactly once per object, before its members.
The final native shared-owner release invoked the same LLVM destructor body.

The body replacement changed stored values and observed destructor output.
Each mode reused exactly the same shell bitcode bytes and modification time;
the edited body was compiled and the executable relinked. This proves a reusable
artifact boundary, **not** implemented compiler incremental selection. LTO may
recompute optimized code during that relink.

Negative checks passed:

- Normal `Runtime_Preparation::run()` rejected the application imports and
  published no package. Test instrumentation also introduces external functions;
  the rejection log explicitly includes the missing constructor-body hook.
- An executable link with instrumentation present but all three body/initializer
  hooks absent failed with those exact missing symbols.
- The fixture's deliberately narrow ABI comparison rejected a changed hook
  argument type before linking. Ordinary symbol linkage alone is insufficient to
  establish compatible signatures. This check is not a production import adapter.

The complete focused run took about 18 seconds including preparation, builds and
negative checks, with four independent mode jobs. This is a reproducibility
observation, not a compiler-speed or generated-program performance benchmark.
No broad compiler suite was rerun: compiler and production preparation code are
unchanged. PHP syntax, Python syntax and diff whitespace checks passed.

## Design consequences before integration

1. **Separate complete operations from source bodies.** A prepared constructor or
   destructor owns field composition. The source body owns its explicit statements
   and local-variable cleanup; it must not add another round of field cleanup.
2. **Preserve initializer semantics.** Member/base initializer selection is part
   of the type/constructor contract. An initializer hook may compute an argument;
   calling the ordinary constructor body after default initialization cannot
   substitute for constructing a non-default-constructible member.
3. **Declare imports as well as exports.** Record exact body identities, physical
   signatures, target and failure effects. Verify application definitions against
   those imports at the project join. A package integrity check alone cannot do it.
4. **Keep dependencies truthful.** Native declarations, selected operations and
   initializer structure affect preparation. External body instructions with
   unchanged contracts affect body compilation and linking. An unresolved link
   dependency does not require the body to be compiled before the shell.
5. **Do not infer ownership transfer from C++ traits.** The witness reports
   `is_move_constructible` true while moving an rvalue executes a copy constructor:
   a user-declared destructor suppressed the implicit move. Operation availability
   and its semantic effects must remain distinct. Adding generated special members
   can itself change those capabilities or triviality.
6. **Keep current failure policy.** The tested hooks are non-unwinding and bridges
   terminate on failure. Recoverable exceptions, partial source-body cleanup and
   end-to-end unwinding remain deferred.

The smallest next design task is the project preparation/import contract, followed
by connecting a bounded source type to it. Inheritance, virtual dispatch,
constructor delegation, custom copy/move bodies and ownership-wrapper performance
are not established by this experiment. Supporting them must follow their actual
contracts; this investigation does not enable them by analogy.
