# INIT-02 staged-field converter reuse audit
Doc Status: planning

2026-10-23. Source inspection only; no implementation, execution proofs or bootstrap.
This refines the accepted nullable-storage/checked-accessor direction. INIT-02 stays
converter/source-owned; no native uninitialized value or compiler annotation is added.

## What exists and can be reused

- `tools/php_portability/src/converter.php` already parses explicit required and
  nullable properties, emits ordinary methods, calls, conditionals and throws, and
  reports source path/line diagnostics. These are the building blocks for private
  nullable backing plus checked value getters and whole-value setters.
- `Declaration_Index::inspect/members` in `declaration_index.php` records declaration
  boundaries, method names and directly declared named instance-field type tokens.
  Its direct trait expansion/collision checking provides structural infrastructure.
  It is not an inherited-member or receiver-type resolver. Extend local declaration
  facts deliberately; do not turn this index into semantic type inference.
- The converter already reserves temporary variable and framework class prefixes.
  Reuse that hygiene convention, adding explicit generated member collision checks;
  existing reservations alone do not prove generated getter/setter names safe.
- Existing nullable runtime/type machinery owns absence, payload lifetime and checked
  extraction. INIT-02 emits ordinary source constructs; NULL-01/NULL-02 capability
  and contract repairs remain separate dependencies, not duplicate implementation.
- `specs/portability/required_fields.md` and `tests/portability/required_fields.py`
  supply declaration/visibility/identity/reassignment and incremental-test scaffolding.
  That existing contract explicitly excludes read-before-assignment parity: it does
  not implement this marker. `value_records.md` supplies explicit-copy proof patterns.

## What is missing

1. Recognize `_uninitialized_`, retain the marked declaration and its source location,
   validate the admitted form, and synthesize private storage/accessors. Current
   required-field parsing simply emits a required declaration; ignoring the comment
   would not preserve its promised behavior.
2. Add a bounded structural access transformation. `sequence()` currently emits
   `property_access` containing just `->name`; assignments/operators are surrounding
   tokens, not a resolved field-place/read/write model. `localBody()` tracks parameter
   names and seen/borrowed locals, not receiver types. There is no existing checked-
   property rewrite engine to switch on.
3. Start with directly declared, structurally identified fields (for example an
   unambiguous current-class `$this->field`) and standalone whole-field writes/value
   reads. Preserve visibility, evaluation order and single receiver/RHS evaluation.
   Inheritance, trait insertion and same-name fields require explicit admission or a
   diagnostic; a spelling match alone is not proof of the declaring field.
4. Use explicit PHP accessors/source adaptation for external or unresolved receiver
   flows. Do not infer arbitrary chains, aliases or cross-file member types. Generated
   storage must be private so an unreplaced access cannot silently bypass the guard.
   Unsupported access should yield an actionable converter diagnostic where locally
   identifiable; otherwise the hidden field/access boundary must fail downstream,
   with source adaptation required. No whole-program rejection coverage is claimed.
5. Reject/adapt reference escapes, nested struct mutation, compound assignments,
   assignment-as-expression, presence probes and unset until individually specified.
   Do not implement a blanket getter replacement: `isset` is not an ordinary read,
   and mutating a struct returned by value would modify a temporary.
6. A getter checks absence explicitly; a required return spelling alone is not the
   check. A setter accepts a valid required value, never uses null as reset, and must
   not mark storage initialized before successful assignment. Reassignment failures
   must follow the admitted payload operation's contract; prove the first bounded
   payloads rather than promise strong exception safety for every type.

## Classes, struct payloads and struct owners

A class field payload preserves object identity after checked extraction. A copyable
struct payload uses nullable value storage and explicit independent PHP copies at
value boundaries under COPY-01. Update by copy, mutate the copy, then replace the
whole field. Absence must not require constructing a dummy/default struct.

Distinguish a struct-valued field of a class from a field whose enclosing owner is
itself a struct. Current converter `valueRecord()` admits initialized scalar fields
only, and no methods; historical v0.1 struct restrictions also prevent assuming a
nullable-field/accessor layout there. First prove the class owner with class and
copyable struct payloads. For a struct owner, require an already admitted source
representation/helper adaptation or report the unsupported case. Do not extend
Simple C++ to make this conversion work. State/copy preservation for enclosing
records remains a separate admission proof, not an implied consequence of getter
synthesis. This audit does not declare all struct-owner forms supported.

## Remaining workload and sequence

**Retain 6–12x for the bounded INIT-02 slice**, incremental to NULL-01/NULL-02 and
COPY-01. Reuse avoids a new runtime/storage subsystem, but access classification,
name/visibility hygiene, safe rejection and two-route behavior proofs are substantive
new converter work. Roughly, declaration/synthesis is the smaller part; restricted
read/write transformation plus failure/copy proofs dominates. These are relative
volumes, not time estimates. Explicit accessor adaptations grow with real site count.
General transparent PHP property emulation remains excluded (20–50x+ risk).

Implementation order when authorized:

1. Inventory actual marked candidates and access shapes; identify source adaptations.
2. Prove explicit accessor/backing code for one class payload and one copyable struct
   payload on both routes, using admitted native constructs. Stop at missing semantics.
3. Add marker/declaration facts and the smallest structural read/write transformation
   that shares that proven layout; reject unsupported cases rather than infer types.
4. Prove early-read failure (including argument/return reads), delayed assignment,
   identity, reassignment, nullable control, failed assignment, generated-name hygiene,
   visibility, and independent struct copies/replacement. Add evaluation-count/order,
   unsafe mutation/reference rejection and incremental-conversion coverage.
5. Validate v0.1 and v0.2 ordinary/prelowered behavior before rollout. Existing fixtures
   are reuse opportunities, not fresh evidence; none were run during this audit.

No change to Simple C++ semantics is proposed by INIT-02. It strengthens the portable
PHP conversion guarantee for explicitly marked staged fields. Exact exception class,
message or cleanup timing is not promised; dependencies on those need separate review.
