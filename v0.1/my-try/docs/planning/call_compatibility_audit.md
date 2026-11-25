# V-D05 / V-D07 compatibility audit
Doc Status: planning

## Current follow-up

The subsequent [VD07-A02 repair](value_pack_transport.md) supplies scalar value-pack
transport with dev002 default-prelowered snapshots/cleanup proofs. The subsequent
[A03 repair](readonly_packs.md) admits read-only owned scalar packs and closes V-D07
in its agreed compatibility scope. Named/unpacking extensions, true aliasing packs
and wider domains remain separate. The [A01 repair](reference_defaults.md) now verifies both read-only
and writable scalar defaults. Expanded baseline probes execute all twelve explicit
writable scalar/free/static/instance calls and fail native build for all twelve
omitted calls; fresh omitted writable storage is an explicit v0.2 extension. The audit findings and failures below are historical evidence.

## Outcome — 2026-10-22

V-D05 is reconciled within the v0.1 compatibility milestone: neither static
interface declarations nor selection through interface-typed qualifiers execute
in the pinned v0.1 baseline. Current v0.2 rejects these explicitly. This is an
extension deferral, not a permanent prohibition or a new implementation.

V-D07 remains open. Named arguments and call-site unpacking reject in both
versions, but reference defaults contain genuinely executable v0.1 cases. The
positive controls also expose missing nonempty value-pack construction under
v0.2 default prelowering. Read-only use of a reference-marked variadic is accepted
by v0.1 without providing caller-element references; its compatibility disposition
requires discussion rather than inheriting a write-through contract.

Overall: **60/64 resolved (93.75%)**, one partial (V-R08) and three other open
(V-D01, V-D07, S02). This adds one verified contract reconciliation (+1.5625 points),
not the hoped-for two closures. No compiler/runtime implementation changes, full
matrix rerun or native compiler rebuild were made. S02 stays deferred.

## Paired observations

| Surface | Pinned v0.1 | Default-prelowered v0.2 | Classification |
| --- | --- | --- | --- |
| Named ordered/reordered/omitted, duplicate/unknown/mixed, effects, instance method | Generation rejects unsupported AST lowering | Generation rejects | No demonstrated lost v0.1 support; named-binding extension remains separate |
| Free/method unpacking, empty/nonempty, fixed prefix | Generation rejects | Generation rejects | Deferred extension; distinct from ordinary typed packs |
| Writable reference with default, explicit argument | Executes and writes caller: `4:4` | Declaration rejects | Executable source compatibility gap, although the default is unused |
| Writable reference with default, omitted argument | Native build fails: generated function requires argument | Declaration rejects | No demonstrated omitted writable-default semantics |
| Read-only reference default, omitted/explicit int, string, instance method | Executes: `7`, `3:7`, `saved`, `7` | Declaration rejects | **VD07-A01**, bounded reference-default compatibility gap |
| Reference-marked pack, empty/read-only use | Executes: `0`, `6:3` | Declaration rejects | **VD07-A03**, accepted spelling needs explicit disposition; no caller write-through proved |
| Reference-marked pack writes, including repeated caller argument | Native build rejects mutation of const elements | Declaration rejects | Do not implement alias-preserving reference packs based on these observations |
| Ordinary positional/default/fixed reference/alias order | Executes | Executes | Supported controls, including `1:2:2:2` alias/snapshot observation |
| Empty value pack | Executes | Executes | Supported control |
| Nonempty free/static value packs, local-copy mutation, snapshots | Executes | Native build fails constructing concrete vector from scalar arguments | **VD07-A02**, existing pack lowering lacks concrete initialization transport |
| Nonempty instance-method value pack | Native build fails because v0.1 does not pack the call | Same concrete-vector construction failure | Current documented v0.2 support gap, not demonstrated v0.1 regression for this form |
| Direct value-pack mutation | Native build rejects const elements | Concrete pack construction fails first | Baseline does not support direct mutation; local-copy probe isolates valid v0.1 behavior |
| Static interface declaration, concrete/interface/type/expression calls | Native build rejects pure static member | Generation rejects | V-D05 extension deferral reconciled |
| Empty interface with concrete class static target | Executes | Executes | Concrete selection remains supported |
| Empty-interface local/expression qualifier selecting implementation static | Native build fails (missing member/unresolved type) | Generation rejects | No dynamic static-implementation selection established |
| Ordinary interface instance dispatch, concrete static controls | Executes | Executes | Supported controls |
| Side-effecting expression qualifier | Baseline type-resolution build failure for this probe | Executes `9:9` | v0.2 control; no baseline runtime claim. The normative v0.2 evaluated-qualifier policy remains unchanged |

