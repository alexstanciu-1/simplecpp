# Runtime-core source formation boundary audit
Doc Status: planning

Checkpoint: 2026-10-27, after `589a8eca4`.
Owner: [runtime-core completion](runtime_core_completion.md), batch C.
This audit records an ownership decision gate; it does not change source admission.

## Finding

Physical prelowering and source eligibility are different models. The C++ API tool
currently proves the former. It cannot truthfully claim the latter from successful
layout probes, copy traits, generated constructors or publication reuse.

The existing compiler already owns source identities, semantic roles, completed
source declaration facts, generic parameter requirements and operation admission.
Extracting those rules into the physical generator would cross ownership boundaries
and duplicate semantics unless the existing semantic owner moves with them.
Full frontend adoption is excluded from this goal; the exact prepared-fact boundary
needs confirmation before changing that ownership.

## Authority and current owners

| Requirement | Authority / current owner | Consequence for the API generator |
| --- | --- | --- |
| Bare source T implies copyable_value | `specs/metaprogramming_contract.md` sections 6.1–6.2 | Do not impose this default on every native/runtime template parameter. |
| Complete source representation, copy and cleanup contracts | Same spec; `compiler/my-try/04_analyze/prepare/semantics/capabilities.php`, `compiler/my-try/compiler/types/queries.php` | C++ copy construction/assignment traits alone are insufficient evidence. |
| Generic definition permissions independent of concrete arguments | Metaprogramming contract section 6.2 | Cannot be validated from a demanded concrete specialization. |
| Application defaults, parameter requirements and role restrictions | `compiler/my-try/03_parse/types/model.php`, Type_Registry::validate_application | Preserve canonical role/identity decisions; do not inspect C++ names. |
| Runtime provider parameter requirements and capability dependencies | `runtime/metadata/modules/core.json` vector/hash type rows | Reuse provider declarations, not a second source capability table in public_api.json. |
| Source Result boundary admission | `specs/error_values_and_take.md`; `compiler/my-try/04_analyze/conversions/results.php`, Result_Conversions::require_payload | result_payload is not equivalent to physical value storage or copying. |
| Physical storage / selected API availability | `runtime/tools/api_generator/capabilities.cpp` and generator.cpp; `runtime/metadata/public_api.json` | Continue target probes, conditional lifecycle/method emission and imported physical-fact validation. |

The compiler source and tests were inspected, not executed as part of this audit.
They are implementation evidence subordinate to the owning language specs.

## Four checks that must not be collapsed

1. **Definition validity:** a bare source T permits only its formal contract, even
   when unused. A later integer specialization cannot legalize T arithmetic.
2. **Application formation:** canonical arguments satisfy declared parameter
   requirements and role restrictions before the application is interned.
3. **Boundary/operation admission:** copying, Result transport, truthiness, arithmetic,
   initialization and extraction each require their own facts. A formed identity is
   not a universal permission to use all these operations.
4. **Physical realization:** the requested public signature and storage actually
   compile, preserve lifetime and match the selected ABI/publication.

A concrete subtlety: `compiler/my-try/tests/type_capabilities.php` constructs a
Result application with a storable provider type, then proves Result transport
rejects it without result_payload. It separately proves nullable transport can
provide result_payload without granting payload copying. Therefore a single
`source_admitted` flag per generated class would lose necessary distinctions.

The physical noncopyable record fixtures remain valid evidence for check 4, not
proof that their records meet a source Result boundary or bare T contract.

## Recommended boundary (pending confirmation)

Keep checks 1–3 in the compiler's semantic owner. The physical library consumes
prepared demands and only the facts required by the selected boundary/operation.
For a bounded API-side proof, use prepared fixture facts; do not present the fixture
as a real frontend producer or full source-level acceptance.

A minimal prepared demand needs canonical source identity and argument mapping,
the selected operation/boundary, its satisfied semantic requirements, and the
corresponding physical public/native binding and publication dependencies. Semantic
facts must retain their own identities and provenance; they must not be promoted
from copy_storage/default_storage/move_storage. Absence of a fact means unavailable,
not a guessed trivial contract. Native-only demands remain explicitly distinct.

Requirements should refer to the existing provider/semantic contracts. Public API
recipes select representation and transport; they must not re-specify source class,
struct, ownership and modifier semantics under new names. Publication reuse proves
physical consistency only; it must not become a source-admission bypass. These are
compiler-owned trusted inputs, not a security certificate for arbitrary JSON.

This is a proposed direction, not a finalized wire schema or permission to add a
new semantic model to the generator. The actual frontend producer remains separate
from a bounded public-library consumer test.

## Alternative: extract a shared source-formation model

A real extraction would touch the existing canonical type/role registry, declaration
capability preparation, generic definition/application checks, Result/ownership
boundary admission, runtime metadata loading and the new C++ consumer. It would
unlock one reusable semantic implementation rather than duplicate it, but entails
cross-stage dependency, incremental identity and concurrency risks, plus source
positive/negative diagnostics and existing compiler regressions in addition to API
publication/lifetime tests. Porting just a whitelist of type names is not an honest
smaller version of this extraction.

The repository's cross-owner refactor rule requires confirmation before this wider
work. Neither option is silently implemented or counted as formation completion.

## Required acceptance evidence for the selected route

- Unknown cleanup/representation facts do not satisfy bare source T.
- A move-only handle does not acquire bare copy permission from unused operations.
- Storable payloads do not automatically acquire Result transport or truthiness.
- Runtime parameters retain their explicit contracts; defaults do not introduce the
  source bare-T requirement.
- Nullable/ownership and direct ownership nesting restrictions use resolved roles,
  including aliases; required/optional nominal handles remain distinct.
- Shared/unique void rejection does not invent a new weak<void> rule.
- Source by-value/modifier normalization is performed by its existing owner.
- Result-of-nullable preserves outer failure versus successful inner absence.
- Imported physical specializations cannot bypass a requesting source operation's
  semantic admission. Diagnostics retain the source request/dependency context.
- Physical native-only fixture requests remain usable and do not claim source
  eligibility. Full frontend integration is reported separately unless scope expands.

Unrelated core work can continue while this decision is pending: scoped carrier
observations and the remaining admitted operation protocols do not require moving
source semantic ownership. The complete carrier review remains open.
