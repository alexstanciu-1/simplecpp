# Handwritten compiler type knowledge and metadata ownership

Doc Status: planning
Date: 2026-11-14
Status: deferred for discussion when the user is back at the desk
Last reminded (Europe/Bucharest): 2026-11-18

The user requested a daily reminder to return to this audit. At the first active
compiler-work interaction each calendar day, briefly remind them once and update the
last-reminded date here. Do not block unrelated work or implement this refactor without
agreement. This repository notice is an active-conversation reminder, not a scheduled
notification service. No automation tool was available when this notice was recorded.

## Scope and findings

Review active v0.2 handwritten compiler knowledge separately from generated code.
Generated concrete identities, aliases, scalar operators, conversions and specializations
are intentional; a type check is not automatically redundant policy.

| Area | Current handwritten knowledge | Review direction |
|---|---|---|
| Collection method publication | Generator recognizes vector/hash plans and explicitly selects clear/remove/has with expected signatures. | Consider metadata-owned source method exposure/signatures while retaining fast generated dispatch. |
| Mixed/dynamic | boxed_type_definition restricts keys to int/string, forms runtime helper names and selects boxed addition versus bool results. | Consider generating these policies/bindings from existing scalar-family rules. |
| Enum storage | Enum_Preparation enumerates signed/unsigned width candidates and assumes available MINIMUM/MAXIMUM constants. | Consider generated representation candidates; retain declaration-local extrema analysis. |
| Vector/hash models | Canonical element/key/value links and similar index-result/copy capability methods. | Consolidate only actual duplicated mechanics; do not erase truthful family ownership. |
| Carrier families | Handwritten common extraction/wrapping transport and null/sentinel treatment; permissions/bindings generated. | Keep shared transport; review remaining policy versus mechanism boundaries. |
| Scalars | bool results, entry integer contract, interpolation string identity and enum string/integer handling. | Distinguish language concepts from duplicated representation policies. |
| Structs/enums/shared/weak | Application AST identities, declaration preparation, ownership and lifetime behavior. | These are compiler semantic responsibilities, not automatically runtime-metadata candidates. |

Owners: compiler/types/{definition,vector,hash,boxed,carrier,struct,enum,resolution,void}.php;
compiler/04_analyze/prepare/enums.php; tools/api_generator/php/render_compiler.php.
Paths above are relative to v0.2. The agreed review order is a proposal, not permission
to refactor: collection method catalog, boxed policy/bindings, enum candidates, then
only demonstrated duplication in the remaining semantic models.
