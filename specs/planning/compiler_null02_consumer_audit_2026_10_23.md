# NULL-02 capability-based nullable reuse audit
Doc Status: planning

2026-10-23. Source/documentation inspection only; no implementation or test execution.
Scope: reuse, overlap with NULL-01, and incremental work for accepted nullable
payload capabilities. This does not close the compiler-wide CH15 audit.

## Existing foundation

- 03_parse/types/register.php installs nullable with a value_storable formation
  requirement and a copyable_value dependency on its payload. This is already a
  generic recipe, not one definition per family.
- 03_parse/types/model.php has_registered_capability evaluates declared capabilities
  and recursively queries argument dependencies. Formation and canonical application
  interning already handle nested arguments.
- Capability_Preparation obtains source-derived facts from completed declarations
  through the scheduler; Capability_Facts reads settled facts. Neither needs a new
  nullable-specific registry/cache of source capabilities.
- Nullable_Conversions already supplies semantic payload, empty/wrap/extract and
  exact-payload conversion operations. Value_Boundary_Preparation owns copy/consume/
  reference handling, rather than nullable having its own transport semantics.
- runtime/include/scpp/nullable.hpp uses std::optional<T>. Absence does not construct
  a T, presence does not copy T, and require_value reports an empty access through
  the existing runtime error surface. Construction from const T& and T&& exists;
  this is not evidence that every native move is admitted by the source language.
- tests/nullable_records.php already covers optional inline records, empty state
  without payload default construction/copyability, copy rejection for noncopyable
  payloads, cleanup, and retained capability changes. These tests are reusable
  fixtures/evidence of intended coverage; not freshly executed in this audit.

## Remaining operation gates

| Consumer | Current restriction / reuse | Required bounded work |
| --- | --- | --- |
| Nullable_Conversions::require_value_payload | Scalar/enum/inline-record whitelist despite generic formation | Replace with definition/storage/admitted-operation queries; forming/empty/presence must not require copy or payload default construction. |
| Nullable_Conversions::require_extractable_payload / Take_Preparation | take payload limited to scalars/enums; outputs bind existing locals; source copy required | Reuse output place/type checks and existing copy policy. Validate output assignment capability, not merely storable payload. Do not turn take into move/out-reference invention. |
| Presence_Operators and Value_Probe_Preparation | Existing presence operations call the same narrow gate | Generalize presence independently of copy/default/truthiness. Existing property-path probe restrictions remain separate. |
| Condition_Truthiness | After payload gate, inline records use presence; other nullable payloads choose nullable_condition | Do not simply delete the whitelist and accidentally enable arbitrary payload truthiness. Preserve existing condition policies; explicit ===/!== null is the general presence witness. |
| Selection_Result_Preparation / Conditional_Expressions | Existing coalesce/conditional owners require exact payload matches and copyability | Generalize admissible payload checks, preserve lazy fallback and existing branch/result rules. No sibling inference, container covariance or blanket match widening. |
| Parameter_Defaults | Nullable null defaults already have a path, then apply current default-copy policy | Reuse null-default handling for admitted copyable payloads. Empty optional local formation does not automatically authorize noncopyable by-value parameter defaults. CONVERT-01's present [] defaults are separate. |
| Ordinary/prelowered/provider transport | Generic optional-value wrapper/recursive transport exists, subject to physical bindings and capability policy | Reuse optional-value representation; validate unfamiliar registered payloads and nested transport. Handle-backed nullable representation belongs to NULL-01 and is not rebuilt here. |

Copyability is not the only conceivable operation requirement: wrapping an existing
payload, consuming an admitted temporary, resetting to empty, assigning a take output
and borrowing a reference have different contracts. Reuse current boundaries and
admit only supported operations. If current capability vocabulary cannot honestly
express one required operation, report a bounded model gap; do not substitute a
family whitelist or grant every operation from value_storable alone.

Existing runtime delegated indexing/member operators are not independent source
admission. Nullable support alone does not enable arbitrary unwrapped indexing,
member access, iteration, interior references or wrapper-delegated empty(). The
current empty probe explicitly rejects wrapper delegation; this audit does not
change that contract. No whole-payload truthiness inference is introduced.

## Overlap with NULL-01

| Shared foundation | Owning allocation of work |
| --- | --- |
| Canonical nullable identity / definition roles / type keys | Reuse existing registry; NULL-01 changes optional class preservation. NULL-02 uses it for nested applications; no second type model. |
| Required-value extraction and value boundaries | Shared conversion owner; NULL-01 handles optional/required class contracts and ancestry, NULL-02 removes payload-family gates under operation capabilities. |
| Handle versus optional-value representation | NULL-01 owns new optional-class representation selection and transport adaptation. NULL-02 reuses existing optional-value storage and adds generic payload proofs. |
| Reference/override rules | NULL-01 owns revised class nullability compatibility. NULL-02 does not create new variance or reference lifetimes. |
| Retained changes / prelowering | Existing IDs/comparisons are shared; NULL-02 adds payload-capability edit/revert and unfamiliar nested-type witnesses. |
| Registry audit | NULL-02 fixes the nullable-policy subset. Broader type-name gates stay tracked in CH15, not silently counted as complete. |

Scalar/inline/container NULL-02 policy can be developed before all NULL-01 consumer
changes, provided handle cases remain explicitly bounded until their owner lands.
Design the shared interpretation first so neither slice duplicates normalization or
physical representation decisions.

## Workload revision

Revise NULL-02 from **6–12x** to **4–8x incremental work** after agreeing the shared
NULL-01 representation/boundary model. Existing formation/capability recursion,
optional storage, operation facts and record tests account for the reduction.
This is not 4–8x per type family and not a new feature for each generic spelling.
Shared NULL-01 work must not be counted twice. Without a settled shared model the
old uncertainty remains; the new estimate is conditional on reuse, not execution proof.

Indicative overlapping sub-slices:
1. Central operation-specific payload admission, removing the whitelist: 1–2x.
2. Presence, take/checked extraction, defaults and existing selection consumers: 1–3x.
3. Unfamiliar/nested registered payload, retained capability and both-backend-mode
   behavioral/rejection proofs: 2–3x.

Escalation conditions: a new ownership/move/borrow protocol, inability to express a
required operation through current capability owners, or an opaque provider ABI that
cannot carry the payload through existing transport. Do not silently include those
redesigns in the estimate. Broad container covariance, runtime optional redesign,
new truthiness and arbitrary source-template admission are non-goals.

## Concrete proofs for the eventual slice

- First compiler witness ?vector<generic_contract>: absent means default copyable
  contract, present empty means no required contracts. Check declaration, argument,
  return, null default, exact wrapping, assignment, copy and required extraction.
- An unfamiliar registered value type and nested application work without a new
  consumer branch. Their physical binding must actually support their declared
  operations; metadata is not permission to invent native behavior.
- A payload lacking valid default construction can still have an absent optional.
- A noncopyable payload permits supported empty/presence operations but fails copies,
  even when the particular source optional happens to be empty.
- Required extraction fails before destination change on absent input; no references
  escape by virtue of nullable support; fallback evaluation remains lazy.
- Retained payload capability changes invalidate consumers without changing nominal
  nullable identity, and restoring the capability restores admission.
- Ordinary and prelowered native execution agree; portable PHP/v0.1 evidence must
  cover the actual compiler witness. No proof is claimed by this source audit.
