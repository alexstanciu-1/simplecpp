# Custom source lifecycle exports
Doc Status: supporting

Custom source lifecycle bodies now participate in the existing source/native family
bridge. This extends the [four integration gates](source_family_integration_plan.md)
without moving source behavior into Clang or adding a second lifecycle implementation.

## Preparation contract and implementation evidence

The phase order stays unchanged:

```text
type resolution and ready layout preparation
  -> declared complete operation + stable import ABI
  -> native preparation against that declaration
body checking -> lifetime/ownership analysis
  -> selected export verification -> joined implementation evidence
backend preparation -> existing lifecycle emission -> export forwarding -> final link
```

An available prepared operation means that its source declaration supplies that
capability and a complete implementation plan exists. It does not mean that a custom
body has already passed analysis. A project module retains unresolved source
obligations; it is never a successfully compiled source program by itself. All
advertised available operations are verified, including ones not imported by the
currently demanded native methods, so one consistent adapter contract is retained.

`Project_Exports` collects declared operations and current layout obligations.
`Export_Verification` binds reachable custom bodies to their exact current
`Analyzed_Body` records, and resource-bearing operations to accepted complete
ownership summaries. Missing or stale evidence rejects before backend emission.
It selects fixed tasks using full-rebuild/changed-input policy; `Export_Worker`
produces private evidence and `Export_Join` accepts exactly the selected results.
`source_linkage` and `Backend_Context` retain that evidence separately from native
preparation contracts. No mutable verified flag is written onto retained types or
packages. Workers share existing analysis records, rather than copying body graphs.

## Compatibility with the native profile

The native payload profile promises uninitialized storage for construction and live
objects for assignment/destruction. It provides a preserved const source for copies,
disjoint operands for construction, and permits self-assignment. It supplies no
extra allocation-state invariant for a live object.

Export checking uses the existing field-local ownership transition algebra:

- Construction must accept an empty destination; live operands must accept both
  empty and owned resource states unless a future profile explicitly narrows that.
- Successful construction/assignment must establish deterministic resource states.
- Complete destruction must discharge its allocation fields.
- Copy sources must remain unchanged.
- Inferred alias exclusions must follow from the profile. Construction can require
  distinct source/destination operands; assignment cannot require those operands to
  be distinct because self-assignment is permitted.

Consequently a source destructor can be valid for every locally constructed instance
in a particular program and still be unsuitable for this profile. A destructor whose
body requires an owned allocation needs a type-state invariant the profile currently
does not carry. This slice diagnoses that mismatch; it does not infer a new native
contract from favorable call sites. Ordinary source behavior is unchanged.

These checks establish the supported type/lifetime/ownership contracts, not arbitrary
functional properties of user code. Richer object invariants, general movement,
exception unwinding and recovery remain separate work. End-to-end coverage here uses
runtime-managed fields; general copying of compiler-tracked allocation descriptors
through native families is not established by this proof.

## Incremental and worker boundaries

Selection compares exact complete plans, definitions, reachable analyzed bodies and
complete ownership results. Unchanged evidence is retained. A changed custom body
requires new verification even when its signature and native ABI remain unchanged.
Native package reuse continues to depend on the exported contract; source body bytes
are not part of native specialization identity. Current body and lifetime associations
must agree before old verification can be considered for reuse.

Tasks contain fixed operation/definition/analysis references. Joins reject missing,
duplicate, stale or foreign results and omit removed contributions. There is no new
scheduler, recursive path enumeration, threading implementation or rollback protocol.

## Proofs

- [Custom source/native execution](../../tests/integration/source_family_custom.php)
  uses a nested source record with a managed runtime field and custom default, copy,
  assignment and destruction bodies. Native append/read bridges execute those bodies;
  event counts and balanced allocations check observable behavior. One custom-copy
  body edit changes execution while preserving native artifact bytes/mtime and old
  snapshots. The proof links at O0, O1 and ThinLTO and rejects missing/stale analysis,
  incomplete/duplicate/foreign verification outputs and stale retained evidence.
- [Source export preparation](../../tests/05_generate_code/prepare_backend/source_exports.php)
  accepts declarations containing nested custom bodies, but rejects final verification
  without their analysis.
- [Owning storage fields](../../tests/integration/owning_storage_fields.php)
  proves compatible construction acceptance and rejection of a locally valid destructor
  whose ownership precondition is stronger than the native profile.
- The existing automatic-source/native execution proof remains on the same path.

Validation results are recorded in the foundation tracker's current-status section.
