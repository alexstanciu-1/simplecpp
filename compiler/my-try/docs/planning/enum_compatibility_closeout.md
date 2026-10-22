# V-D08 compatibility closeout audit
Doc Status: planning

## Outcome — 2026-10-22

The tests/docs-only audit accounts for all eight original V-D08 probes: nullable
and Result enum payloads now execute; the other six are unsupported in pinned
v0.1 as well as v0.2. Earlier wrapper/carrier/helper goals retain their independent
state, snapshot, integer-width and runtime proofs.

The broader control audit finds **one genuine remaining compatibility gap,
VD08-A01: default-less match lacks concrete failure transport**. It reproduces for
enum and integer selectors. V-D08 remains partial pending discussion of this shared
backend gap; it is not hidden by marking the original extension inventory closed.
Overall remains **58/64 resolved (90.625%), two partial, four other open**. No
compiler/runtime implementation changed, no full matrix or native compiler rebuild.

## Evidence and reproducibility

- Source base: `fc3e701ad`; baseline:
  `75de94c71e37060c564b6ade19850b7686bd457a`, using the previously pinned isolated
  compiler/runtime snapshot. Its relevant generator/type-mapper/catalog paths match
  release/0.1.78; this is a frozen-baseline comparison, not a new latest-release audit.
- Host: dev002, `/home/alex-ai/curl/20261020-work`; shared 12-slot runner with CLI
  fallback because CLI/FPM versions differ. Production v0.2 runs use default
  prelowering and `application_templates: forbidden`.
- `enum_compatibility_audit.json` contains 48 paired cases. Eight sources reproduce
  the frozen `verification/results/enum-extension-admission` inputs byte-for-byte.
  Adapted controls are separate entries; original probes are never rewritten.
- `enum_compatibility_audit.py` records generation, native build and execution
  separately. A runner task PASS means the observation was recorded, not that the
  feature works. Reviewed `enum_compatibility_expectations.json` pins source hashes,
  observed stages and support outcomes, including known failures.
- Initial 36-pair run: `20261022-vd08-audit-01`; 12 targeted follow-ups:
  `20261022-vd08-audit-followup`; consolidated expectation-checked run:
  `20261022-vd08-audit-final`.

Reproduce on dev002:

```sh
python3 compiler/my-try/tests/enum_compatibility_audit.py \
  --baseline /home/alex-ai/curl/vd08-v01-baseline \
  --results /home/alex-ai/curl/FRESH_ENUM_AUDIT
```

The audit has **96 observations**, not 96 supported features: v0.1 executes 22/48
cases with expected results; v0.2 executes 27/48. These totals include deliberate
unsupported probes, syntax adaptations and historical generator limitations; they
are not language coverage percentages. All 27 successful v0.2 programs run cold
and warm, with no warm compilation/linking. The additional retained publication
builds and runs cold/warm too.

## Original eight probes

| Original probe | Pinned v0.1 observation | Current v0.2 | Disposition |
| --- | --- | --- | --- |
| `?E` initialized from a case | Generates bare-enum storage; exact original top-level return fails native build | Executes | v0.2 shorthand repaired; previous take-based probe demonstrates v0.1 lost absence semantics |
| `result<E>` return/local | Exact original top-level return fails native build | Executes | Repaired; separate no-return and previous state/extraction controls execute on both versions |
| String backing | Generator rejects | Preparation rejects | Deferred extension, not demonstrated lost v0.1 support |
| `E::cases()` | C++ has no such member | Preparation rejects | Deferred extension |
| `E::from()` | C++ has no such member | Preparation rejects | Deferred extension; distinct from supported `enum_from_value` |
| `E::tryFrom()` | C++ has no such member | Preparation rejects | Deferred extension |
| Methods | Generator rejects non-simple enum body | Parser rejects | Deferred extension |
| Interfaces | Generator rejects non-simple enum body | Parser rejects | Deferred extension |

The original inputs contain `return 0` at top level. The pinned generator emits a
wrapped integer return into native `int main`, causing an unrelated build failure
in otherwise valid wrapper probes. Separate output-observation adaptations remove
that obstacle. Prior wrapper proofs remain the authority for actual absence/error
states; bare declaration acceptance is not a state proof.

Pseudo-properties `->name`/`->value` also fail v0.1 native compilation. Invoked
method/interface probes fail v0.1 generation, confirming that declaration-only
observations did not conceal a callable implementation. Signed and unsigned helper
coverage remains in the earlier helper/carrier reports; this audit adds no PHP
reflection contract.

## Fields, defaults and constant expressions

