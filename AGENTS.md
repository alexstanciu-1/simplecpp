# AGENTS.md

Purpose: quick-start instructions for Codex and similar AI assistants working in this repository.

## Read First

Before making changes, read in this order:

1. `specs/spec_map.md`
2. `docs/ai_onboarding/README.md`
3. `docs/ai_onboarding/coding_style.md`
4. `specs/simple_cpp_php_strict_quick_learn.md`

Then read the owning spec for the task.

## Core Rules

- PHP is the current authoring surface.
- For PHP++ / PHS authoring, `specs/simple_cpp_php_strict_quick_learn.md` is a mandatory read.
- The source syntax is PHP-like, but source code must target the supported Prism++ / Simple C++ subset, not standard PHP.
- Do not use a PHP feature merely because normal PHP accepts it.
- Generated C++ is a lowering/debugging artifact, not the primary source of truth.
- Top-level normative specs under `specs/` define user-visible semantics.
- Architecture, runtime, and generator subsystem docs are subordinate to top-level semantic specs.
- Implementation and tests are evidence, not the highest semantic authority.

**v0.2 C++ evaluation order.** Emit direct C++ when the source permits either order and
the native expression has defined behavior. Preserve single evaluation, required checks,
short-circuiting, and ordered effects such as printing each `echo` item before evaluating
the next. Tests should accept every order the source permits. A qualification trace
describes only the tested expression and build. The build reruns the probe when the
compiler fingerprint, target, flags, or probe revision changes, or after seven days.
[Evaluation contract](specs/expression_evaluation.md) · [Toolchain qualification](v0.2/compiler/docs/arithmetic.md#verification)

## S2S Generator Boundary

- The PHP S2S generator is type-blind unless a specific generation rule documents a narrow local structural check.
- The generator does not resolve symbols, infer full program types, validate inheritance, validate overrides, or behave like a semantic compiler.
- If emitted C++ is semantically invalid, that failure normally belongs to the C++ compiler or runtime contract unless a generator rule explicitly says otherwise.
- Do not write source code that depends on the generator having standard-PHP runtime knowledge.

These generator rules apply to `generators/php/`. The experimental
`compiler/my-try/` pipeline has its own shared semantic preparation stage; follow
its local rules and preparation/emission boundary instead of treating its C++
emitter as the semantic validator.

## Work By Owning Layer

- language meaning -> `specs/`
- PHP lowering -> `generators/php/`
- runtime behavior -> `runtime/`
- CLI/build/project workflow -> `bin/`, `docs/`, `specs/project_build_v1.md`
- onboarding/operator guidance -> `docs/`

Do not patch generated output as the final fix when the real issue belongs in a higher-level source.

## Default Coding Posture

- prefer explicit types at meaningful boundaries
- stabilize `mixed` early
- keep `null` and `false` distinct
- prefer `===`
- avoid ambiguous truthiness
- write for the supported Prism++ subset, not full PHP

## Standard-PHP Fallbacks To Avoid

Do not assume these are valid just because they are common PHP:

- untyped parameters or returns
- dynamic includes or computed `require_once`
- broad builtin availability without a spec
- loose truthiness as a substitute for explicit state checks
- magic array behavior outside the documented subset
- implicit PHP runtime behavior that is not documented in the Prism++ specs

## Validation

Validate at the smallest layer that proves the change:

- focused transpilation or samples for lowering changes
- `scpp build` / `scpp run` for project workflow
- `scpp usability-harness` for first-user flows
- runtime test suite for runtime behavior

See `docs/ai_onboarding/workflows.md` for the fuller task-routing and validation guide.

## Focused Tool Use and Daybreak Blocks

- Keep tool calls and messages narrowly scoped to the task. Use precise compiler terminology and state the purpose of an operation when context is needed.
- Prefer targeted searches, bounded file reads, and relevant diagnostic excerpts over broad dumps. Return enough evidence to diagnose and verify changes without exposing unrelated or sensitive content.
- Do not assume compiler development caused a Daybreak block. Base explanations on the actual rejection and distinguish known reasons from uncertainty.
- When blocked, report the action and stated reason, continue unaffected work, and use an authorized alternative only when it respects the safeguard. If no such path exists, explain what remains blocked and what approval or clarification is needed.
- Do not disguise, encode, split, or reroute requests or outputs to evade Daybreak or other safeguards. Narrowing scope must genuinely reduce the operation or data involved, not conceal the same blocked action.

## Planning Notes

- Temporary planning notes for active tasks should default to `specs/planning/` unless a more specific owning folder is clearly better.
- Planning notes must carry `Doc Status: planning` and must not be treated as semantic authority.

## my-try compiler instructions

When working under `compiler/my-try/`, also read and follow
[its local operating rules](compiler/my-try/AGENTS.md). They apply to the entire
`compiler/my-try/` subtree.

## Singular relationships and erroneous duplicates

This rule and its similar-case review apply to active `v0.2` code only.
Mined structures are reference material and must be discussed with the user before
adoption into `v0.2`; mining does not authorize copying their design.

When a property or per-key slot has zero or one element in a valid program, and
multiple elements are necessarily an error, represent it as one nullable reference.
Retain invalid additional elements through `duplicate_element` (or the equivalent
typed duplicate link), not a list allocated for the normal case. One declaring
scope/owner exclusively maintains that chain; secondary indexes only reference it.
Define publication and incremental relinking boundaries before mutating shared links.
Do not apply this to valid multiplicity: syntax inventories, overload sets, observers,
dependencies and diagnostics may legitimately need collections. Do not change
serialized/public formats or historical donor subsystems without reviewing their owner.
See [the compiler pattern and inventory](v0.2/compiler/docs/singular_duplicates.md).
