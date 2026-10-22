# VD07-A03 read-only owned compatibility packs
Doc Status: planning

## Approved scope — 2026-10-22

The user authorized `T &...$xs` as read-only owned value packs for canonical
int/float/bool/string through named free/static/instance source calls, with no
caller aliasing. The [contract](../../../../specs/instance_methods_v0_2.md#read-only-owned-compatibility-packs--2026-10-22)
preserves argument snapshots, copy restrictions, value returns and cleanup.
Ordinary writable packs and explicit fixed references retain their existing meaning.

Source parameter preparation normalizes only the admitted spelling to value mode
with read-only storage access. Shared callable validation admits read-only value
packs without relaxing fixed value parameters. Existing semantic mutation/escape
checks consume the pack access; the C++ emitter keeps element snapshots as owned
values and applies read-only access to the completed vector. No new retained
structure, runtime implementation or caller-reference transport is introduced.
Anonymous and wider compatibility packs remain explicitly rejected. Runtime
metadata/name-free callable contracts continue through their original variadic
admission path; source normalization does not extend their bridges.

## Acceptance — 2026-10-22

VD07-A03 is implemented and verified in the agreed canonical scalar scope. Together
with VD07-A01 reference defaults and VD07-A02 value-pack transport, it closes V-D07's
v0.1 compatibility scope. Named arguments and call-site unpacking remain explicit
baseline-unsupported extensions under the [original audit](call_compatibility_audit.md);
this closeout does not claim general PHP call compatibility.

Overall: **61/64 resolved (95.31%)**, comprising 57 implemented/repaired entries and
four contract/environment reconciliations. V-R08 remains partial; V-D01 and S02
remain open. True caller-aliasing packs, wider elements, explicit const variadic
spellings and other callable bridges remain separate. No runtime changes, full
matrix or native compiler rebuild.

## Dev002 verification

Source base `a409554d2`, with ten compiler/test/expectation files pinned in
`/home/alex-ai/curl/20261020-work/readonly-pack-manifest.json`. Final payload hashes
match the verified local files. Production default prelowering is used throughout,
with `application_templates: forbidden`. Shared runners use twelve slots and CLI
fallback through the existing task-local PHP8.5 launcher:

```sh
export PATH=/home/alex-ai/curl/value-pack-bin:$PATH
export MY_TRY_PHP_EXECUTOR=cli
python3 compiler/my-try/tests/readonly_pack_prelower.py \
  --baseline /home/alex-ai/curl/vd08-v01-baseline --results FRESH_DIRECTORY
python3 compiler/my-try/tests/value_pack_prelower.py --results FRESH_VALUE_REGRESSION
```

- `20261022-readonly-pack-01`: 43 executable cases and retained publication pass,
  including scalar free/static/instance calls, empty/one/many tails, fixed/default
  prefixes, repeated caller variables, int/string snapshots, independent local-copy
  mutation, fixed-reference writes without pack aliasing, read-only forwarding and
  local aliases, returned values, argument/conversion/callee failures and receiver
  order/lifetime. All 44 publications execute cold/warm; warm builds compile/link
  nothing. Five programs additionally pass ASan/UBSan for owned return, argument/
  callee failure and receiver success/failure cleanup.
- Fifteen source rejection assertions pass for writes, append, replacement, alias
  writes, cleanup, writable forwarding, escaping reference/capture, wider elements,
  explicit const spellings, anonymous callables, pack defaults/order and constructors.
  The sixteenth source correctly rejects vector-element removal at its earlier
  existing admission gate; the initial test expected a read-only diagnostic instead.
  The expectation alone was corrected. `20261022-readonly-pack-rejection-final`
  proves that assertion and verifies all ten final source hashes. Acceptance combines
  these runs; the initial failed assertion is preserved, not reported as a green run.
- Retained tests cover empty/nonempty/one-element helper demand, ordinary/read-only
  access changes, mutation rejection, ready-output withdrawal, identical recovery
  and fresh compiler parity. Existing references, method-parameter and callable PHP
  suites pass. These PHP checks do not claim a native compiler rebuild.
- `20261022-readonly-pack-value-regression`: all 33 tasks pass, including 26 ordinary
  programs, three rejections, retained publication and three PHP suites. Ordinary
  pack mutation still affects only the owned pack. Five sanitizer executions and
  all 27 cold/warm publications pass.
- `20261022-readonly-pack-audit`: nine paired baseline/current cases pass reviewed
  source-hash/stage/stdout expectations and retained recovery. v0.1 executes six;
  v0.2 executes seven. Writes and repeated-alias writes remain rejected; the current
  instance-method control executes while baseline packing fails. Empty and read-only
  reference-pack expectations now execute in v0.2. Baseline expectations are unchanged.

## Expanded baseline observations

The first run records 26 additional observations against pinned v0.1
`75de94c71e37060c564b6ade19850b7686bd457a`. Eighteen execute: all tested free/static
scalar packs, their empty/one-element controls, repeated int input and local-copy
mutation. Eight instance-method probes fail native compilation at the existing
method-packing boundary. Consequently current instance-method support follows the
agreed v0.2 contract; it is not claimed as demonstrated executable v0.1 parity.
Read-only storage is an owned value vector, never caller-element references.

Historical vector-element removal remains unsupported independently of read-only
access. This goal neither repairs that wider operation nor changes its diagnostic
precedence. Initial and final evidence are retained together.

Evidence archive: `/home/alex-ai/curl/readonly-pack-evidence.tar.gz`, SHA-256
`18479f4ba464ba5766601e998273b542b88c9410429dd752754610bf7c2c3456`.
Logs, source, generated publications and hash manifest are retained; native caches
are excluded.
