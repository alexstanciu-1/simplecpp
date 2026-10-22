# VD07-A01 reference defaults
Doc Status: planning

## Completed writable follow-up — 2026-10-22

**VD07-A01 is implemented and verified within its canonical scalar scope.** Named
source free/static/instance functions admit literal defaults on writable references
to int/float/bool/string. Explicit calls retain exact caller storage and write-back;
omitted calls initialize fresh mutable owners on every invocation. Reference results
whose origin was omitted reject during preparation, including uses that would copy
the result. Explicit reference-return origins remain supported, even when another
parameter uses its default. Wider defaults and other callable bridges remain separate.

The [normative contract](../../../../specs/native_reference_safety.md#writable-reference-defaults--2026-10-22)
distinguishes safe borrow-or-copy optimization for ordinary values from explicit
reference semantics: copying an explicit reference would change its live alias or
write-back behavior. No new escape analysis, runtime mechanism or retained structure
was added. Existing borrowed-capture restrictions remain.

Parameter-default preparation admits both access modes through the same scalar
literal path. Shared callable validation no longer rejects writable defaults. Call
preparation checks that a reference result has an explicit origin before assigning
its place. The existing emitter supplies owned default locals as lvalues. Redundant
C++ defaults were removed from source callable prototypes: call-site lowering already
materializes every default and pack, and C++ writable references cannot bind a
literal default temporary. This keeps one default-binding path. Ordinary by-value
and empty/nonempty pack controls verify the shared declaration change.

### Verification and evidence

Dev002 checkout `/home/alex-ai/curl/20261020-work`, base `dc80eec76` with twelve
compiler/test/expectation files in `writable-default-manifest.json`. Final hashes
were verified before the retained closeout. Tests use default prelowering and assert
`application_templates: forbidden`, through the shared 12-slot runner with:

```sh
export PATH=/home/alex-ai/curl/value-pack-bin:$PATH
export MY_TRY_PHP_EXECUTOR=cli
python3 compiler/my-try/tests/writable_reference_default_prelower.py \
  --baseline /home/alex-ai/curl/vd08-v01-baseline --results FRESH_DIRECTORY
python3 compiler/my-try/tests/reference_default_prelower.py --results FRESH_READONLY
```

- `20261022-writable-default-01`: 24 pinned-v0.1 observations. All twelve explicit
  scalar/free/static/instance calls execute with caller write-back; all twelve
  omitted calls fail native build. Omitted writable defaults are an explicit v0.2
  extension throughout this tested slice. The baseline is pinned at
  `75de94c71e37060c564b6ade19850b7686bd457a`.
- `20261022-writable-default-final`: 37 executable cases, twelve source rejections
  and three existing PHP regression suites pass. Five programs also pass ASan/UBSan.
  Coverage includes repeated/nested defaults, mutable forwarding, alias/snapshot
  order, exception write-back, independent string values, receiver cleanup, signed
  defaults, explicit reference results and omitted-origin rejection. Each executable
  runs cold and warm; warm compilation/linking is zero.
- That run's sole remaining failure was a retained-test expectation that removing
  an unused default must change explicit-call artifacts. The corrected assertion
  verifies that these artifacts stay unchanged; other edits still change consumers.
  `20261022-writable-default-retained-final` passes explicit/omitted/default/access
  edits, rejection of an escaping default reference, withdrawal of ready output,
  identical recovery, fresh parity, provider exclusion and cold/warm execution.
  Acceptance combines these two runs; the original failed log is preserved.
- `20261022-writable-default-readonly-final`: all 35 tasks pass, including 22 source
  programs, nine rejections, retained lifecycle and three PHP regression suites;
  five programs also pass ASan/UBSan. Its 23 publications run cold/warm without warm
  compilation or linking.
- `20261022-writable-default-audit-final`: ten paired cases and retained recovery
  pass reviewed stage/hash/stdout expectations. Each version executes nine cases:
  v0.1 rejects omitted writable defaults at build, while v0.2 rejects the separately
  deferred reference-pack spelling. Ordinary value defaults and empty/nonempty packs
  remain positive controls. Current executed cases and retained output pass warm
  no-work builds.

The initial runs preserve the redundant C++-default build failures and stale method/
retained expectations. The signed-default probe initially requested an unsupported
int-to-float default conversion; it now uses correct-type signed literals without
widening conversion rules. A container-interior probe initially had a `>=` tokenization
error; corrected whitespace now reaches and proves the intended binding rejection.
The escaping-capture rejection remains the existing exact-callable boundary diagnostic.

No runtime changes, full matrix or native compiler rebuild. Wider defaults/packs,
constructors and other bridges stay separate. VD07-A03 reference-pack spelling is
still open; overall remains **60/64 resolved (93.75%)** with V-R08 partial and V-D01,
V-D07 and S02 open. RD-H01 below remains a separate stale nullable-metadata fixture.

Writable evidence archive: `/home/alex-ai/curl/writable-default-evidence.tar.gz`,
SHA-256 `43cca4d2eadc95777b0cbf4bbe7ec052134b5936111c495e457cbe646fb7f189`.
It retains initial failures and final proofs, excluding native caches.

## Completed read-only scope — 2026-10-22

Named source free functions and static/instance methods admit literal defaults for
fixed read-only references to canonical int/float/bool/string. The normative
[reference contract](../../../../specs/native_reference_safety.md#read-only-reference-defaults--2026-10-22)
defines the bounded exception to explicit stable-storage argument admission.

Explicit arguments retain their exact-type storage borrow, including live alias
behavior. Omitted arguments initialize fresh call-local owners using the existing
prepared literal/default conversion. Invocation borrows that lvalue until the
call completes or unwinds; it is not moved as a by-value argument. Ordinary value
arguments retain snapshots. Default owners are created after explicit arguments,
in parameter order. Nested forwarding is permitted within the call; owned value
returns survive independently.

Parameter-default preparation and callable-contract validation own admission;
`CPP_Declarations` owns the physical default owner and invocation. Existing
reference-origin validation rejects const-reference returns, and the callable
boundary rejects escaping borrowed captures. No new retained structures or runtime
semantics were introduced. Anonymous-callable and provider metadata reference
defaults retain explicit exclusion gates; constructors keep their existing rules.

Testing exposed one local emission dependency: reading a borrowed bool's
`native_value()` needs a complete bool declaration in the consuming method body.
The existing truthiness emitter now records that dependency, instead of relying
on a parameter forward declaration. A no-default borrowed-bool control verifies
the same requirement.

**At this historical checkpoint VD07-A01 was partial:** this completed its read-only scalar slice, while writable
reference-default declarations remain unsupported, including explicit-argument
calls that v0.1 accepts. VD07-A03 reference-pack spelling also remains open. Wider
default types/expressions and other bridges stay separate. Overall remains
**60/64 resolved (93.75%)**, one partial original issue (V-R08) and three other open
(V-D01, V-D07, S02). No runtime changes, full matrix or native compiler rebuild.

## Verification

Source base `10478ceba` plus nine compiler/test/expectation files recorded in
`/home/alex-ai/curl/20261020-work/ref-default-manifest.json`. Dev002 uses the shared
12-slot runner, explicit CLI fallback and the task-local PHP8.5 launcher introduced
by the preceding pack goal. Default prelowering is used throughout; executable
publications assert `application_templates: forbidden`.

```sh
export PATH=/home/alex-ai/curl/value-pack-bin:$PATH
export MY_TRY_PHP_EXECUTOR=cli
python3 compiler/my-try/tests/reference_default_prelower.py \
  --results /home/alex-ai/curl/FRESH_REFERENCE_DEFAULTS
python3 compiler/my-try/tests/call_compatibility_audit.py \
  --baseline /home/alex-ai/curl/vd08-v01-baseline \
  --cases const-ref-default-omitted,const-ref-default-explicit,const-ref-string-default,method-const-ref-default,ref-default-explicit,ref-default-omitted,ref-pack-read \
  --results /home/alex-ai/curl/FRESH_REFERENCE_DEFAULT_AUDIT
```

Initial evidence: `20261022-ref-default-01`. Final bounded acceptance:
`20261022-ref-default-final`; paired audit: `20261022-ref-default-audit`.

- 22 source programs: all four scalar defaults through free/static/instance calls,
  with explicit and omitted arguments; value snapshots versus live borrows; mutation
  through another alias; forwarding; nested/repeated calls; independent string
  returns; argument/callee failure; receiver lifetime and finally; no-default bool.
- Five ASan/UBSan executions cover returned values, argument/callee failure and
  receiver success/failure cleanup. Source exceptions remain catchable with the
  same messages; receiver destruction occurs once.
- Ten source rejections preserve writable defaults, explicit temporary/conversion
  rejection, read-only writes and mutable forwarding, reference returns, escaping
  borrowed callable storage, wider enum defaults, computed expressions and anonymous
  reference defaults. The escaping-capture diagnostic is the existing exact-callable
  boundary rejection, not a new borrow-analysis diagnostic.
- Retained explicit/omitted/default-value edits, rejection of a writable edit,
  unready publication, identical recovery and fresh parity. Provider metadata rejects
  both writable and read-only reference defaults independently of source admission.
- 23 publications run cold/warm with zero warm compilation/linking. Existing
  references, closure-model and method-parameter PHP suites pass; these are not
  fresh native execution of every historical suite fixture.
- Seven baseline/current pairs retain source hashes and reviewed expected outcomes.
  Four read-only-default current cases now execute; writable-default and reference-
  pack findings remain unchanged. Original audit failures remain historical evidence.

## Separate regression finding RD-H01

RD-H01 tracks the existing nullable-metadata fixture expectation separately from
the original 64 issues. The initial run also invoked `runtime_signatures.php`. It fails its historical
`Unsupported runtime default` expectation for null metadata, which predates the
existing nullable-default registration. That fixture mismatch is not repaired or
counted as a pass in this goal; its stderr is retained in the initial evidence.
The directly relevant provider-reference-default exclusion has its own passing
checks in this goal's retained test. Review the nullable metadata fixture separately.

The initial escaping-capture assertion expected a diagnostic containing “borrow”;
the actual callable-boundary rejection was retained and the assertion corrected.
The initial bool-method build failures are preserved alongside the dependency repair.

The strengthened retained edit assertion also passes in
`20261022-ref-default-retained-review`, including cold/warm execution.

Evidence archive: `/home/alex-ai/curl/ref-default-evidence.tar.gz`, SHA-256
`3b45abb4b7f04faa2bf3b256549cef76fc1d4359990245dab35bf6ec743fb782`.
All nine executed compiler/test/expectation hashes match the final overlay manifest.
Native caches are omitted; logs, source and generated publications are retained.
