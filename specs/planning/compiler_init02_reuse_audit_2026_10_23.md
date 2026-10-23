# INIT-02 staged-field converter reuse audit
Doc Status: planning

2026-10-23. Initial source audit, followed by the explicit layout proof below;
automatic marker conversion is not implemented.
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

## Explicit layout proof and source inventory

The active implementation goal now has a passing explicit layout proof in
`tests/portability/staged_storage.py`, with evidence under
`results/compiler_bootstrap_backlog_2026_10_23/staged_storage_layout/`. PHP, legacy
native and ordinary/concrete v0.2 pass for a class owner with class and scalar struct
payloads. Checked reads fail before assignment; class reads retain identity; struct
owner-copy operations preserve independent reads/writes and whole replacement.
A throwing RHS leaves previous class storage intact. No new native protocol needed.

Current production PHP contains no explicit `_uninitialized_` declarations.
`Parser_Run::$current_scope` was the first concrete later-assignment candidate: its
parsing entry paths select a scope after construction. It now uses explicit private
nullable backing with checked current_scope()/select_scope() accessors. The source
adaptation preserves nested scope save/restore and does not depend on marker rewriting. Other
Parser_Run fields such as parsed/collector are established by constructor algorithms;
that is not itself a reason to mark them staged. Review each lifecycle separately.

Next: bounded marker facts/access rewriting, hygiene and unsupported-use diagnostics.
This proof does not establish transparent access parity, invalid-null-write behavior,
struct-owner support or the final COPY-01 protocol. It uses the already-admitted
explicit scalar-record copy operation, not a pending struct-method implementation.

The production parser accessor bodies are extracted by staged_storage.py and execute
on PHP, legacy native and ordinary/prelowered v0.2, proving early failure and selected
scope identity through save/change/restore. PHP parser/AST/trait/retained lookup and
namespace lifecycle regressions cover the actual parser call sites. This remains a
source adaptation, not automatic marker implementation or a native parser rebuild.

## Implementation-owner follow-up: reuse the class Node tree

Inspection of the current converter identifies a stronger reuse point than a token
preprocessor. `referenceClass()` already collects the full property/method list before
returning its class Node; `method()`/`localBody()` retain grouped bodies and `emit()`
visits them recursively. Run a bounded staged-field transformation on that completed
class representation, before emission. This sees declarations after methods without
adding PHP symbol/type inference or a second token parser.

Declaration parsing must retain the marker, original visibility, payload spelling and
origin path as explicit field facts rather than emitting an ordinary property string.
Use the proven nullable backing/checking layout. Track only the containing class's
unambiguous direct fields and `$this` receivers. Trait-expanded tokens retain original
paths, but Nodes currently retain only line numbers: preserve the relevant origin
before admitting or rejecting trait-owned fields. Inheritance must not be inferred
from a spelling match. Generated names need collision checks for fields AND methods;
the current reserved temporary check covers source variables only.

The class tree is not yet a complete place model. `property_access` represents only
`->field` beside its receiver. Standalone writes need a same-level assignment/RHS
boundary; all nested groups must preserve evaluation order. Do not rewrite field
reads recursively before classifying their enclosing use: that could turn a mutation
into an update of a copied value or make a presence probe throw.

Specific existing parser owners requiring attention:

- `issetProbe()` folds its path into one opaque `probe` string. A generic child-Node
  walk cannot see its field uses. Keep a structural path or reject a locally identified
  marked path before this information is discarded. Bare-field isset already rejects;
  keyed probes can hide the staged field inside a longer path.
- `keyedRemoval()` similarly owns removal places; `referenceLocal()` owns borrowing.
  They must not bypass staged-field rejection through opaque emitted text.
- `closure()` is represented as a method-shaped Node. Nested callable receiver scope
  cannot inherit the enclosing class's marked-field map blindly. Unsupported `$this`
  capture forms should reject rather than receive an implicit lifetime protocol.
- Ordinary by-reference call compatibility still belongs to the downstream compiler;
  converter structural rewriting must not claim to resolve arbitrary callee signatures.
  A synthesized by-value getter cannot serve as writable storage.

This is a local converter-model refinement, not a new compiler retained model or
language contract. Declaration/synthesis alone is not a safe usable implementation;
first vertical slice must include reads, whole writes and rejection paths together.
Class/struct payload classification and explicit struct copies remain governed by
COPY-01; unresolved payload kinds need explicit source adaptation, not PHP-reference
semantics guessed from a named type. The 6–12x estimate remains: the class Node reuse
avoids a token preprocessor, but these place and origin distinctions still need work.
