# Collection migration and reference advisories
Doc Status: supporting

V-D11 and V-D15 are implemented and verified on 2026-10-21. The batch advances the
original issue ledger from 53/64 to **55/64 resolved (85.9375%)**, +3.125 percentage
points. This is issue count, not an estimate of engineering effort or whole-language
coverage. Historical audit findings remain intact.

## Collection contract and ownership

All six helpers are restored through `scpp::collections`, ordinary core metadata
and the existing shared callback preparation path. Twelve registrations cover
map/filter over typed vectors and hashes, boxed mixed/dynamic tables, and the
sequence/keyed adapters. The [normative contract](../../../../specs/collections.md)
preserves dense vector outputs, exact hash keys/order, type-changing typed maps,
exact-bool filters, fresh outer membership and ordinary element identity.

The pinned v0.1 provider at `75de94c71e37060c564b6ade19850b7686bd457a` supplied the
shared algorithm shape. Its obsolete dynamic shared-hash representation was not
restored: current value iteration and each boxed family's own table owner are used.
Boxed map callbacks receive and return the same family; wider result boxing and
fixed-array adapters remain separate. Empty inputs invoke no callback; exceptions
propagate without publishing partial results.

The separately approved bounded callback prerequisite belongs to the existing C++
specialization/runtime-operation owners. Concrete callback storage owns its capture
object, clones it on value copy and destroys it with the callable. Invocation and
provider adapters preserve physical/native type boundaries, snapshots and synchronous
lifetime. Shared object captures retain ordinary handle identity. Borrowed captures,
reference parameters/returns, other callable arities and asynchronous retention are
not newly supported. Mutation observers in tests are instrumentation, not permission
to mutate collection inputs or their shared elements during traversal.

## Reference advisory contract and ownership

Shared preparation publishes source-located `REF001` records after success. The
compiler lifecycle clears obsolete snapshots; public reads return independent
records. Host delivery renders these records to stderr without changing admission
or generated program output. Source locations reuse the retained token remapping
used by dbg; no backend warning policy is introduced.

Methods, unused traits, multiply-used trait origins and anonymous callables receive
advice without duplicate origin records. Retained edits, no-op, failure/recovery,
declaration/file removal, CRLF columns and reset/fresh agreement are covered.

[Reference semantics](../../../../specs/references.md#v02-advisory-delivery) reconcile
NOTE-059: interior reference returns already reject. A legal whole-parameter alias
followed by normal assignment follows the ordinary value-copy contract, receiving
the declaration advisory without a second claim of undefined copy semantics.
The legal scalar control prints `3:3:1` after mutation through its alias.

## Verification on dev002

The shared 12-slot runner uses PHP 8.5/Clang 18 and CLI fallback because the installed
CLI/FPM versions differ. No full matrix or legacy STAN run was performed.

- `20261021-collection-integrated`: **25 passing tasks**, including 22 default-prelowered
  source programs, nine rejection forms with retained repair/no-op checks, runtime
  ASan/UBSan, and advisory lifecycle checks. Every application manifest forbids
  templates; warm builds compile and link zero units. Coverage includes all twelve
  registrations, scalar/string/nested-vector/object callback values, owned capture
  snapshots and copies, lifetime, callback order, source snapshots, empty/all/none
  results, boxed families and legal alias/copy semantics.
- `20261021-advisory-integrated`: **five passing tasks** for the portable retained proof,
  real CLI stderr/program-output separation, selected project source lists, module
  synchronization and dbg location recovery.
- `20261021-reference-advisories-native`, `logs-13`: the **O2 native compiler builds
  and passes focused PHP/native diagnostic parity under default prelowering**.
  Exact warning bytes agree across initial/no-op, moved source, removal, rejected
  return, repair and preparation reset. This is focused compiler parity, not a
  cumulative native language regression claim.

The native build exposed source-portability defects in earlier work: unsupported
string defaults, cross-file enum reflection, untyped vector literals, nested Storage
access, missing checked record narrowing, vector identity comparison and a nullable
guard relying on short-circuiting. Explicit local types, existing enum-owner helpers,
ordered element comparison and nested guards preserve semantics without extending
the converter or runtime. Native proof fixture failures (stale input snapshot,
shadowed type names and missing catalog initialization) were corrected as well.
All failed attempts remain archived; `logs-10` first built the compiler, `logs-12`
first completed parity; `logs-13` repeats it after remote integration. The final nested-guard correction is covered by that native
proof and the subsequent integrated acceptance run.

[Acceptance archive](collection-advisories-acceptance.tar.gz), SHA-256:
`ff7ca07a9cb2bc6f27aafa10d776e9a3c0e277e83211eb5c68b20e7bb159ddc8`.
It retains 2,137 evidence files, including failed and passing runs, command journals,
source fixtures, manifests and the final audit of **3,343 executable inputs with
zero mismatches**. The [30-file source delta](collection_advisories_source_delta.json)
records this goal’s changes after integrating remote `9f11c14bd`. Planning documents
and unrelated IDE notes are outside this executable-source audit. The earlier
checkpoint used `a7e8881ec`; final verification includes concurrent string work and
its nine passing snapshot tests (`20261021-collection-snapshot-integration`). All
**39 focused tasks** pass on the combined source. The collection commit is
`fd231cc79`; diagnostic changes complete the recorded overlay.

## Separate gaps

Default prelowering still lacks the tested mixed keyed initializer physical/native
entry bridge, dynamic scalar boxing/data initialization, and dynamic indexed-read
key transport. Fixture providers isolate boxed collection behavior from those gaps;
they do not repair them. General callable transport, wider boxed callback return
conversion, concurrent iteration and mutation semantics remain separate. Earlier
ordinary-mode callback evidence is not promoted to a default-mode claim here.
