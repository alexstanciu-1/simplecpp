# Portability review debt
Doc Status: planning

[Conversion status](conversion_review.md) separates recorded proof from current
claims. [Incremental/v0.2 debt](../planning/incremental_strategy.md) owns language,
selection, cleanup and partitioning work; do not duplicate that backlog here.

| Owner | Remaining review |
| --- | --- |
| Source reads | Metadata/read consistency and filesystem failure boundaries; mtime/size limitation remains accepted. |
| Token representation | Per-token object/text costs, byte spans and retained-buffer bounds; measure before replacing handles with views. |
| AST/scopes | Native lifetime across retained/replaced/retired graphs; explicit scope owners, weak acquisition and observer survival. |
| Collected/prepared records | Required publication fields, typed weak returns, dependency/index cleanup and strong cycles. |
| Native analysis | Constructor delegation and externally staged initialization; use meaningful rejected-read proofs, not dummy initialization. |
| Host boundary | Reporting, process/resource APIs and truthful dynamic-result stabilization; distinguish host-only code from conversion inputs. |
| Parked LLVM | Preserve regression contracts. Its token-index maps and preparation ownership await shared-fact convergence, not new semantic work. |

## Explicit nullability audit

For a touched field, follow construction, assignment, read, publication, failure and
reset. Required fields must be initialized before use; `?T` is for meaningful absence.
Empty collections remain required. Weak-reference intent does not imply optionality.

Check both missing initialization and unnecessary nullable fields. PHP typed-property
failures, converter acceptance and STAN reasoning are separate evidence. Scope/link
expiration and actual concurrent publication need native tests. The
[earlier initialization audit](../archive/initialization_audit.md) records historical
field decisions, not a current per-class inventory.

## Representation discipline

Preserve shared record identity and stable sparse positions. Use explicit container
and compact-integer annotations only within supported bounds. Indexes are references,
not additional semantic owners. Do not introduce value-record promotion, ID tables,
serialization or collection compaction as incidental portability fixes.

Do not remove narrowing casts solely because PHP accepts the assignment. Keep actual
class narrowing separate from same-type nullable extraction and report unsupported
native forms to their converter/runtime/STAN owner.

## Legacy S2S covariance and source convention