`value-pack-local-copy` observes `9:3`; `pack-snapshot` observes `12:2` in v0.1.
The latter cannot yet execute through default prelowering. Passing an observation
recording task is not a claim that its source compiled or behaved successfully.

## Genuine gaps brought back for discussion

### VD07-A01 — reference defaults

```php
function read(const int &$n = 7): int { return $n; }
echo read();
```

The baseline prints `7`; shared preparation rejects the v0.2 declaration. The
owner is parameter-default preparation and ordinary call argument lifetime/binding.
A future proposal must distinguish a call-local owned default for read-only
borrowing from writable references to caller storage. Do not infer writable
omitted-argument support from an explicit-argument call that never uses its default.

### VD07-A02 — concrete value-pack construction

```php
function sum(int $base, int ...$xs): int {
    foreach ($xs as $x) { $base += $x; }
    return $base;
}
echo sum(1, 2, 3);
```

The baseline prints `6`; v0.2 generates a concrete vector brace-construction from
concrete scalar arguments without a matching constructor. The existing owner is
`CPP_Declarations` call sequencing/pack construction, with the existing initializer
transport owner as the candidate integration path. Empty packs already execute.
A repair must preserve left-to-right conversions/snapshots, owned pack storage,
copy restrictions and failure cleanup. No new unpacking or reference-pack design
is needed to repair this value-pack path. Recommend discussing this slice first.

### VD07-A03 — reference-marked read-only packs

```php
function sum(int &...$xs): int {
    $n = 0;
    foreach ($xs as $x) { $n += $x; }
    return $n;
}
$n = 3;
echo sum($n, $n), ":", $n;
```

v0.1 prints `6:3`, while its generated parameter is an owned-call-pack's const
vector reference, not a vector of caller references. Writes reject. v0.2 rejects
the declaration explicitly. Decide whether this marker should remain an explicit
incompatibility or have a bounded migration/admission policy. The audit does not
authorize a new reference-variadic model or silently relabel accepted v0.1 code.

## Verification and reproduction

Source base `9bd365405`; only four test/expectation files are overlaid, pinned by
`/home/alex-ai/curl/20261020-work/vd05-vd07-manifest.json`. Baseline:
`75de94c71e37060c564b6ade19850b7686bd457a`, the same isolated snapshot used for V-D08.
Its relevant generator/catalog paths were previously compared with release/0.1.78.
This is a pinned-baseline comparison, not a new latest-release search.

Host dev002, `/home/alex-ai/curl/20261020-work`. Shared 12-slot runner uses CLI
fallback because CLI/FPM versions differ. Current publications use production
default prelowering and assert `application_templates: forbidden`.

```sh
python3 compiler/my-try/tests/call_compatibility_audit.py \
  --baseline /home/alex-ai/curl/vd08-v01-baseline \
  --results /home/alex-ai/curl/FRESH_CALL_AUDIT
```

Initial 33 pairs: `20261022-call-audit-01`; eleven focused follow-ups:
`20261022-call-audit-followup`; consolidated reviewed-expectation run:
`20261022-call-audit-final`. The inventory has 44 paired sources (88 observations).
Expectations retain source hashes, failure stage, successful behavior and observed
stdout. Failure logs retain actual diagnostics; generation/build failures are not
counted as executions. These are new focused sources; original frozen verification
findings remain unchanged and linked from the issue ledger.

The retained API proof changes a concrete static default, checks caller
invalidation, rejects named/unpack/reference-default/static-interface edits,
withdraws ready publication, restores identical artifacts/manifests and checks
fresh parity. Its final source prints `79`. Every successful current source and
the retained publication run cold/warm; warm builds require zero compilation/link.

The final run records 19/44 baseline executions and 9/44 current executions; these
include deliberately unsupported inputs and are not language-coverage percentages.
All nine successful current sources plus the retained publication pass cold/warm
execution with ten zero-work warm builds. No current failure was hidden by
switching off prelowering or adapting the source to avoid the tested feature.

Evidence archive: `/home/alex-ai/curl/call-audit-evidence.tar.gz`, SHA-256
`a0489f8133b6bc4e4e90c2b9dd19f0cfa72b983ead61fd693271d1b0c721581c`.
All four test/expectation hashes match the executed workspace.
