# VD07-A02 scalar value-pack transport
Doc Status: planning

## Scope and implementation — 2026-10-22

Nonempty canonical `int`, `float`, `bool` and `string` packs now use concrete
initializer-buffer transport for free functions, static methods and instance
methods with default prelowering. No runtime implementation changed.

Call preparation resolves the existing registered list-initialization binding for
the pack's `vector<T>` through `Initialization_Preparation`. The initialization
catalog retains that settled binding; no new syntax, registry or retained record
is introduced. Backend emission only reads the binding. Copyability and parameter
admission remain with the existing shared semantic owners.

`CPP_Declarations` still evaluates/converts every explicit argument once, in source
order, preserving its value snapshot or fixed-reference binding. Only after those
snapshots settle does it construct the owned vector. `CPP_Initializer_Buffer` now
accepts settled binding/key/value types instead of a literal AST, so literals and
packs share the existing compiled buffer/factory implementation. Pack storage and
buffer lifetime stay within the call expression. The scalar gate leaves wider
concrete nonempty packs explicitly rejected; empty packs and ordinary emission
retain their existing paths.

This repairs VD07-A02 without closing V-D07: VD07-A01 reference defaults and
VD07-A03 reference-pack spelling remain open. Overall stays **60/64 (93.75%)**,
with V-R08 partial and V-D01/V-D07/S02 open. Constructor, provider and callback
variadic bridges, broader element types, reference packs and unpacking remain
separate. No full matrix or native compiler rebuild was run.

## Verification on dev002

Source base `130452246`, plus seven compiler/test/expectation files pinned in
`/home/alex-ai/curl/20261020-work/value-pack-manifest.json`. The shared runner uses
12 task slots. Production default prelowering is used; every executable publication
requires `application_templates: forbidden`.

```sh
export PATH=/home/alex-ai/curl/value-pack-bin:$PATH
export MY_TRY_PHP_EXECUTOR=cli
python3 compiler/my-try/tests/value_pack_prelower.py \
  --results /home/alex-ai/curl/FRESH_VALUE_PACK
python3 compiler/my-try/tests/call_compatibility_audit.py \
  --baseline /home/alex-ai/curl/vd08-v01-baseline \
  --cases pack-control,pack-empty-control,value-pack-write,value-pack-local-copy,pack-snapshot,method-pack,static-pack,ref-pack-read,ref-default-omitted,const-ref-default-omitted \
  --results /home/alex-ai/curl/FRESH_VALUE_PACK_AUDIT
```

The task-local launcher links to `/usr/bin/php8.5`. The previous modern-bin launcher
adds mbstring explicitly; the server now loads it from normal PHP configuration,
so that override emits startup warnings into JSON responses. Initial runs
`20261022-value-pack-01` (FPM) and `-02` (CLI) failed JSON decoding, not compiler
acceptance. No system PHP configuration or shared launcher was changed.

`20261022-value-pack-03` passes the initial 19 programs plus retained publication,
including five sanitizer runs. Expanded `20261022-value-pack-final` passes every
execution but has one incorrect test expectation: constructor packs reject at
`Constructor parameters require fixed by-value inputs`, earlier than the expected
bridge gate. The assertion was corrected without changing compiler behavior.
The final acceptance directory is `20261022-value-pack-acceptance`.

Final coverage:

- 26 source programs, including all four scalar families through all three call
  forms; empty/one/many tails and fixed/default prefixes.
- Integer and string snapshots, aliased fixed-reference prefix, receiver-before-
  arguments ordering, owned pack return and independent pack/local-copy mutation.
- Later argument failure and nullable-to-int conversion failure prevent later
  evaluation/callee entry. Callee failure preserves source exception identity and
  runs finally. Receiver clearing during argument evaluation keeps the receiver
  alive through the call and destroys it once on success or failure.
- ASan/UBSan on argument failure, callee failure, receiver success/failure lifetime
  and returned string packs: five focused sanitizer builds/executions.
- Existing vector/hash/mixed/dynamic literal construction exercises the generalized
  initializer entry point. Data-preparation, initialization and callable PHP suites
  pass independently.
- Three explicit rejection controls: wider nonempty packs, reference packs and
  constructor packs. These do not claim coverage of other deferred bridges.
- Retained empty/nonempty/one-element transitions retire and recreate helper
  demand; rejected reference-pack edit withdraws ready publication; restoring the
  source restores identical artifacts/manifests and agrees with a fresh compiler.
- All 27 executable publications (26 sources plus retained) run cold/warm; warm
  builds compile and link nothing. This is generated-program proof, not a native
  rebuild of the PHP-authored compiler.

`20261022-value-pack-audit` records ten pinned-v0.1/current pairs, with seven
executions in each version for different subsets. Six current expectations move
from native-build failure to execution. The baseline's direct pack-mutation and
instance-method packing failures remain historical limitations. Reference-default
and reference-pack expectations remain unchanged. The audit's original 44-pair
results remain historical; this goal does not claim a new complete audit run.

Final acceptance passes all 33 runner tasks. The evidence archive is
`/home/alex-ai/curl/value-pack-evidence.tar.gz`, SHA-256
`a49d456012f0faee1e3018fe5ed2cd6de4d878ee8d0470aebec1cc82bd493e71`.
All seven executed source/test hashes match the overlay manifest. Logs, source,
generated publications and manifests are archived; native caches are omitted.