Project builds now supply ancestor/accessor signatures to the legacy emitter, with
cache invalidation for signature changes. Split-file named-object accessor dispatch
is covered by `tests/tools/test_scpp_cross_file_covariance.py`. The compiler still
uses invariant expression accessor returns and distinct specialized accessor names.
See the [generator boundary](../../../../generators/php/specs/rules_catalog.md#cross-file-covariant-object-accessors).

File-local class imports now expand at declaration/expression use sites rather than
emitting shared C++ aliases. The separate-file native proof is
`tests/tools/test_scpp_cross_file_import_aliases.py`; header dependencies remain explicit.

## Native and STAN checkpoint (2026-09-30)

The earlier candidate `11d70185` plus declaration-identity normalization passed
193/236 broad comparisons; its 43 failures involved struct lookup or related rejection
diagnostics. After the shared-identity repair on `cf14bc1a`, native validation passes:
15 scalar and 36 function/struct S2S executions, 27 S2S rejections, all 142 parked
LLVM/sample comparisons (48 executions, 94 rejections), the initial S2S smoke proof,
repeated-compilation/recovery checks and incremental rebuild. STAN was explicitly
skipped. Evidence: `/tmp/my-try-native-20260929-bfd12958/summary.json` and `logs-11/`.

The native identity blocker is repaired in the shared runtime: compatible base/derived
shared handles compare adjusted object pointers, including nullable normalization.
`Scope_Publication::register()` can now match `?collected_struct` with `collected_name`
without a compiler-side cast or algorithm change. The focused runtime test covers
both comparison directions, distinct objects, empty/present nullable values, and
multiple/virtual inheritance; it passes with Clang and GCC. Unrelated static interface
views retain the existing fallback and are not expanded by this slice.

## STAN diagnostic catalog (2026-09-30)

Fresh analysis after `420cb2a3`: 71 converted units, 1,049 diagnostics (70 in the
compile-error bucket, 760 STAN-error bucket, 219 warnings). These are analyzer
classifications, not native compilation failures. The exact original native sweep
now passes 236/236. Native success alone does not establish every diagnostic is false.

| Group | Count | Included diagnostics | Assessment |
| --- | ---: | --- | --- |
| Name/type dependencies | 423 | 421 unresolved, 2 ambiguous | Mostly missing built-in family recognition; two namespace collisions. |
| Overrides | 91 | override_declaration | Ordinary matching hooks are flagged by an accessor-only compatibility exception. |
| Call classification/arity | 15 | 13 static_instance_misuse, 2 argument_count_mismatch | All 13 concern parent constructors; both arity reports concern supported string offsets. |
| Unknown receiver/result chains | 292 | 112 expression chains, 26 return chains, 76 method calls, 60 property reads, 16 property writes, 2 static calls | Recount after resolving root types and typed helper results. |
| Type compatibility | 74 | 24 argument, 21 return, 19 property, 9 local, 1 enum | Includes derived/base and wrapper/numeric-boundary gaps; review individually. |
| Wrapper boundaries | 55 | 23 local assignments, 18 returns, 13 arguments, 1 property assignment | Separate supported checked extraction from missing flow reasoning and actual unsafe use. |
| Initialization | 99 | 67 property, 31 local, 1 partial-branch local | Needs control-flow/publication review; do not insert dummy initialization. |

### Small repair candidates

1. **Parent constructor classification (13 direct reports).**
   `StanExpressionTypeResolver::collectCallSiteDiagnosticForCallSite()` treats all
   syntactic `::` calls as static dispatch. Recognize parent constructor invocation
   on the current instance while retaining visibility, arity and argument checks.
   Prove valid parent construction and rejection of an actual static call to an
   instance method; do not blanket-exempt methods named `__construct`.
2. **String offset metadata (2 direct reports).**
   `RuntimeShallowSourceGenerator` publishes only two parameters for `strpos` and
   `strrpos`; `php_string.hpp` implements both two- and three-argument forms.
   Fix the owning signature metadata and regenerate the STAN surface. Prove two
   and three arguments accepted, one/four and wrong offset types rejected.
3. **Built-in generic families (419 candidate reports, not yet a measured reduction).**
   Breakdown: Storage 171, nullable 131, shared 34, Key_Storage_List 33,
   Keyed_Storage 24, weak 18, result_or_false 5, Storage_Cursor 3. The remaining
   unresolved names are Iterator and Exception, one each, which need separate review.
   `FrontEndSymbolExtractor::collectTypeDependencyTargets()` splits type spelling
   into names; its built-in filter recognizes `shared_p` but misses source `shared`
   and several other supported families. Use supported-family recognition at that
   boundary while preserving dependencies on element/key types. Existing collection
   and wrapper analysis already models many of these families; do not invent fake
   project classes or suppress unknown payload types. Include `Storage<Missing>`
   and nested generic payload rejection proofs. Check body dependencies too.
4. **Matching override signatures (up to 91 candidate reports).**
   `StanDiagnosticCollector::collectOverrideDiagnostics()` currently exempts only
   eligible zero-argument object accessors. An ordinary identical signature should
   pass after type qualification, checking staticness, visibility and parameter
   modes. Start with exact compatibility; general variance is a separate feature.
   Prove identical parameterized/scalar-return hooks and genuinely incompatible
   overrides. Do not compare parameter names or entire declaration records as types.

### Follow-up after the small repairs

`StanDependencyResolver::resolveDependencyTarget()` merges qualified and short-name
matches without lexical namespace precedence. The two `Token_Buffer` ambiguities
combine `scpp\compiler\Token_Buffer` with a runtime declaration in another namespace;
related expression/static-call reports follow. Correct lookup using the declaration
context and imports; avoid a name-specific exception. This is a larger resolution
slice than correcting one metadata signature.

Then reassess cast/weak-acquisition result typing, compatible derived/base boundaries,
nullable flow and initialization using the reduced report. Known required nullable
extraction is an agreed runtime boundary; it should not acquire source casts merely
to silence STAN. Keep real incompatible assignments and uninitialized reads rejected.
Existing focused homes include `test_scpp_stan_bounded_inference.php`,
`test_scpp_stan_strict_discipline.php` and `test_scpp_stan_diagnostics_session.php`.

Evidence: `/tmp/my-try-native-20260929-bfd12958/phpp/.prism/cache/stan_report.json`;
refresh command: `php bin/scpp.php stan` from the converted project, using the
checkout's absolute CLI path. This catalog changes documentation only; repair counts
must be measured after each slice rather than subtracting estimated cascades.
