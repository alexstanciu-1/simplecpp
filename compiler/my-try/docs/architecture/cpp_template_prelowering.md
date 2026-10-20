# Concrete C++ specialization
Doc Status: supporting

The default `prelower_templates` path uses the existing AST, registry identities and
prepared operation decisions. It adds no semantic pass or second type registry.
Ordinary C++ is an explicit opt-out. There is one specialization engine configuration;
handwritten family recipes and the former runtime-ABI emission mode have been removed.

## Selection and strict boundary

CLI, `Compiler` and standalone `CPP_Generator` default to the same general concrete
path through `cpp_lowering_options`. CLI `--prelower-templates` remains an explicit
selection; `--no-prelower-templates` selects ordinary compilation (the last mode flag
wins). API callers can set `$compiler->cpp_lowering->prelower_templates = false`,
or pass `new cpp_lowering_options(false)` to `Compiler` or as the generator's options.
Unsupported concrete operations fail; ordinary mode is never selected automatically.
The temporary `runtime_template_abi` and
`concrete_type_substitution` option fields no longer exist. The retained C++ program
stores only the effective `prelower_templates` setting. Switching it clears affected
C++ fragments without changing semantic preparation; unchanged mode replays demand.

```sh
php compiler/my-try/main.php --s2s SOURCE_DIRECTORY --output ABSOLUTE_OUTPUT
php compiler/my-try/tools/build_cpp.php ABSOLUTE_OUTPUT ABSOLUTE_CACHE
# Explicit ordinary compilation, with optional runtime PCH:
php compiler/my-try/main.php --s2s SOURCE_DIRECTORY --output ABSOLUTE_OUTPUT --no-prelower-templates
php compiler/my-try/tools/build_cpp.php ABSOLUTE_OUTPUT ABSOLUTE_CACHE --pch
```

