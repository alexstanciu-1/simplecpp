# Storage registration and PHP-hosted bootstrap checkpoint
Doc Status: planning

Date: 2026-10-21. Candidate: `6fcbed03` plus the Storage registration changes
committed with this note. This is evidence, not a language specification.

## Implemented boundary

The compiler module registers Storage, Keyed_Storage and Key_Storage_List using
ordinary runtime metadata. Inline providers can declare a static construction
factory and implicit default construction; source-record handle formation is
checked in shared preparation. Indexing/iteration and concrete handle/vector
transport reuse existing owners. A shared-header layout scheduling issue and
zero-input runtime factory dispatch were corrected in those existing owners.

No cursor widening, borrowed slots, nested writable container paths or iterator
membership mutation is added. The PHP-hosted compiler has not compiled itself.

## Bootstrap attempt

`/tmp/bootstrap-attempt-20261021-storage.py` snapshots the seven compiler stage/core
directories and the existing native proof driver, converts them using
`tools/php_portability/convert.php`, installs the filesystem/OS/JSON framework,
and runs the PHP-hosted Compiler through Project_Compilation initialize, sync,
prepare, validate and C++ emission. The attempt stops on the first failed phase.
The existing native driver's project configuration supplies the runtime modules.

PHP 8.5 CLI, OPcache disabled, Xdebug disabled for the bootstrap, 4 GiB memory limit;
`/usr/bin/time -v` measures each process separately. No competing test/build task
ran during this measured attempt. PHP heap uses `memory_get_peak_usage(true)`.

| Phase | Wall time | Peak RSS | Outcome |
| --- | ---: | ---: | --- |
| PHP-to-PHS conversion | 6.63 s | 182.9 MiB | 256 PHP files converted |
| PHP-hosted bootstrap | 2.88 s | 126.2 MiB | Parsing rejected parameter syntax |

Framework installation leaves 266 PHS files totaling 1,898,504 bytes. Bootstrap
initialization completes at 192 ms; shutdown records 100 MiB peak PHP heap.
It does not reach semantic preparation or C++ emission, so these numbers cannot
estimate full compiler build time or its eventual peak memory.

The first diagnostic is `Expected parameter type` at
`01_prepare_inputs/module.phs`, byte 405, line 11. The converter emits:

```php
public static function configuration($inputs Storage<module_input>): Keyed_Storage<module>
```

The v0.2 parameter parser expects `Storage<module_input> $inputs`. The same mismatch
appears for other converted generic/nullable parameters, including framework
helpers. This is the next observed blocker; neither converter nor parser is
changed in this slice. Later semantic/backend blockers remain unexamined.

Raw evidence: `/tmp/bootstrap-attempt-20261021-storage/` contains source hashes,
the exact converted snapshot, project/probe configuration, phase JSON, GNU time
reports and diagnostics.

## Focused verification

- `tests/compiler_storage.py`: ordinary and concrete generated programs pass
  with identical output; concrete publication forbids application templates.
  Covers alias mutation, handles surviving removal/replacement, monotonic keys,
  presence/removal, foreach, named/full snapshots and independent default fields.
  `/tmp/storage-v02-full5/` contains the native build/execution evidence.
- Admission, recovery and malformed metadata checks pass, as do type capabilities,
  runtime types/catalog/type bindings, constructors, value/collection probes and
  data construction. Evidence: `/tmp/storage-v02-regression/` (nine PHP suites).
- `tests/prelower_substitution.py --indexing` passes ordinary/concrete parity at
  O0, O2 and with AddressSanitizer/UndefinedBehaviorSanitizer, plus warm-cache,
  body/layout edit and restore checks. Evidence: `/tmp/storage-v02-indexing/`.
- Prelower closure coverage and acceptance-reporting checks pass. The older
  unsupported-boundaries fixture fails at `Missing ordinary representation: local`.
  An isolated `git archive HEAD` base (`6fcbed03`) reproduces that exact failure;
  it is pre-existing and remains untouched. Evidence:
  `/tmp/storage-v02-prelower-contracts/`, `/tmp/storage-v02-base-check2/`.

The shared runner falls back to CLI because this sandbox cannot bind FPM sockets.
These are generated-program and PHP-host checks, not a native compiler rebuild.

## Parameter emission retry

