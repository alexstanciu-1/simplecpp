# V-D08 sentinel Result enum carriers
Doc Status: planning

## Outcome — 2026-10-22

`result_or_false<E>` and `result_or_bool<E>` now admit exact unit, int-backed and
fixed-integer-backed enum payloads under default prelowering. The shared payload
capability, conversion, take and concrete transport owners are reused. No runtime
implementation changed. V-D08 remains partial: overall **58/64 closed (90.625%)**,
two partial and four other open issues. This completes the approved enum-carrier
value/state slice, not operators, reflection or broader enum extensions.

## States and ownership

The enum-only sentinel-family exclusion was removed from `Result_Conversions`.
Exact enum identity remains required. Construction/copy/assignment/arguments/returns
reuse existing conversions; checked extraction requires a payload. The source is
snapshotted once before take write-back, and copies preserve payload/sentinel state.

`Value_Probe_Preparation` now distinguishes usable-value presence from sentinel-aware
presence. The boolean-sentinel enum family selects `sentinel_presence`, transported
through the existing compiled-operation owner as `wrapper_isset` and delegated to
the runtime isset authority. Ordinary emission invokes that same authority. The two
wrapper observation bridges share borrowing/transport plumbing, not semantic tests.
No source-name registry or parallel state model was introduced.

| State | isset | take result | Enum output | Boolean output (bool carrier only) |
| --- | --- | --- | --- | --- |
| Enum payload, including zero | true | true | Copy payload | Unchanged |
| False sentinel | false | false | Unchanged | Set false |
| True sentinel | true | true | Unchanged | Set true |

Required enum extraction throws for both sentinels. True-sentinel presence is not
payload availability. Only falseable Results admit lazy exact-enum `??` fallback;
boolean-sentinel Results remain rejected on either side. Conditions, lifted operators,
implicit integer/other-enum/family conversions and wrapper emptiness remain rejected.
Existing explicit initialization rules remain: no invented default enum payload.

## v0.1 versus intentional v0.2 syntax

`20261022-vd08s-baseline` executes the pinned strict generator and native output from
`75de94c71e37060c564b6ade19850b7686bd457a` on dev002. The expanded baseline harness
records ordinary, nullable, structured Result and both sentinel carriers; `?E`
retains its previously observed generator rejection. Sentinel probes use int-backed
A=0 and B=7. Falseable output is `yesAemptyA`; boolean-carrier output is
`yesAFyesATnoAF`, proving payload/true/false transitions and preserved outputs.
This is an execution proof for those cases, not every v0.1 backing or operation.

v0.1 accepts bare true/false as sentinels. v0.2 deliberately requires explicit
`true_sentinel` / `false_sentinel` for non-bool payloads. Bare booleans remain rejected;
null retains the existing false-state conversion. The first source fixtures used
legacy bare booleans and were corrected, not used to broaden v0.2 semantics.

## Verification on dev002

Workspace `/home/alex-ai/curl/20261020-work`; shared 12-slot runner with CLI fallback
because CLI/FPM versions differ. Production programs use default prelowering and
application manifests forbid templates. No full matrix or native compiler rebuild.

- `20261022-vd08s-runtime-02`: existing runtime passes ASan/UBSan for unit, int64
  endpoints and uint64 endpoints; false/true/value states, copies, presence,
  take output preservation and required-extraction failures. The first runtime
  attempt used an invalid native bool assignment in the fixture; explicit bool_t
  construction corrected it without runtime changes.
- `20261022-vd08s-source-02`: 24 positive programs, including two carriers across
  unit/plain-int and all eight fixed widths; zero payloads, explicit sentinels,
  null transitions, saved state copies, both take spellings, exact required values,
  once-only source evaluation and by-value snapshots before later source mutation.
  Lazy falseable fallback executes only when needed.
- Three programs check required-extraction failure from falseable false and both
  boolean sentinels; the true case first asserts isset is true.
- 23 preparation rejections cover wrong payloads/families/outputs, bare booleans,
  structured errors, defaults, conditions, equality, wrapper/integer fallbacks,
  boolean-carrier coalescing and empty. Existing results/take/nullable/value-probe
  suites pass.
- All 27 executable cases run cold and warm; warm builds compile/link nothing.
- Retained imported cross-file enums reject invalid backing/removal, invalidate
  readiness, recover exactly, change int8 to full-width uint64, retire/restore
  helper and sentinel-presence demand, match fresh output and build/run successfully.
- `20261022-vd08s-wrapper-controls`: updated nullable/structured/sentinel wrapper
  controls: 20 positive programs, two checked-extraction failures, ten semantic
  rejections, retained recovery and six existing regression suites pass. Historical sentinel-carrier rejection
  rows are replaced by state-sensitive positive checks; the earlier report stays
  evidence of the original gap.

## Remaining boundaries

Equality, lifted operators, wrapper conditions, indexed operations, broader selection
and same-wrapper fallback remain separate. String backing, reflection, enum methods/
interfaces and other V-D08 extensions still require individual decisions. Default
prelowering support here does not certify wider generic probes or LLVM parity.

## Evidence archive

Dev002: `/home/alex-ai/curl/vd08s-evidence.tar.gz`. SHA-256:
`7f9897f9189a869958fadb1b868eff36d53bf6c19b6e4de0df7adc1adaf3b7b4`.
The manifest pins base `6a1491272` and verifies all 12 changed compiler/test files
against the dev002 workspace. Final review confirms 49 zero-work warm builds and
51 template-forbidden publications across the sentinel and wrapper suites, including
both retained outputs. Baseline, sanitizer, earlier fixture-failure and final logs
and generated sources are retained; runtime binaries/caches are excluded.