The coordinator publishes `application_templates: "forbidden"` for this mode.
Known unsupported representations/operations fail at their existing backend owners.
Native compilation rejects runtime PCH and active template tokens in all application
units and their transitive headers. Validated object reuse follows fresh dependency
identity through the existing native cache. Neither failure switches to ordinary
compilation. See [native enforcement](native_cpp_build.md#dependencies-and-pch).
Source publication readiness and successful native readiness are distinct.

Runtime and middleware implementations still use templates. Only application inputs
must be template-free. Metadata registration alone is not a support claim; consult
the [representation/operation coverage matrix](../../../../specs/planning/runtime_template_capability_matrix_2026_10_19.md#current-coverage-contract--2026-10-20).
Callable storage, object tests/casts, weak observation, source exception transport,
`take`, match without a default, and specified boxed/runtime helper paths still reject
until concrete transports exist. Ordinary mode retains its existing semantics.

## Existing owners

| Responsibility | Owner and contract |
|---|---|
| Source identities, storable/copy requirements, ownership and source capabilities | Existing registry and completed preparation; backend reads settled facts |
| Runtime provider variation | Existing `cpp_types` recipes and callable/initialization/indexing/iteration bindings; no family registry in the engine |
| Concrete physical selection | `CPP_Types`, `CPP_Type_Bindings`, `CPP_Opaque_Representation`; stored and payload argument roles remain distinct |
| Demand and artifacts | `CPP_Specializations` and retained fragment requests; only demanded types/operations and their dependency closure |
| Entry/runtime error boundary | `CPP_Entry_Support`; shared CLI initialization and runtime exception reporting in middleware |
| Source fields, signatures, members and sequencing | Ordinary C++ emitters; no duplicate source-implementation partition |
| Initialization | Existing prepared factory/entry conversions, `CPP_Data_Construction`, `CPP_Initializer_Buffer` |
| Operations | `CPP_Runtime_Operations`, concrete member/index/iteration/mutation adapters; native renderers/runtime algorithms remain authoritative |
| Constructor transport | `CPP_Source_Calls` reuses selected call boundaries and native argument rendering; ordinary source methods use ordinary emission |
| Layout, publication and reuse | Existing Clang probes, `Output_Publication` and native builder/cache; no new persistent engine/cache |

Native outer implementations contain concrete stored arguments where selected by the
recipe. Opaque storage owns the actual native object; it does not introduce a second
payload or mandatory heap box. Borrowed transport designates existing objects and owned
results construct in final storage. Source evaluation order, temporary lifetime and
cleanup remain with existing emitters. The scalar unary rendering used by the general
path stays in the ordinary operation emitter; it is not a container-family recipe.

Layout probes determine native size/alignment and special-member traits. Source copy
permission still comes from prepared capabilities. Dependent layouts follow the existing
generated-header graph, with explicit rejection of unsupported cycles. A layout match
alone does not authorize byte copying or arbitrary provider interoperability.
See the [runtime physical contract](../../../../runtime/specs/concrete_type_interoperability.md).

Source method prototypes use concrete forward-declaration headers where a name is
sufficient, including methods accepting or returning their own `value<T>`. Stored
fields still require complete concrete storage. Modifier pointer interfaces use the
runtime's `pointee_modified_type` binding and retain by-value const access; ordinary
member emission and prepared empty-owner conversions remain with their existing owners.

Low-level `request_opaque`/`request_observation` helpers remain for direct storage and
observation proofs of the shared representation worker. They are not a selectable
source-lowering mode. Public requests always use concrete argument substitution.

## Generated naming

`CPP_Artifacts::bounded_component` supplies concrete type labels: up to 48 readable
bytes plus the full SHA-256 of semantic identity and physical spelling. With the
`concrete_` prefix, identifiers are at most 124 bytes; nesting does not expand the
public filename. `CPP_Runtime_Operations::name` uses the operation kind and a full
SHA-256 of its selected contract/types/binding. The engine sorts emitted entries by
name, so discovery order and compact entry IDs do not determine artifact bytes.

The readable prefix is a debugging aid, not identity. Distinct deep types may have
the same prefix. Generated implementation aliases and bridge definitions show their
physical targets; no additional debug-name registry is retained. Hash collisions
have no separate detection/disambiguation mechanism; these names rely on SHA-256.
Do not describe this as a proven collision-free encoding.

The inventory proof exercises ten nested template levels with different leaves,
shared readable prefixes, distinct type/operation identities and bounded filenames.
It tests naming only, not execution of every deep operation. Source callable/type/unit
paths and low-level proof-only `opaque_` names still use reversible hex encoding and
can grow with the original name. General path-limit handling and richer debug name
maps remain non-blocking debt; this review changes no output spelling or cache keys.

## Proofs and remaining work

The agreed supported path is solidified by the [fresh combined O0 sign-off](../../../../specs/planning/results/prelower_solidified_acceptance_2026_10_20/README.md)
at `da17e1ba`: all eight phases pass, accounting is identical (998/0 in each mode),
and the complete 16-unit application closure is template-free. This status retains
the explicit unsupported-operation boundary and deferred broader runtime coverage.

Run the combined current acceptance with
`python3 compiler/my-try/tests/prelower_acceptance.py --results FRESH_DIRECTORY`.
It composes the existing O0 proof owners and retains per-phase evidence; failures
cannot be hidden by a later successful accounting run.

- [Native compiler execution](../../../../specs/planning/results/native_prelower_accounting_2026_10_20/README.md):
  O2 compiler executable with O0 downstream builds; PHP/native artifact parity,
  repeated concrete emission, strict rejection/recovery, accounting and full closure.
  The existing validator's `--prelower-accounting` gate owns reproduction.
- [Fresh public accounting acceptance](../../../../specs/planning/results/prelower_public_accounting_2026_10_20/README.md): ordinary/prelowered 998/0, identical output, complete 16-unit no-PCH closure and warm reuse.
- `tests/prelower_null_output.py`: public CLI/API parity, single-option lifecycle/replay, strict rejection/repair, PCH rejection and complete fixture closure.
- `tests/prelower_contracts.py`: explicit unsupported-boundary failure/recovery and exact audit command coverage.
- `tests/prelower_header_boundaries.py`: O0 output/indexing/compound behavior, reference identity, order and matching allocation/copy counters.
- `tests/prelower_layout.py --o0-only`: shared low-level observation/storage layout and cache proof, including failure/recovery and dependency edits. The default extended run also includes optimization/sanitizers.
- `tests/prelower_source_lifecycle.py`: ordinary/concrete source methods and constructors,
  receiver retention, ordered/default/reference arguments, owned results, copy-failure
  cleanup, ownership identity, strict rejection/recovery and method/layout edits at O0.

Current reproduction and proof limits are in [testing](../testing.md). Comparison-only
recipe/runtime-ABI harnesses are preserved as non-executable historical source in
[the proof archive](../../tests/historical/prelowering/README.md), with their migrated
behavior mapped to current fixtures. They do not justify keeping obsolete production branches.

The [solidification TODO](../../../../specs/planning/prelower_templates_solidification_todo_2026_10_20.md)
tracks consolidated acceptance and remaining contracts. Full runtime coverage,
wrapper removal, new optimization and ThinLTO remain deferred; see the
[tentative v0.3 runtime review](../../../../specs/planning/runtime_prepared_facts_review_v0_3_2026_10_20.md).
The [pre-cleanup history](cpp_template_prelowering_history_2026_10_20.md) retains older
implementation checkpoints and evidence links without defining current configuration.
