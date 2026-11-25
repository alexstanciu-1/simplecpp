# SimpleC++ Usability Feedback Details
Doc Status: planning

Date: 2026-05-08

Context:
- feedback gathered while working on Open M3 ORM import, materialization, and schema-parity debugging
- emphasis is day-to-day developer usability

## Main Frictions

### 1. Build behavior is hard to predict

Reason:
- it is not always obvious when `scpp run` will rebuild, partially rebuild, or fully reuse state
- that uncertainty makes developers hesitate before even running a command
- when the mental model is fuzzy, every slow or surprising run feels suspicious

### 2. Runtime/dependency reuse was too implicit before `0.1.16`

Reason:
- before the newer defaults, too much workflow knowledge lived in flags and release notes
- users had to remember the right incantation instead of trusting the default experience
- that creates friction especially during debugging loops

### 3. Error reporting is often too indirect

Reason:
- top-level failures often arrive first as “Ninja build failed” or similar wrappers
- the actual compiler/runtime error is present, but not always foregrounded enough
- this increases time-to-understanding even when the underlying issue is simple

### 4. Sandbox/read-only interactions are painful

Reason:
- build failures caused by depfile or runtime-cache writes can look like project or compiler problems
- the user has to separate environment failure from source failure manually
- this is especially confusing in agent-driven or containerized environments

### 5. Generated build artifacts are hard to inspect confidently

Reason:
- generated C++, generated Ninja, runtime cache paths, and dependency objects are all useful
- but the inspection flow is still fairly manual and expert-oriented
- that makes low-level debugging possible, but not comfortable

### 6. Mixed/runtime type failures are hard to localize

Reason:
- errors like `cast<string_t>(mixed_t)` identify the conversion class, but not enough semantic context
- the missing context is usually:
  - which source field/value caused it
  - what runtime kind it actually was
  - what file/line or generated path produced the bad cast
- this slows down debugging of otherwise straightforward data-shape issues

### 7. Transpilation support gaps are discovered late

Reason:
- some natural PHP-style constructs compile or run differently than expected
- support boundaries are learned reactively through failures rather than proactively through guidance
- that causes churn in debugging code, helper code, and tooling scripts

### 8. “Works in PHP mindset, fails in SimpleC++ mindset” is still too common

Reason:
- some code feels perfectly natural from a PHP perspective but is fragile in the transpiled/runtime model
- if the developer does not already know the preferred SimpleC++ idiom, they lose time on avoidable translation mistakes
- this is a usability problem more than a capability problem

### 9. Direct `ninja` debugging is misleading

Reason:
- the emitted `build.ninja` is useful, but direct `ninja` inspection can show missing edges or missing generated artifacts in ways that are hard to interpret
- users do not know whether they are seeing a real graph issue, a lifecycle/setup issue, or an intentional `scpp` abstraction boundary
- that weakens confidence in lower-level diagnosis

### 10. Debug-output workflows are cumbersome

Reason:
- when developers need to export or print debugging data, they often touch exactly the risky surfaces:
  - mixed conversions
  - loops over hashes/vectors
  - helper functions that are only partially intuitive in SimpleC++
- this means diagnostic code can become almost as fragile as production code

## Important Additions Needed

### 1. First-class “why did this rebuild?” diagnostics

Reason:
- build behavior becomes much easier to trust when the tool explains dirtiness directly
- this would reduce the need to inspect Ninja manually
- a command or flag like `scpp explain-build` would pay for itself very quickly

### 2. First-class “show root cause” failure reporting

Reason:
- the actual root cause should be surfaced prominently above wrapper/toolchain noise
- this is especially important for compiler failures, runtime-cache failures, and sandbox write errors
- the shorter the path to the real error, the better the developer experience

### 3. Better runtime type error context

Reason:
- runtime cast failures need richer diagnostics
- ideal additions:
  - expected type
  - actual runtime kind
  - source file and line
  - maybe field/path/variable context
- this would drastically reduce debugging time for data-shape mismatches

### 4. Clear supported-language reference for transpilation

Reason:
- developers need a stable answer to “is this construct supported, partially supported, or discouraged?”
- this is especially important for:
  - regex helpers
  - filesystem helpers
  - array/hash iteration patterns
  - mixed conversions
  - common PHP convenience idioms

### 5. Safer helper equivalents for common PHP operations

Reason:
- many usability problems come from lack of obvious “safe default” helpers
- the more standard operations have endorsed wrappers, the less each project has to rediscover good patterns

### 6. A stable debug/inspection mode for generated outputs

Reason:
- developers need a supported path to inspect:
  - generated C++
  - build graph
  - depfiles
  - runtime signature
  - cache/runtime paths
- if inspection is first-class, debugging becomes far less mysterious

### 7. Better incremental-build visibility and guarantees

Reason:
- incremental trust matters a lot to usability
- if depfiles are missing or targets are being dirtied for structural reasons, the tool should say so explicitly
- hidden invalidation is one of the fastest ways to erode confidence

### 8. Easier project-local debug scripts/tools

Reason:
- developers often need small probes, exporters, and one-off analysis helpers
- if those are hard to write safely, debugging slows down even when the compiler is fine
- a few documented patterns or built-in helpers would go a long way

### 9. Better mixed-to-typed ergonomics

Reason:
- this is one of the biggest day-to-day pain points
- developers need a more obvious and reliable story for:
  - `to_string`
  - integer/string conversion
  - nullable handling
  - mixed extraction
  - typed iteration

### 10. A “minimal repro export” command

Reason:
- bug reporting gets much easier when the tool can package:
  - source
  - generated artifacts
  - build graph
  - runtime signature
  - exact failing command state
- this helps both users and SimpleC++ maintainers

## Good To Have

### 1. A stricter lint mode before compile

Reason:
- catching likely transpilation/runtime mismatches earlier would reduce trial-and-error

### 2. Better docs for collection/loop patterns over hashes and vectors

Reason:
- iteration is a common source of accidental mixed/type friction

### 3. Better docs for writing debug-only code safely

Reason:
- a lot of developer pain happens in instrumentation, not in final production code

### 4. A small cookbook of PHP-idiom to SimpleC++-idiom migrations

Reason:
- this would shorten onboarding and reduce repeated mistakes

### 5. Optional structured logs for build/runtime phases

Reason:
- developers and tools could inspect phase timing and decision points more easily

### 6. An official pattern for emitting debug data snapshots

Reason:
- exporting TSV/JSON-style state should be a solved problem, not a bespoke one each time

### 7. Better naming around project deps/runtime deps/build deps

Reason:
- terminology clarity improves mental models and makes bug reports more precise

### 8. A command to clean only local target state

Reason:
- developers often want to reset one target without disturbing reusable runtime/dependency caches

### 9. More examples of “large real project” workflows

Reason:
- many usability issues show up only on real codebases with generated artifacts and iterative debugging

### 10. A short troubleshooting page for sandboxed/dev-container environments

Reason:
- environment-related friction is common and should be explained directly rather than rediscovered each time