| Surface | Evidence | Classification |
| --- | --- | --- |
| Explicit enum field default, class/struct | Executes on both versions | Supported control |
| Class constructor assigning an already initialized enum field | Executes on both | Supported control |
| Struct constructor | v0.1 rejects methods in structs; v0.2 executes | v0.2 extension already implemented |
| Omitted/exact enum parameter defaults | Executes on both | Supported control |
| Imported default | Executes on both with enum-name observation inside the declaring namespace | Supported control; direct imported enum_name observation exposes an unrelated v0.1 name-resolution limitation |
| Nullable/Result fields | Name-first generic field spelling executes on both; type-first spelling fails v0.1 parsing and executes in v0.2 | Syntax-sensitive baseline, no payload gap |
| Bare local or field without enum initializer | v0.1 selects zero/A in these cases; v0.2 rejects | Intentional no-invented-enum-default policy |
| Class constructor body first assigning an uninitialized enum field | v0.1 executes; v0.2 rejects before the body | Intentional initialization-before-body rule; `set:` class/struct alternatives execute in v0.2 |
| Literal and signed literal case values | Executes on both | Supported control |
| Named constant, arithmetic, another case, class constant as case value | Both reject | Deferred broader constant expressions |

The initialization distinctions are normative in `specs/data_initialization.md`
(construction/property initialization before the body and no invented enum default)
and `specs/compact_layout_types.md`. The audit changes neither policy nor compiler.
No new default-selection or definite-assignment analysis is inferred from v0.1's
zero-initialized representation.

Exact enum equality and ternary selection execute on both with supported observation
forms. An inferred ternary followed by enum_name is a v0.1 inference limitation;
an explicit enum destination passes. Enum switch executes in v0.2; the baseline
probe generates invalid `.native_value()` access on the native enum. These are
baseline limitations, not v0.2 regressions.

## VD08-A01 — default-less match

Minimal successful v0.1 / failing default-prelowered v0.2 case:

```php
enum E:int { case A=0; case B=7; }
$e=E::B;
$s=match($e) { E::A => "bad", E::B => "B" };
echo $s;
```

Pinned v0.1 prints `B`. v0.2 rejects during C++ generation:
`prelower_templates: match failure operation requires concrete transport`.
An integer-selector counterpart has the same result. Adding an explicit default
makes both enum versions execute, isolating the missing failure path rather than
enum identity or result transport.

Owner: `CPP_Syntax::generate_match_expression` emits the existing unmatched-match
throw only in ordinary mode. Default prelowering rejects before publishing it.
The semantic contract permits a default-less match and requires an unmatched value
to throw. This is **not** a request for exhaustiveness analysis or a reason to invent
a default arm. The native compiler still needs a valid failure path even for a
source case list that appears exhaustive.

Next discussion: a bounded compiled failure operation through the existing
exception/operation transport owners, preserving selector snapshots, lazy selected
arms and unmatched failure behavior. Verify enum and scalar selectors, side effects,
matched/unmatched execution, exception cleanup and retained recovery. No repair is
included or implicitly authorized by this audit. Broader exception work remains
separate if that probe exposes another prerequisite.

## Retained and regression proofs

`enum_audit_retained.php` publishes imported enum fields and omitted parameter
defaults, changes both defaults from A to B, and checks invalidation. It rejects a
removed referenced case and wrong-type field/parameter defaults, marks output
unready, restores identical artifacts/manifests, changes int8 backing to uint64,
and compares fresh/retained output. Final default-prelowered output prints `BB`.

Existing enum-model, ordinary enum and property-default PHP suites pass. These
establish their preparation/generation assertions, not a rebuilt native compiler
or fresh execution of every ordinary-mode fixture. Prior helper and four-carrier
state/snapshot tests remain linked evidence, not inflated into this audit's counts.

Reflection, string backing, methods/interfaces, broader constant expressions and
exhaustiveness analysis remain individually deferred extensions. Wrapper operators,
conditions, indexed operations and broader selection are still separate scopes.

## Evidence archive

Dev002: `/home/alex-ai/curl/vd08-audit-evidence.tar.gz`. SHA-256:
`7bd6e555961d85e12ebcb6deed697da241fe807b71db5a00510c38fe8edaac5d`.
The manifest pins base `fc3e701ad` and verifies all four test/inventory files against
the executed workspace. Final review verifies the eight original source texts
against the frozen evidence, 96 expectation-checked observations, and 28 zero-work
warm builds with template-forbidden policies (27 source cases plus retained output).
Initial/follow-up/final logs, source fixtures, generated artifacts and manifests are
retained. No source/runtime implementation repair is included.
