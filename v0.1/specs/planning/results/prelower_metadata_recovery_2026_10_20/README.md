# Metadata validation and recovery — 2026-10-20

Doc Status: planning

Tested base: `a70c0517` plus the type-binding proof/documentation overlay.
No compiler/runtime implementation or schema change was required.

## Owners and bounded result

`Runtime_Catalog_Reader` validates the structured recipe vocabulary and JSON kinds.
`CPP_Runtime_Type_Bindings` resolves targets/arguments against existing definitions,
checks pointee/literal/byte contracts and rejects duplicate bindings through the
existing binding catalog. `Compiler_Lifecycle::configure_runtime` validates candidate
types, protocols and C++ bindings privately before retaining the input. This is
startup configuration; hot reload after compilation begins remains unsupported.

The existing `runtime_type_bindings.php` fixture now installs a valid custom provider
before attempting invalid configurations. It covers **39** rejection cases, up from
24, and requires the expected diagnostic, unchanged installed-input identity, and
successful valid reconfiguration after every rejection. Cases include:

- Missing required fields/ordinary bindings, unknown fields/targets/parameters,
  duplicate definition/modifier bindings and declaration-target mismatches.
- Native versus semantic argument roles, stored/payload selection, modifier payload
  and pointee requirements, malformed native/header spellings.
- Scalar/byte interface vocabulary and adapter consistency; boolean typing of
  concrete exposure and modifier-pointee settings.
- Provider failures after core recipes have already been privately installed.

After all failures and repairs, ordinary representation checks still prove reordered
arguments and a closed native template. Demanded concrete artifacts must be exactly
identical after resetting to fresh registry identities. This adds recovery evidence
without another registry, configuration transaction or family-specific engine logic.
The existing combined acceptance already runs this fixture, so no new runner is needed.

## Fresh validation

All checks passed through the existing 12-slot shared runner. FPM socket startup
was unavailable in the sandbox; its standard CLI fallback ran the PHP checks.

| Recording | Checks |
|---|---|
| `/tmp/prelower-metadata-recovery-20261020-final/logs` | `runtime_type_bindings.php` (39 cases plus recovery/reset), `runtime_inventory.php`, `runtime_protocols.php`, `runtime_types.php` |
| `/tmp/prelower-metadata-contracts-20261020` | `prelower_contracts.py`: unsupported operation/publication repair, closure coverage and aggregate reporting |
| `/tmp/prelower-metadata-provider-20261020` | `prelower_native_boundary.py`: transitive provider-template rejection, strict PCH rejection, pending readiness on failure, ordinary/strict cache separation, repair and ordinary PCH control |

The native provider proof used O0. Both repaired strict builds reused two objects,
compiled zero objects and linked nothing; a template-bearing ordinary object could
not bypass strict validation. Active transitive template leakage was rejected even
after an earlier successful strict build.

The first metadata pass covered 37 cases; two boolean-kind cases were then added and
the final four-check metadata run passed. No failing implementation case was found.

These are PHP-host/compiler-output proofs, not a native compiler rebuild. Accounting,
allocation/copy and full combined acceptance were not rerun for this test-only change.
The prior combined acceptance remains their evidence. No O2/sanitizer run or new
performance measurement was performed.

## Remaining boundary

Valid metadata is not certification of arbitrary provider implementations. Native
compilation and strict transitive-input checks remain required. This slice covers
representative invalid/missing contracts; it does not broaden supported operations,
prove every possible metadata combination, or convert unknown provider combinations
into supported ones. Strict failures never select the ordinary path automatically.
