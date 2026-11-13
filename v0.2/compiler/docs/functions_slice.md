# Typed value parameters, calls and returns

Doc Status: supporting

The [function contract](../../../specs/compiler_entry_v0_2.md#typed-value-parameters-and-returns)
owns semantics. All current value types work as explicit parameters and returns:
int/int64, null, bool, byte, float/float64 and string. Void is return-only.

```text
PHP: function identity(string $value): string { return $value; }
C++: string identity(string value) { return value; }
JS:  function identity(value: string): string { return value; }
```

PHP parameters use type-before-name. Calls work as statements or values, including
nested calls. Typed destinations reuse generated conversions; no new conversion
matrix or runtime type interpretation was added. Strings remain owning values and
text conversions remain unsupported. Non-void bodies require a value return; void
bodies may fall through or return without a value. Entry return still uses its
separate native exit-status conversion.

## Structure and phase ownership

- `ast_parameter_declaration` is function-owned signature syntax with its own
  canonical prepared type. It may own a literal `default_value`; null means required, with no separate optional flag. The function owns an
  ordered `parameters` array; body scope references these same nodes.
- `ast_local_binding` is the common storage interface implemented by parameters
  and local declarations. Reads, writes and latest-write tracking share that path.
  Parameter values start unknown during body preparation; calls cannot change a
  caller local when passed by value; explicit references bind caller storage.
- `Body_Collection` seeds parameter names before local statements. Storage IDs put
  parameters first, followed by locals. PHP bare writes to parameters assign them.
  Parameters and locals share the declaring scope's duplicate chain; the duplicate
  guard now permits these two local-binding node kinds under the same variable key.
- `ast_call_expression` owns source-ordered arguments and prepared positional
  conversion decisions, plus the stable callee symbol. `ast_call_statement` owns
  one such expression; it does not duplicate the call's name or semantic facts.
- `ast_return_statement` retains either a generated `value_conversion` or the
  existing entry `exit_conversion`; void returns need neither. No second return
  record or prepared signature model is introduced.
- Signature preparation settles parameter and return types before any executable
  body. Body preparation reads foreign signatures only. Reset and collection walk
  nested argument syntax, never the target function's body.
- Incremental declaration contracts include ordered parameter types/count, default source spelling and return
  type. Parameter renames do not affect positional callers. Changed signatures
  invalidate observing bodies; unchanged caller ASTs and body-only reuse survive.
- Syntax/preparation inspection includes parameters and nested call arguments.

## Native lowering

Function definitions and forward declarations use the settled generated type
representations. Public header requirements come from those types, including when
only a foreign signature needs string storage. Each source emits its own definitions;
no foreign body is embedded or consulted. Stable symbols keep backend names stable.

Calls with arguments currently emit an immediately invoked lambda. Each argument is
evaluated and converted into a typed temporary in source order, followed by the
invocation using those completed values. Transfers into by-value parameters use
native moves; moving a scalar is just a value transfer. The temporaries ensure a
failure prevents subsequent argument evaluation and callee entry. This is a local
lowering choice, not a new source closure feature. Calls to parameterless functions remain direct.

String echo binds its operand once to a local const reference, keeping returned
storage alive across data/size queries without evaluating a call twice. Discarded
string results are destroyed normally. Parameter reassignment changes only callee
storage, and returned owning strings survive function exit.

Future lowering may replace lambdas with statement temporaries where appropriate;
benchmark first and preserve conversion order, cleanup, snapshots and single
execution. Signature changes do not authorize widening runtime headers or exposing
private C++ implementation types.

## Reviewed donor concepts and boundaries

Reviewed [my-try's typed signature work](../../../compiler/my-try/docs/catalog/04_functions.md#typed-signature-verification--2026-10-02)
and its source-call lowering. Retained concepts: declaration-before-body settlement,
value-parameter copies, argument order and callable signature dependencies. Donor
prepared-call/signature objects and reference/variadic transport were not imported.
Current Simple C++ contracts and the agreed PHP type-before-name spelling take
precedence over historical shorthand examples.

Named arguments, read-only references, reference returns/defaults, overloads, variadics, signature inference,
first-class callables, methods and trailing commas remain deferred. Recursive
signatures can resolve, but terminating recursive execution is not claimed by these function proofs. The later [conditional slice](conditions.md) adds braced branches separately.

## Verification

`tests/typed_functions.php` covers all current types/flavors, typed conversions,
parameter scope/ownership, mutable string value copies, nested calls, discarded
results, return validation, null inference rejection, pure lowering, reset, signature
invalidation and body-only reuse. It also emits a mixed-flavor multi-file program.

`tests/typed_functions_native.py` proves exact output and exit status for all flavors,
single evaluation, left-to-right argument order, returned string lifetime and NUL
bytes. Test-only native exception observation verifies that a first-argument checked
conversion failure prevents the second argument and callee from running. This does
not enable source exception syntax. Cross-file C++ prototypes and string/byte
transport are executed, not only inspected.

Native proof on 2026-11-04: Clang `-O2 -g0`, libc++, Ninja `-j8`, mold, no PCH/LTO;
fresh instrumented app objects and reused runtime archive. Each row is one correctness
build, not a repeated-run performance estimate. Trace stages are nested/cumulative
and are not additive wall time; the multi-file build has four translation units.

| Program | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 209.583 | 28.578 | 41.932 | 18.050 | 20.336 | 70 |
| php | 141.653 | 17.989 | 22.444 | 10.439 | 10.454 | 58 |
| js | 142.758 | 16.667 | 23.627 | 11.886 | 10.251 | 59 |
| cpp checked failure | 169.219 | 26.427 | 15.780 | 7.186 | 6.692 | 74 |
| php checked failure | 127.223 | 14.553 | 10.487 | 3.711 | 4.929 | 59 |
| js checked failure | 131.819 | 14.777 | 9.990 | 4.245 | 4.162 | 62 |
| mixed-flavor multi-file | 129.284 | 27.149 | 30.096 | 9.982 | 12.626 | 63 |

PHP fixture generation/assertions took 80.238 ms. Raw commands, build logs, traces
and results remain in untracked `v0.2/build/typed-functions-proof-2026-11-04/`.

## Trailing literal defaults

`ast_parameter_declaration` owns optional `default_value` syntax and a private
`default_conversion` prepared fact. The literal owns its existing decoded/checked
value facts. Signature preparation validates the optional suffix and prepares each
default once, using generated literal and conversion handlers. Unchanged settled
signatures skip this work. Parameter reads in the body remain unknown values.

Call preparation checks excess arguments and the first omitted parameter in constant
time; a settled optional suffix makes a required-count cache or repeated scan
unnecessary. Only supplied arguments are visited and converted. No omitted argument
ASTs, per-call default conversion arrays or prepared signature copies are allocated.

C++ lowering traverses the signature's parameters, using supplied arguments where
present and the parameter's prepared literal/conversion otherwise. All use the
existing sequenced argument temporaries; generated C++ signatures contain no defaults.
Foreign defaults are read-only declaration syntax, never foreign executable bodies.
Strings construct fresh value storage per call. Signature reset clears default facts.
Inspection shows default source spelling and its prepared conversion.

Publication includes default source spelling in the existing ephemeral signature
snapshot. This conservatively invalidates spelling-only changes; semantic default
hashing and omission-specific watches are not needed for this slice. Incremental
preparation still stops at body facts; this does not add incremental emission.

`tests/parameter_defaults.php` proves all three flavors, default ownership, unknown
body values, generated conversions, declaration rejection even when uncalled or
explicitly supplied, arity, reset, lowering purity, and incremental edit/removal/repair,
no-op and body-only reuse. `tests/parameter_defaults_native.py` executes all flavors
and a mixed-flavor multi-file call, checking exact bytes, repeat string defaults,
argument order and process exit status.

Native proof (2026-11-04): `-O2 -g0`, libc++, mold, Ninja `-j8`, no PCH/LTO,
prebuilt runtime archive and fresh instrumented app objects. These are single
correctness builds, not performance averages; trace stages are nested/cumulative.

| Program | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 195.773 | 23.865 | 32.503 | 15.304 | 14.819 | 73 |
| php | 151.005 | 17.716 | 27.379 | 13.013 | 12.780 | 62 |
| js | 153.423 | 18.446 | 27.938 | 12.313 | 14.220 | 59 |
| multifile | 134.232 | 21.901 | 25.562 | 8.190 | 10.112 | 68 |

## Mutable reference parameters

`passing_mode` is an int-backed enum with native uint8 intent: value/reference.
`ast_parameter_declaration.mode` owns source intent. The same mode participates in
publication's signature contract and syntax/preparation inspection; no separate
prepared parameter or reference-call object is introduced.

Call preparation resolves arguments once, requires a named local or parameter and checks canonical
type identity for references, and records identity in the existing positional boundary
array. Generated conversions remain for value arguments and assignments. Only calls
with references perform a second linear argument pass, after argument preparation,
to mark borrowed locals unknown in the existing `latest_writes` index. No per-call
alias collection, global local scan, callee-body walk or dependency graph is built.
Null in that transient index means unknown; absence still selects the initializer.
A subsequent ordinary-local assignment restores its facts. Reference parameter reads
always suppress constants to cover aliasing with other parameters.
Earlier occurrence snapshots are immutable. Both paths remain linear in arguments,
with constant-time lookup/invalidation per local.

Lowering binds typed `T&` argument temporaries in source order and passes those as
lvalues. Value arguments retain owning temporaries and moves. Signatures emit `T&`
for references; ordinary returns remain values. No runtime wrappers or metadata
conversion changes are needed. The JS spelling `&name: T` is an explicit Simple C++
flavor extension. Reference defaults and const references diagnose as unsupported.

`tests/reference_parameters.php` covers all current types/flavors, exact bindings,
forwarding, value parameter isolation, global rejection, duplicate aliases, live reference vs
value-snapshot argument order, owning string returns, invalid temporaries/types,
deferred reference defaults/const, preparation reset, lowering purity, and incremental
mode changes/body-only/no-op reuse. Native fixtures execute these paths, including
range-check failures after reference writes and aliased-parameter writes. Test-only
C++ exception observation does not enable source exception handling.

Native reference proof (2026-11-04): all 13 executables passed. `-O2 -g0`, libc++,
mold, Ninja `-j8`, no PCH/LTO, prebuilt runtime archive, fresh instrumented app objects.
PHP fixture generation (including incremental checks) took 90.189 ms. These are
single correctness builds, not repeated-run benchmarks. Frontend/backend totals
are nested/cumulative and do not add to parallel build wall time.

| Program | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 143.158 | 15.454 | 18.690 | 8.115 | 8.981 | 64.000 |
| cpp_types | 132.834 | 12.302 | 11.580 | 5.471 | 4.816 | 65.000 |
| cpp_stale_local | 158.463 | 18.463 | 9.723 | 3.275 | 4.931 | 81.000 |
| cpp_aliased_parameters | 173.376 | 26.769 | 12.849 | 4.596 | 5.539 | 68.000 |
| php | 144.669 | 19.546 | 18.901 | 8.511 | 8.856 | 61.000 |
| php_types | 148.107 | 15.558 | 16.108 | 6.147 | 8.072 | 71.000 |
| php_stale_local | 124.289 | 13.124 | 8.730 | 3.564 | 3.815 | 60.000 |
| php_aliased_parameters | 138.588 | 15.803 | 10.165 | 3.890 | 4.648 | 69.000 |
| js | 145.280 | 16.903 | 20.389 | 8.730 | 9.816 | 62.000 |
| js_types | 125.319 | 11.643 | 10.680 | 4.925 | 4.237 | 59.000 |
| js_stale_local | 136.783 | 14.073 | 9.408 | 3.651 | 4.240 | 65.000 |
| js_aliased_parameters | 130.783 | 16.253 | 8.274 | 3.274 | 3.522 | 60.000 |
| multifile | 130.002 | 26.602 | 25.914 | 9.246 | 8.991 | 63.000 |
