# Milestone 3.1–3.3 bounded completion goal

Doc Status: planning

Agreed 2026-11-19. Milestone 2 belongs to the other workspace. Keep the required-field
relaxation unchanged; protected static methods, interface inheritance/composition and
new exact-type query syntax are not authorized by this goal.

## Ordered slices

1. Inventory exact-type uses before deciding on a source API — complete below.
2. Protected instance methods: ordinary, virtual, abstract, ancestor calls and visibility
   widening. Reuse lexical/receiver access checks, existing method facts and argument binding.
3. Final classes: one source declaration flag, extension rejection, native final spelling,
   and finality-aware admission for existing class/interface casts and tests.
4. Combined verification: all flavors, incremental failure/recovery versus fresh compilation,
   no-op reuse, existing inheritance/dispatch regressions and focused native timings.

Commit verified slices independently. Stop for any wide refactor or new representation
requirement. Do not add a reflection subsystem, per-object type tags, new preparation
passes, copied inherited member inventories or native synchronization for this goal.

## Exact-type inventory

The inventory concerns active v0.2 compiler sources, not donor implementation support.

| Use | Current owner | Implication for native translation |
| --- | --- | --- |
| Compare get_class on two declarations | `ast_declaration::link_duplicate`, structures/abstract.php | A defensive same-category check, with existing local-binding/type-declaration family exceptions. Preserve the invariant; discuss category identity when translating this trait. It does not yet justify a public exact-object query. |
| Dynamic class name as dispatch key | runtime overload selection in prepare.php; generated type handlers | Compiler type identity/dispatch, not a source object's concrete-class query. Reuse the eventual canonical type identifier/capability mechanism; do not add reflection merely to reproduce PHP array keys. |
| Dynamic class name in generated concrete identity | cpp/concrete.php and generator-produced specialization names | Distinguishes compiler type-definition families plus semantic names. Needs stable compiler identity when ported, not necessarily runtime RTTI. |
| Dynamic class constants MINIMUM/MAXIMUM | enum preparation, match and enum construction in prepare.php | Existing integer representation/capability knowledge; belongs with the deferred metadata/type-knowledge review. |
| get_class and ReflectionClass in test tools | retention inspect/equivalence and declaration_facts tests | Host inspection/testing, not a demonstrated source-language runtime requirement. |
| instanceof throughout compiler | AST families and capability interfaces | Subtype/capability tests already have a source path. Exact equality must not replace them. |

Conclusion: there is a real translation decision for the duplicate invariant and
compiler-type dispatch, but no demonstrated need to introduce `object_is_exact` in
this goal. Exact-type syntax, operand/target admission, empty handling and support for
nonpolymorphic views remain a discussion before implementation. No existing invariant
was removed or rewritten by this inventory.

### Duplicate-link translation proof

The declaring scope links nodes only when both their symbol key and duplicate
category agree. The proof uses an enum returned by the declaration kind. Local
declarations and parameters share a category, as do records and enums. A local
and global can have the same `var:` key, and a field and enum case can have the
same bare key. The category keeps those pairs separate. The proof uses a weak
successor link while the scope retains both nodes.

The [PHP oracle](../../compiler/tests/duplicate_link_oracle.php) runs the actual
`ast_declaration::link_duplicate` against production AST classes. The
[native proof](../../compiler/tests/duplicate_links_native.py) compares decisions,
checks accepted identity and rejected-write purity, clears and relinks the
successor, and edits the candidate declaration in the same compiler instance.
Errors keep the last good artifacts, and repair matches a fresh build with the
unaffected unit reused. This verifies the duplicate-link call site with existing
language features. The separate compiler-type dispatch sites still need their
own identity decisions.

## Protected instance methods

Implemented using existing `is_protected` method syntax, threaded through the flavor
function-header builders. No AST field or per-call preparation fact is added.
`record_type_definition::permits_protected_access` owns the common ancestry/receiver
query used by protected fields, constructors and methods. Constructors have lexical
access only; ordinary instance methods additionally constrain receiver static type.
Ancestor-qualified calls use the lexical current-instance type. Overrides may widen
protected to public but cannot narrow public to protected. Interface implementations
remain public. Native free helpers/virtual slots retain the existing emission model;
source visibility is enforced before lowering.

