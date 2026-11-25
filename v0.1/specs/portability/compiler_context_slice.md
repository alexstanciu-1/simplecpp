# First compiler portability slice: update context
Doc Status: supporting

The real `compile\Update_Context` component in `compiler/src/compile/state.php`
is now portability-ready. It remains the single maintained implementation used by
the PHP compiler. Only its managed imports changed; its field, default and class
identity were preserved. The adopted source revision was
`75e9b0f7c3f6420255b3b126429afbfa0e13ccb1`.

## Representation and ownership

This is a shared decision object, not a value record. The coordinator owns its
`full_rebuild` flag and changes it between phases; workers observe that same object.
A new request creates an independent context initially set to false. Neither PHP
nor this slice enforces the coordinator's monotonic/phase discipline through access
modifiers; existing compiler policy and tests retain that responsibility.

PHP class assignment shares object identity. Its PHP++ form deliberately remains
an ordinary class, whose assignment shares a handle. No struct annotation, clone,
wrapper, ID table or library emulation is needed. Generated PHP++ is derived output,
not a second source implementation.

## Small structural extension

The converter still tokenizes PHP and observes only local syntax. It now accepts:

- One optional leading `declare(strict_types=1);`, removed in native output while
  retaining its newline count for input diagnostic attribution.
- One optional lowercase semicolon namespace, including qualified namespaces.
  Leading comments are allowed. Block/multiple/late namespaces and other declare
  forms are rejected. Input LF and CRLF are accepted; managed blocks use canonical LF.
- The fixed managed imports immediately after the namespace, or after the strict
  declaration/opening tag when there is no namespace. Manual imports remain rejected.
- File-scope ordinary classes containing explicit public `bool`, `int`, or `string`
  properties with matching scalar literal defaults. Integer defaults in this slice
  are nonnegative literals. No methods, inheritance, constructors, static/readonly/
  nonpublic fields, constants, nullable fields or omitted defaults.
- `new LiteralClassName()` without arguments, including qualified names, and named
  `->property` reads/writes. No dynamic class/member names or method calls.
- Documentary doc comments outside the existing adjacent local-type annotation form.

The AST has class/property, namespace, construction and property-access nodes.
It does not look up class/member names or infer receiver types. Unknown literal
names remain the responsibility of PHP execution and target analysis/compilation.
A public scalar field's syntax/default can be checked locally; cross-file member
existence cannot. This boundary is intentional.

## File set and execution

`compiler/portability.json` lists the ready production files, initially only
`src/compile/state.php` (the [tokenizer slice](compiler_token_slice.md) extends it). This is a migration source set, not a claim that the whole
compiler is convertible. The proof stages those files in a disposable PHP tree
with the same paths, adds its shared test entrypoint and invokes the unchanged
directory-to-directory converter. `src/compile/state.php` maps to
`src/compile/state.phs`. PHP loading stays in the host test harness; generated
project membership supplies PHP++ composition. Neither production source nor test
entrypoint contains a PHP include that needs translation.

```bash
python3 tests/portability/run.py
python3 tests/portability/compiler_context/prologues.py
python3 tests/portability/compiler_context/run.py \
  --target-checkout /path/to/simplecpp-v0.1.76 \
  --results specs/planning/compiler_migration/results/NEW_CONTEXT_RUN
```

The target checkout must be clean at selected commit
`8cc4d8ff7eb395c6cc69219f25bef960323bce45`. Strict native execution uses Clang 18 and
an explicit project-local runtime build. Runtime-provider baseline configuration
is unchanged.

## Evidence

[context-01](../planning/compiler_migration/results/context-01/summary.json) passed:

- Same shared entrypoint in PHP and generated native PHP++, with fixed expected
  observations: initial false, alias sees true, new context false, previous context
  remains true, rebinding sees new false while retained old handle stays true.
- Two-file conversion with unchanged paths, and no-op reuse of both outputs without
  changing the component output timestamp.
- Rejection of methods, inheritance, unsupported fields, dynamic class/property
  access and constructor arguments; failed conversion preserves the output manifest.
- Existing compiler `compile_driver.php` and `incremental_policy.php` fixtures,
  exercising real per-request selection behavior after the import-only source edit.

The separate prologue proof passed LF/CRLF, comment placement, idempotent import
synchronization, scoped mapped calls, exact input-line diagnostics after prologue
removal, and rejection without source mutation for unsupported namespace/import forms.
The existing converter/runtime/incremental suite also passed.

## Remaining work

This section records the first checkpoint; later syntax is documented in the
[tokenizer slice](compiler_token_slice.md).

No additional compiler functionality or PHP support-library API was introduced.
The converter still cannot port the compiler's methods, arrays, typed containers,
traits, value-record representation, clone operations, exceptions or IO. Choose the
next dependency-owned component and establish its behavior before extending syntax.
The native escape hatch remains documentation-only.
