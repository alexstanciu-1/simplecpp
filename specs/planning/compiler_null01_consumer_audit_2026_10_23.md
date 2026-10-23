# NULL-01 consumer reuse audit
Doc Status: planning

2026-10-23. Source inspection only. No implementation, bootstrap or execution proof.
Scope: member access, ownership queries, wrapper transport and retained type keys.
This is a focused consumer audit, not closure of CH15-RUNTIME-TYPE-METADATA-01.

## Finding and proposed owner design

The existing nullable role, canonical application registry, conversion decisions,
value boundaries, callable adaptation and dependency/publication model are reusable.
Do not build a second nullable registry or a new retained-key system. Preserve
nullable<Node> as a semantic application and select shared-pointer physical storage
through the existing representation owner. This remains a recommended bounded model
shape to confirm in implementation design, not a new semantic decision.

Presence, ownership and nominal payload must remain separate queries. A global
"unwrap nullable everywhere" patch would erase the required/optional distinction
at exactly the consumers that enforce it. In particular, semantic matching and
reference compatibility must not use equality of native C++ spelling.

## 1. Member access — existing owner, representation-aware adaptation

Evidence:
- compiler/my-try/compiler/types/queries.php: record_storage, record_payload,
  is_nominal_handle and source_declaration recognize ownership wrappers and direct
  nominal/runtime handles, not a preserved nullable nominal application.
- 04_analyze/prepare/semantics/expressions.php: prepare_field_access selects record
  storage and payload, resolves the original field, and retains a prepared place.
- 04_analyze/prepare/semantics/methods.php: instance calls resolve payload owners;
  instance_receiver selects retain_shared versus borrow_inline/current_instance.

If normalization is simply removed, ?Node can be treated as inline storage or as
an unrelated runtime nullable definition, so property/method lookup and receiver
lifetime selection can fail. Reuse member inventory, access checking, original
field identity, places and receiver retention. Add an explicit presence-aware
receiver/type interpretation under these owners; preserve the applicable checked
receiver contract, do not silently discard absence when discovering the class.
No new permission for nullable dereference, weak access or escaping references is
established by this audit. Tests must distinguish lookup from presence failure and
verify receiver evaluation once, aliases, mutation and base/interface calls.

## 2. Ownership and boundaries — reusable operations, policy composition required

Evidence:
- 04_analyze/conversions/ownership.php: nominal handles receive shared ownership;
  null -> ownership handle is accepted in assignment context, including bare classes.
- 04_analyze/conversions/preparation.php: ownership dispatch precedes nullable
  dispatch; merely making ?Node report a shared role can bypass nullable policy.
- 04_analyze/conversions/nullable.php: empty/wrap/extract are prepared operations,
  currently restricted by payload family and exact-payload matching.
- prepare/semantics/value_boundaries.php: one value/ref protocol supplies calls,
  locals and returns; same_storage_type uses semantic matches with only a numeric
  representation-equivalence exception (types.php), not arbitrary C++ equality.
- prepare/semantics/callable_adaptation.php: directional parameter/result ancestry
  and exact reference contracts already have an owner.

Reuse shared upcasts, normal copy/transfer, exact refs, ancestry query dependencies
and boundary emission. Compose presence conversion before/with ownership conversion:
required Node rejects known null; ?Node accepts null; ?Derived -> Base checks presence
and safely upcasts. Do not insert checked narrowing in an override bridge. Existing
class/shared/unique/weak contracts and agreed redundant-spelling rejection remain
separate. Current scalar/record nullable payload gate removal belongs to NULL-02's
capability policy, not a new class-name exception inside NULL-01.

## 3. Wrapper transport — largest identified adaptation

Evidence:
- 05_backend/cpp/types.php: native_representation_for applies shared storage to
  nominal handles; stored_recipe and pointee/application-pointee logic select bindings.
- 05_backend/cpp/wrapper_transport.php: payload recognizes nullable/Result semantic
  wrappers; adapt and provider_value emit has_value/require_value for them.
  provider_vector accepts strings/shared records through current classification.
- cpp/declarations.php and runtime_operations.php lower nullable_extract using
  require_value; runtime_operations covers concrete/prelowered conversions.
- cpp/opaque_representation.php constructs physical adapters and pointer surfaces.

