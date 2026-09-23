# Type-stage debug projection inventory
Doc Status: planning

The prototype debug views are diagnostic output, not a persisted cache format or
semantic input. Migration must preserve their useful facts without relying on PHP
reflection over implementation objects. This inventory keeps the remaining work
explicit; it does not declare the complete dump implemented.

| Owner / prototype entry point | Facts to retain | Current state |
| --- | --- | --- |
| `resolve_types/Type_Resolution::to_json`, association portion | Callable/symbol identity, source provenance, provider operation, receiver position, passing modes, syntax IDs, representation/return/parameter IDs; local-ID/type-ID pairs; family package/operation associations; entry symbol | `Type_Association_Debug::encode` supplies this explicit projection. Source paths replace the old source-file-ID field because the current source-buffer owner exposes paths, not assigned file IDs. Do not fabricate IDs. |
| `type_model/Type_Catalog::to_json` | Provider/content/representation scope, ordered named definitions and records, integer/boolean/entry role references | Not yet migrated. Definitions include representation, lifetime/resource permissions, native layout and element-storage ownership; records include field types and lifecycle/body facts. |
| `type_model/Type_Store::to_json` | Context, ordered canonical type rows and definition associations, representations and member rows | Not yet migrated. Preserve unresolved state, member offsets/counts, parameter passing and result production. Do not replace missing information with empty collections. |
| `instantiate/Instance_Set::to_array` | Concrete IDs, definition IDs, receiver, ordered type/value arguments, application bindings, template-check projection | Not yet migrated. Typed registry state is already available; serialization must not expose a mutable state alias. |
| `check_templates/Template_Set::to_array` and nested symbolic results | Template checks and their retained permissions/dependencies, consumed by the instance dump | Inventory its concrete symbolic variants before implementing the instance serializer. Existing behavioral proofs do not constitute debug-output coverage. |
| Complete `Type_Resolution` dump | Catalog + canonical store + association projection + instances | Pending the owner serializers above. Do not publish a misleading partial `to_json()` under this complete-dump contract. |

Construction lookup is separate from serialization. `Construction_Types` uses a
worker-owned Annotation_Types diagnostic channel over the accepted snapshot. It
returns only an already-materialized exact definition; it neither allocates types
nor decides default-construction/lifetime capabilities. Body checking owns those
capabilities. Source errors preserve path/span/reason; stale internal owners remain
internal errors.

Validation for the remaining serializers must check independently expected fields,
null versus empty distinctions, numeric IDs, enum/tag meanings, escaped paths and
names, and unchanged snapshots. Compare meaningful decoded PHP/native data; require
byte equality only for a documented output spelling. Shared semantic identities
must not become raw process addresses or recursive object dumps. The final compiler
report should compose owning projections rather than reflect over PHP objects.
