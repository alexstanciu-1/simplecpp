# Supported-path consolidation and naming review — 2026-10-20

Doc Status: planning

Reviewed base: `689e75ae`, with the inventory proof/documentation overlay.
No production code, metadata, emitted spelling or native cache key changed.

## Checklist reconciliation

Existing evidence closes the supported-path obligations for explicit unsupported
operations, transitive header/PCH enforcement and reuse of prepared decisions:

- [Metadata/native recovery](../prelower_metadata_recovery_2026_10_20/README.md):
  failed metadata candidates preserve configuration; strict provider leakage and
  PCH rejection retain non-readiness and recover without selecting ordinary mode.
- [Transport audit](../prelower_transport_audit_2026_10_20/README.md): existing
  semantic/call/emission owners retain sequencing, lifetime and value boundaries.
- [Demand/publication proofs](../prelower_demand_publication_2026_10_20/README.md):
  demand retirement/restoration, cached replay, layout dependencies, native reuse,
  O0 counter/reference/order and cleanup evidence.
- [Combined acceptance](../prelower_acceptance_2026_10_20/README.md): complete fresh
  accounting closure, public options and the focused proof suite at its recorded revision.

Current architecture, CLI/API guidance, interoperability and testing instructions
now agree on the one public concrete path. The historical archive maps detailed
source-method/constructor cases to the migrated lifecycle fixture. Searches of live
PHP found no references to the retired `runtime_template_abi`,
`concrete_type_substitution`, `CPP_Template_Recipes` or `CPP_Runtime_ABI` names.
Shared low-level physical storage/observation helpers remain intentional proof APIs,
not alternate public emission modes. No additional deletion was justified.

## Naming audit and proof

The existing naming owners already bound public concrete and operation names:

| Surface | Current contract |
|---|---|
| Concrete representation | `concrete_` plus at most 48 readable bytes and a full SHA-256 identity suffix; at most 124 identifier bytes |
| Concrete headers/forward declarations | Same bounded basename, under existing specialization directories |
| Operation bridges | Fixed operation-kind label plus SHA-256 of selected contracts, types and bindings; no recursive public spelling |
| Debug inspection | Readable type prefix/operation kind, generated native aliases and bridge definitions; no retained debug registry |
| Source callable/type/unit paths | Existing reversible hex encoding; can grow with source identity/path length |
| Low-level proof-only opaque names | Existing reversible hex encoding; outside the public concrete substitution path |

Engine interning trusts the generated hash identity. There is no explicit digest
collision detector or alternate-name protocol; SHA-256 reliance is not a mathematical
collision-free guarantee. Richer debug mapping and general source/path/opaque length
handling remain non-blocking debt, not a reason to change all names in this slice.

The existing inventory fixture now requests ten nested vector levels with integer
and string leaves. Their readable prefixes must match, full identities must differ,
concrete identifiers must stay within 124 bytes and generated basenames within 128.
Operation-name construction must remain bounded/stable and distinguish the two input
types. This tests naming, not whether those deep output operations are supported.

Fresh shared-runner result: `/tmp/prelower-naming-20261020/logs`, inventory **PASS**.
The runner used its CLI fallback after sandbox FPM socket startup failed. This adds
to the inventory task already included in combined acceptance. Documentation links
and diff whitespace were checked. No native build, accounting rerun, performance
measurement or renewed full-acceptance claim was needed for this proof-only change.

Remaining representation/operation combinations stay explicitly unproved or rejected
as recorded in the coverage matrix. Full runtime expansion and optimization remain
deferred; nothing in this reconciliation loosens the no-silent-fallback contract.