Verification: `compiler/tests/protected_methods_native.py` checks ordinary inline
struct methods, class virtual dispatch, protected abstract/public implementation,
ancestor calls, sibling/base/external receiver rejection and visibility narrowing.
Eight incremental checkpoints per flavor cover body edits, private edits, deletion,
failed retry, no-op reuse and fresh-equivalent recovery.

## Final classes

Implemented a readonly `is_final` flag on `ast_record_declaration`, without extra
per-call facts or indexes. PHP/JS spell `final class Leaf`; C++ supports its native
`class Leaf final` spelling. Abstract/final combinations, duplicate modifiers and
final struct/interface declarations reject in this bounded class slice.
Declaration layout checks reject a final base; native payload declarations emit
`final`. Existing class/interface cast/test admission rejects both directions of
an impossible final-class/interface relationship, including empty operands. Settled
ancestry and existing dependency watches supply this check; no implementer inventory,
new RTTI requirement, type tag or ownership representation is introduced.

`compiler/tests/final_classes_native.py` covers 11 incremental checkpoints per flavor,
final/nonfinal edits, base finalization, interface overlap becoming impossible,
declaration deletion and fresh-equivalent recovery. Its executable combines a final
implementer with inherited protected access, virtual dispatch and an identity-preserving
interface downcast. Additional rejections cover forbidden modifiers and the reverse
interface-to-final relationship. Existing required-field semantics are unchanged.

## Completion and verification

The bounded goal is complete: exact-type uses inventoried; protected instance
methods and final classes implemented. No new exact-query syntax or representation
is introduced. Required-field policy and protected static-method deferral remain.

All 57 focused incremental checkpoints passed (24 protected, 33 final), with
additional access/modifier/receiver rejections and recovery on all three flavors.
Existing namespaces, ordinary struct methods, virtual methods, abstract methods,
ancestor calls, classes, class casts, interfaces and interface-cast regression suites
passed. The interface-cast suite's old final-syntax rejection was updated to reject
extending a final class and rerun successfully. Compiler style and diff checks pass.

Fresh sequential timings, one observation per fixture/flavor, in milliseconds:

| Fixture | Flavor | PHP compiler | Native wall | Frontend sum | LLVM sum | Optimizer | Codegen | Link | End-to-end |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| protected | php | 7.94 | 266.11 | 57.02 | 95.83 | 49.65 | 31.16 | 68.00 | 364.25 |
| protected | cpp | 8.28 | 263.70 | 62.73 | 100.05 | 46.58 | 36.57 | 71.00 | 377.69 |
| protected | js | 7.81 | 252.87 | 52.40 | 90.73 | 44.85 | 31.66 | 65.00 | 355.98 |
| final | php | 8.03 | 255.44 | 66.45 | 100.21 | 47.36 | 39.01 | 67.00 | 357.03 |
| final | cpp | 9.63 | 233.86 | 55.58 | 94.04 | 45.95 | 34.47 | 62.00 | 337.50 |
| final | js | 8.07 | 226.71 | 54.38 | 90.28 | 43.63 | 33.89 | 60.00 | 325.28 |

O2/g0, Ninja8, mold, no PCH/LTO, prebuilt libc++ runtime; direct Ninja qualification.
Frontend and LLVM sum parallel translation-unit work. Optimizer/codegen are nested
within LLVM. End-to-end includes fresh service startup, compile/emission, native
setup/build and executable checks. These single observations are not speedup claims.

Retained structures: protected methods add no fields; final classes add one readonly
boolean on the existing record declaration. No new occurrence facts, passes,
descendant inventories or runtime allocation fields. The shared access query and
existing casts consume settled ancestry; ordinary calls avoid protected receiver
queries. Future exact-type syntax/identity decisions remain outside this completed goal.