Candidate: `883592c0` plus the converter parameter repair committed with this
section. Method signatures and constructor expansion now emit type-first parameters
uniformly; fields/locals retain their existing syntax. Nullable-parameter textual
expectations and the owning guides are updated. A conversion-to-v0.2-parser proof
covers scalar, nullable, vector/hash, Storage and object-hash parameters across
methods, constructors, interfaces and expanded traits. That proof and the existing
nullable-parameter, method-signature and container-return suites pass through the
shared runner (`/tmp/parameter-syntax-v02-check/`). No native rebuild was performed.

The same isolated PHP 8.5 harness and settings were rerun after those checks ended.
`/tmp/bootstrap-attempt-20261021-parameters.py` and its matching output directory
retain the source snapshot, hashes, converted PHS, probe, logs and measurements.

| Phase | Wall time | Peak RSS | Outcome |
| --- | ---: | ---: | --- |
| PHP-to-PHS conversion | 5.29 s | 183.2 MiB | 256 PHP files converted |
| Framework assembly | 0.05 s | 30.9 MiB | 266 total PHS files, 1,898,519 bytes |
| PHP-hosted bootstrap | 4.02 s | 162.7 MiB | Framework parameter syntax rejected |

Runtime initialization completes at 164 ms. Peak PHP heap is 140 MiB. Parsing now
reports only the separately maintained `scpp_framework/collections.phs` (byte 140)
and `scpp_framework/processes.phs` (byte 144), both still using postfix parameter
types. Their source files under `tools/php_portability/runtime/` are copied by
`install_native_runtime.php`, not generated by the converter. They remain untouched
as the next blocker. Semantic preparation and C++ emission were not reached;
full compilation time and memory remain unknown.

## Framework parameter retry

Candidate: `a69264f9` plus the two framework signature changes committed with this
section. Only the vector parameter placement changes in `collections.phs` and
`processes.phs`; framework assembly copies those corrected source files as usual.
The full bootstrap supplies the relevant parser verification; no extra native
build or unrelated repair was attempted.

The same isolated PHP 8.5 harness and settings were rerun using
`/tmp/bootstrap-attempt-20261021-framework.py`. Its matching output directory
retains the exact source/converted snapshot, hashes, phase JSON and time reports.

| Phase | Wall time | Peak RSS | Outcome |
| --- | ---: | ---: | --- |
| PHP-to-PHS conversion | 5.04 s | 182.9 MiB | 256 PHP files converted |
| Framework assembly | 0.06 s | 31.0 MiB | 266 total PHS files, 1,898,515 bytes |
| PHP-hosted bootstrap | 4.36 s | 165.0 MiB | Relationship preparation rejected |

Runtime initialization completes at 172 ms and full project parsing at 4,104 ms.
Peak PHP heap is 142 MiB. The next diagnostic is
`S2S needs one resolved type for \Iterator`, repeated during relationship preparation;
the batch also reports `Relationship target must be a source class, struct or interface`.
The converted `03_parse/structures/iterators.phs:6` declares
`interface child_iterator_i extends \Iterator`, preserved from the authored PHP.
No Iterator binding or relationship repair is made. Semantic preparation does not
complete and C++ emission is not reached, so full-bootstrap time/memory remain unknown.

## Typed Iterator retry

Candidate: `dd8bc541` plus the bounded typed Iterator implementation committed with
this section. Global Iterator has a distinct non-storable protocol role, source
relationships carry membership, and completed source type contracts retain the
five selected methods with exact key/value types. Foreach prepares ordinary source
calls and retains one shared receiver. Both C++ modes consume their existing
source-method ABI directly, without applying the native-container range bridge to
already-concrete source results. General runtime inheritance is not enabled.

`tests/typed_iterator.py` passes ordinary and concrete execution and admission
checks (`/tmp/iterator-v02-full/`). Tests cover interface dispatch, inherited
overrides, direct implementation, string keys, shared record yields, temporary
receiver evaluation, replacement of the original receiver, empty iteration,
continue and early break. Incremental signature edits exposed a missing equality
comparison in the new protocol facts; method/type comparison fixes stale foreach
bindings. Final admission/recovery and relationship checks pass in
`/tmp/iterator-v02-final-contracts/`. Type capabilities, provider iteration, runtime
catalog, interface and inheritance PHP suites pass in `/tmp/iterator-v02-regression/`.

