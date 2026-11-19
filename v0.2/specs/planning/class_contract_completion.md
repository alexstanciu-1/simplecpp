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
