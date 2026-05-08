# SimpleC++ Usability Feedback
Doc Status: planning

Date: 2026-05-08

Context:
- feedback from active Open M3 ORM/schema-parity work
- focus is usability, not language design purity

## Main Frictions

- Build behavior is hard to predict.
- Runtime/dependency reuse was too implicit before `0.1.16`.
- Error reporting is often too indirect.
- Sandbox/read-only interactions are painful.
- Generated build artifacts are hard to inspect confidently.
- Mixed/runtime type failures are hard to localize.
- Transpilation support gaps are discovered late.
- “Works in PHP mindset, fails in SimpleC++ mindset” is still too common.
- Direct `ninja` debugging is misleading.
- Debug-output workflows are cumbersome.

## Important Additions Needed

- First-class “why did this rebuild?” diagnostics.
- First-class “show root cause” failure reporting.
- Better runtime type error context.
- Clear supported-language reference for transpilation.
- Safer helper equivalents for common PHP operations.
- A stable debug/inspection mode for generated outputs.
- Better incremental-build visibility and guarantees.
- Easier project-local debug scripts/tools.
- Better mixed-to-typed ergonomics.
- A “minimal repro export” command.

## Good To Have

- A stricter lint mode before compile.
- Better docs for collection/loop patterns over hashes and vectors.
- Better docs for writing debug-only code safely in SimpleC++.
- A small cookbook of common migrations from PHP idioms to SimpleC++ idioms.
- Optional structured logs for build/runtime phases.
- An official pattern for emitting data snapshots like TSV/JSON for debugging.
- Better naming around project deps/runtime deps/build deps so the model is clearer.
- A command to clean only local target state, without disturbing reusable runtime/dependency caches.
- More examples of “large real project” workflows, not only toy examples.
- A short troubleshooting page for sandboxed/dev-container environments.