The type-catalog test's fixed semantic definition/type/exposure counts are adjusted
for the new marker. Its later `C++ binding count changed` assertion fails identically
on an isolated archive of unmodified `dd8bc541`; it remains unchanged. Baseline
evidence: `/tmp/iterator-v02-base-check/`. No native compiler rebuild is claimed.

The bootstrap ran after preceding checks finished, with the same PHP 8.5 CLI,
OPcache/Xdebug settings and 4 GiB limit. Exact snapshots, source hashes, diagnostics,
phase JSON and GNU time reports are in `/tmp/bootstrap-attempt-20261021-iterator/`;
the harness is `/tmp/bootstrap-attempt-20261021-iterator.py`.

| Phase | Wall time | Peak RSS | Outcome |
| --- | ---: | ---: | --- |
| PHP-to-PHS conversion | 5.94 s | 184.4 MiB | 257 PHP files converted |
| Framework assembly | 0.05 s | 30.9 MiB | 267 PHS files, 1,908,220 bytes |
| PHP-hosted bootstrap | 4.49 s | 165.9 MiB | Exception relationships rejected |

Initialization completes at 161 ms; parsing completes at 4,199 ms. Peak PHP heap
is 142 MiB. The 56 unresolved Iterator diagnostics are gone; the eight relationship
target failures remain for the portability exception hierarchy. The first is
`scpp_portability_exception extends Exception`, whose base is runtime-owned rather
than a source declaration. No exception or Storage_Cursor widening repair is made.
Relationship preparation still stops before type contracts/method bodies and C++
emission, so this does not prove the whole converted compiler's Iterator use or
full-bootstrap time/memory.

## Exception hierarchy and prelowered transport retry — 2026-10-21

Candidate: `68e75704` plus the bounded exception implementation in this change.
Canonical Exception ancestry now admits source shared classes, including descendants
and aliases. Existing relationship work owns membership and invalidation; the type
contract owns polymorphic destruction. Root widening and throw/catch retain the
original shared payload. Runtime-instance lookup falls back to the registered root
when source lookup has no declaration; borrowed `$this` calls use compiled transport.
The runtime payload base is template-free. Native factory/typed catch templates stay
behind the compiled boundary. Finally uses inline layout-probed exception state and
scalar storage with a pending-return flag; replay moves the saved scalar value.

Root message semantics are explicit: runtime factory messages preserve bytes;
source-created exceptions have the default root message `Exception`. The portability
framework's `getMessage()` remains its ordinary source detail getter, not an override
of native root reporting. General runtime inheritance, new runtime constructors,
multiple catches and Storage_Cursor widening are not added.

Focused evidence:

- `/tmp/exception-hierarchy-final/`: six ordinary/concrete programs, runtime legacy
  factory/subtype/identity/destruction proof and PHP ancestry rejection/invalidation/
  recovery. Concrete production builds enforce full transitive template-free headers.
  Cases include current-instance root access, embedded NUL messages, once-only throw
  evaluation, retained catch lifetime, unmatched and foreign propagation, absent
  throws and nested/replacing finally. All pass.
- `/tmp/exception-v02-regression/`: all 17 existing generated exception programs pass,
  plus the retained semantic rejections and repair checks.
- `/tmp/exception-v02-finally-regression/`: all 34 existing generated finally programs
  and 19 preparation rejections pass. The subsequent replay-move refinement is
  exercised by the final ordinary/concrete nested string-return proof above.
- `/tmp/exception-v02-semantic-regression/`: runtime catalog, typed Iterator admission,
  inheritance and interfaces pass. No native compiler rebuild was performed.

After test work completed, the existing harness ran conversion/framework assembly
and a fresh PHP-hosted bootstrap sequentially. PHP 8.5 CLI used OPcache disabled,
Xdebug off and a 4 GiB memory limit for the bootstrap. Source snapshots, hashes,
phase JSON, GNU time files, metadata copy, candidate code hashes and diagnostics are
in `/tmp/bootstrap-attempt-20261021-exceptions/`; reproduction harness:
`/tmp/bootstrap-attempt-20261021-exceptions.py`.

| Phase | Wall time | Peak RSS | Outcome |
| --- | ---: | ---: | --- |
| PHP-to-PHS conversion | 6.87 s | 184.6 MiB | 258 PHP files converted |
| Framework assembly | 0.07 s | 31.0 MiB | 268 PHS files, 1,918,244 bytes |
| PHP-hosted bootstrap | 47.97 s | 289.2 MiB | Later semantic preparation rejected |