A semantic nullable application does not always imply a physical optional wrapper.
The existing transport owner must discriminate optional value storage from optional
shared-handle storage, using a common representation description rather than repeated
special cases. Reuse runtime pointer presence/ownership and existing provider adapters;
retain optional-value wrapper behavior for structs/scalars/containers. Do not introduce
optional<shared<Node>> or copy a pointee to bridge representations. Preserve semantic
identity in operations even when physical storage is identical. Validate direct
provider calls plus nested vector/Result/nullable payloads, ordinary and prelowered.
The vector/hash name branches found here also belong in the CH15 inventory; their
complete remediation is not silently included in this NULL-01 estimate.

## 4. Retained type keys — substantial reuse, no rewrite indicated

Evidence:
- 03_parse/types/model.php: canonical_type_use::matches compares type identity and
  modifier; type_application_index uses ordered type IDs and modifier edges.
  intern_application validates and interns existing generic applications.
- 04_analyze/prepare/changes.php: same_signature compares parameter/return types and
  defaults; same_fields compares field types and original declaration identity.
- 05_backend/cpp/opaque_representation.php: identity recursively includes semantic
  definition/argument identities; explicitly permits distinct types with the same
  native spelling. runtime_operations uses those identities in operation keys.
- cpp/specializations.php interns output entries by their derived names.

Preserving the existing nullable application gives Node and ?Node distinct keys
without adding a nullable bit to every retained record. Existing signature/field
comparison should detect the change once semantic IDs differ. This is source evidence,
not proof of every incremental consumer. Require change-and-revert tests for field,
parameter, return and nested generic annotations, checking caller invalidation and
ordinary/prelowered artifact refresh. Same native spelling must not merge semantically
different reference/override checks or conversion helpers. Source overloads, if an
otherwise admitted declaration pair would collide in native ABI, need an explicit
admission/emission disposition rather than inference from physical equality.

## Revised workload and slice proposal

Revise NULL-01 from 15–30x to **12–20x** relative to the established small binding
baseline. The reduction reflects reuse of interning, retained comparisons, lookup,
reference checking and directional adaptation. It does not imply a measured schedule.
Related NULL-02, OWN-01 and LOCAL-01 share foundations; do not sum their estimates as
independent systems. This remains one of the larger tasks, chiefly transport and
semantic integration, not creation of nullability from scratch.

Indicative work distribution (overlapping, not additive commitments):
- Preserve semantic presence identity and central payload/storage queries: 2–4x.
- Member/boundary/ownership/ref/override integration: 3–5x.
- Native/provider/prelowered representation and transport: 4–7x.
- Defaults/required-field integration, migration and retained/cross-route proofs: 3–5x.

Proposed implementation slices after authorization:
1. Agree the small shared interpretation/representation model with existing owners;
   preserve Node/?Node identity and prove same native storage plus distinct keys.
2. Integrate value conversions and required/default boundaries; retain exact refs.
3. Integrate receiver resolution/retention and directional override adaptation.
4. Carry the same semantics through provider/opaque/prelowered transport, including
   nested applications; then retained edit/revert and native/PHP compatibility proofs.

Do not claim each intermediate slice supports every consumer. Keep incomplete cases
explicitly diagnosed; never normalize away requiredness to obtain temporary success.
Escalate before a wide redesign if physical wrapper transport cannot be generalized
within its existing owner, or retained consumers prove representation-keyed elsewhere.
No runtime optional-storage redesign or compiler awareness of the converter is needed
by the inspected paths. Existing source contracts still need their agreed amendments.

## Qualified static property/constant consumer follow-up

Expression-qualified member references still used record_payload after instance
methods had moved to receiver_payload. This misclassified ?Class as a runtime
wrapper during nominal lookup. Reuse receiver_payload at that lookup boundary;
leave the qualifier's prepared type and evaluation untouched. No presence conversion
or dereference is introduced. Static calls already reuse the correct query.

Absent nullable derived qualifiers now select inherited static properties/constants,
preserve qualifier-before-RHS effects and permit updates in ordinary and concrete
output. Retained static-member tests pass after migrating absent class fixtures to
?C = null. Negative member fixtures construct their receiver so definite assignment
cannot mask their intended member-rule rejection. This is the existing Simple C++
static qualification contract, not a claim that PHP permits null::member evaluation.
