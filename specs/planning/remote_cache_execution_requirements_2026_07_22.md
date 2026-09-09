# Remote Cache And Execution Requirements
Doc Status: planning

Date: 2026-07-22

## Purpose

This note scopes the requirements for future Simple C++ remote cache and
remote execution support. It is a planning document, not a semantic authority
and not an implementation commitment.

The current implementation has local action identities, an opt-in local object
action cache, module surface artifacts, and a persisted build planner
warm-state snapshot. Remote reuse should build on those pieces only after the
identity, trust, and path-mapping contracts below are explicit.

## Current Baseline

Simple C++ currently has:

- generated/native object action keys in `.prism/last_run.json`
- opt-in project-local object restore/store with `build.object_cache = true`
- generated artifact interface/implementation hashes
- project module public surface and private implementation artifacts
- module analysis summary artifacts
- build planner warm-state snapshots in `.prism/cache/build_planner_state.json`

Simple C++ does not currently have:

- a shared content-addressed store
- cross-checkout object reuse
- remote cache read/write policy
- remote execution workers
- sandboxed action execution
- cache eviction, quotas, authentication, or tenancy

## Non-Goals For The First Remote Design

The first remote-cache design should not try to replace Ninja, infer undeclared
inputs, or make generated C++ the authoring source of truth.

The first remote execution design should not run arbitrary undeclared project
commands. Only fully declared build actions with stable input roots, command
payloads, environment allow-lists, and expected output paths should be eligible.

## Identity Requirements

Remote action keys must include enough information to prove that a downloaded
object or action result is valid for the current build:

- Simple C++ repository/runtime signature
- S2S generator signature and PHP profile
- compiler executable identity and version
- compiler kind, target triple, standard library, and linker-relevant identity
- build mode and all effective C/C++ flags
- compiler launcher behavior when it affects command semantics
- normalized environment allow-list, not the whole process environment
- source input content hashes
- generated header/source hashes
- project module public surface hashes
- force-include and implicit input hashes
- native C++ input hashes and discovered compiler depfile inputs
- action schema version

Remote keys must not depend on absolute checkout paths unless the path is part
of the action's user-visible semantics. Project-relative or content-addressed
paths should be used wherever possible.

## Path Mapping Requirements

Remote cache and execution must define a stable path model:

- root project id and dependency project ids
- project-relative source paths
- generated/build/cache path roles instead of absolute local directories
- normalized forward-slash paths in action payloads
- explicit mapping for dependency project outputs
- stable names for grouped generated objects
- no cache hits when an action embeds an unmapped absolute path in command-line
  flags, generated output, debug info, response files, depfiles, or diagnostics

If debug-info paths are emitted into objects, the first design should either
normalize them with compiler flags such as prefix maps or mark those actions
remote-cache ineligible.

## Trust Requirements

Remote cache reuse links downloaded object files into local binaries, so cache
integrity is a build correctness and supply-chain concern.

Required controls:

- authenticated cache endpoint
- project/repository namespace separation
- read/write policy per namespace
- immutable content blobs addressed by digest
- action-result metadata that references output digests, sizes, and executable
  bits
- digest verification before restoring any output
- schema version checks and forward-incompatible miss behavior
- cache poisoning mitigation, including optional trusted-writer-only mode
- clear local opt-in before remote reads or writes
- auditable hit/miss metadata in saved build reports

Remote execution adds stronger requirements:

- sandboxed worker execution
- declared input root materialization by digest
- no ambient source checkout access
- no undeclared network access for build actions by default
- bounded CPU, memory, disk, and wall-clock limits
- captured stdout/stderr and exit code
- returned output digests only for declared outputs

## Cache Data Model

The remote cache should separate blobs from action results:

- CAS blob: digest, size, bytes, optional compression
- Action result: action key, output file digests, output paths, exit code,
  tool metadata, timing metadata, and schema version
- Negative result: optional short-lived record for known invalid or unsupported
  actions, never for ordinary compiler failures without an explicit policy

The local object cache can remain the first on-disk implementation. Remote cache
support should adapt the same action identity into a transport/store boundary
rather than introducing a second identity system.

## Invalidation Requirements

A remote hit is valid only when all action inputs and command semantics match.
At minimum, cache misses must occur for:

- changed source content
- changed generated headers or generated sources
- changed module public surface inputs
- changed force-include/project-unit pack inputs
- changed native include dependencies from compiler depfiles
- changed runtime or language support artifacts used by the action
- changed compiler executable/version/target/flags
- changed action schema
- path mapping that cannot be normalized
- environment allow-list differences

Private module implementation hashes should not invalidate consumers unless the
consumer action directly includes that private artifact or the module public
surface changes.

## Remote Execution Requirements

Remote execution should come after remote cache identity is stable.

Before enabling remote execution, Simple C++ must be able to produce a complete
declared action package:

- command argv and working directory role
- environment allow-list
- input file digests and virtual paths
- expected output paths
- timeout and resource hints
- platform/toolchain constraints

Generator/S2S actions are not currently first-class Ninja actions. If future
remote execution covers generation as well as compilation, generator actions
need the same declared-input and declared-output model.

## Observability Requirements

Future reports should make remote behavior inspectable through
`.prism/last_run.json` and focused `scpp explain-build` views.

Required fields:

- remote cache enabled/read/write status
- remote execution enabled status
- action count by generated, native, grouped, runtime, and generator action
- cache hit, miss, upload, download, and bypass counts
- bypass reasons, including unsupported action, unsafe path mapping, untrusted
  namespace, changed identity, and remote unavailable
- bytes uploaded/downloaded
- remote lookup/upload/download/execute timing
- selected cache namespace and endpoint label, without leaking secrets
- per-action key and output digest for detailed views

## Rollout Plan

Suggested sequence:

1. Stabilize local action identity and local object cache reports.
2. Add a remote-cache eligibility report with no network behavior.
3. Add opt-in read-only remote cache lookup for generated/native object actions.
4. Add opt-in trusted-writer uploads after local successful compiles.
5. Add eviction/quota policy for local and remote stores.
6. Add remote execution only for actions with complete declared inputs and
   safe path mapping.
7. Evaluate default-on read behavior only after repeated v2 compiler and CI
   measurements prove correctness and value.

## Expected Value

Planning estimates:

- Developer warm no-change builds: small direct value, because Ninja no-work
  already handles the final executor path.
- Repeated local toggles between known action states: local cache can already
  help; remote cache mainly helps if another machine produced the same action.
- CI and fresh checkout builds: potentially high value when action hit rates
  are good, often cutting compile wall time by multiples rather than
  percentages.
- Release/O3 validation: high upside for expensive generated objects if action
  keys are stable and debug/path output is normalized.
- Remote execution: highest cold-build upside, but only after sandboxing and
  declared-input completeness are strong enough.

## Acceptance Gates

Do not implement remote cache/execution until these checks have an owner:

- action identity is stable across two different checkout roots for the same
  source tree
- cache-ineligible actions explain the exact blocker
- remote hits verify output digests before restore
- local build output is byte-compatible or explicitly accepted as semantically
  equivalent after remote restore
- remote cache failures fall back to local build unless strict remote mode is
  explicitly requested
- secrets never appear in saved reports
- v2 compiler benchmark reports separate local-cache, remote-cache, and
  remote-execution timings