Peak PHP heap: 270 MiB. Initialization completes at 210 ms; parsing completes at
4,640 ms. There is no OOM or PHP fatal error. The original eight exception relationship
errors are gone. Preparation reports 60 distinct diagnostic messages, beginning
with the ownership-boundary rejection. Other categories include portable helper
bindings, parameter defaults, member/modifier restrictions, enum initialization,
nullable/value boundaries and unresolved Storage_Cursor. These are a failed
preparation round's aggregate diagnostics, not 60 independently diagnosed root causes.
C++ emission is not reached. The elapsed time and memory cover only progress to
this blocker and cannot establish full-bootstrap cost. No downstream fix is made.

A separate read-only diagnostic-owner replay initially used default PHP debugging
settings and timed out at 240 s (`/tmp/exception-bootstrap-locate-logs/`). It is not
included in the measured bootstrap above; the owner lookup then passes in 58.35 s with the same OPcache/Xdebug settings as
the measured attempt (`/tmp/exception-bootstrap-locate-final-logs/`). This second
run includes diagnostic inventory work and is not substituted for the timed result.
It confirms zero relationship failures. Retained owner details are saved as
`/tmp/bootstrap-attempt-20261021-exceptions/failure-owners.json`.

The first located ownership failure is `source_record::__construct`, authored at
`compiler/my-try/01_prepare_inputs/structures.php:81` and converted at
`01_prepare_inputs/structures.phs:75`: a shared `module` parameter is assigned
into the `weak<module>` field `module_reference`. An isolated fixture reproduces
the same preparation rejection (`/tmp/exception-next-blocker/`). This weak-reference
assignment boundary is the next concrete blocker; it is not repaired. Storage_Cursor
widening and other reported diagnostics also remain untouched.


## Portable weak operation retry — 2026-10-21

Candidate: `14e8c4be` plus the portable-weak overlay retained in
`/tmp/bootstrap-attempt-20261021-weak/candidate.diff`; source hashes and revision are
in that directory. The facade adds `weak`, identity-preserving under PHP, and maps
`weakref_get` to `lock`. The legacy runtime exposes `scpp::lock`; v0.2 reuses existing
ownership preparation and lowering. Explicit derivation is authored at shared-to-weak
writes; weak copies/null resets remain direct. No ownership conversion policy changes.

Measured PHP8.5 CLI, OPcache CLI disabled, Xdebug disabled, 4 GiB memory limit:

| Stage | Wall time | Peak RSS |
| --- | ---: | ---: |
| Convert 258 PHP files | 5.92 s | 184.8 MiB |
| Framework assembly | 0.06 s | 31.0 MiB |
| PHP-hosted bootstrap | 52.58 s | 289.3 MiB |

Assembled input: 268 PHS files, 1,917,038 bytes. PHP peak heap: 270 MiB.
Parsing completes at 5.003 s; preparation fails without PHP fatal/OOM. No C++ emission.
Focused verification overlapped this run; this is a single observed attempt, not
an isolated performance comparison. Bootstrap CPU utilization was 99%.

First diagnostic: unresolved `scpp_portability_object_cast` in
`source_record::owning_module` (`01_prepare_inputs/structures.phs`). The original
constructor weak assignment no longer appears among failed owners. The read-only
owner replay in `/tmp/weak-bootstrap-audit-logs` records 42 object-cast owners and
54 ownership-boundary owners (not 54 independent root causes); no `weakref_get`
resolution failures remain. No subsequent bootstrap blocker was fixed.

Focused proofs: `/tmp/portable-weak-proof-final`, the retained
`tests/portability/weak_operation.py` runner; PHP/v0.1/ordinary-v0.2 identity,
single evaluation, weak copying, clearing and native expiration. Prelowered
locked-class null comparison fails native compilation because emitted `.has_value()`
is absent on the concrete shared handle. The test records this expected defect;
it is not a full prelowered pass. A separate prelowered live-owner proof passes
derivation, acquisition, single evaluation, identity and clearing. Host ownership and inheritance checks pass in
`/tmp/portable-weak-host`; corrected stale weak-field fixture paths/qualified C++
names and portable helper arity checks pass in `/tmp/portable-weak-field-final-02`.
Storage_Cursor widening and the discovered backend defect remain out of this slice.
