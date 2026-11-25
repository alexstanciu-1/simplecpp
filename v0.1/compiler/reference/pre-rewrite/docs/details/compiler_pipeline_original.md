# Initial compiler pipeline
Doc Status: supporting

Saved: 2026-09-07. Historical snapshot of the first proposed list before
comparison with the previous compiler. The [current pipeline](../compiler_pipeline.md)
supersedes this list; these 17 numbers are not current responsibility IDs.

1. **Read the project manifest** — source roots, entry point, dependencies, language options, and build target.
2. **Discover source files** — walk the configured file tree and apply inclusion/exclusion rules.
3. **Identify changes** — compare files, configuration, and runtime metadata with the previous compiler state. On the first run, everything is new.
4. **Read source contents** — load new or changed files into source buffers.
5. **Tokenize** — turn source bytes into tokens with source locations.
6. **Parse** — build syntax trees and report syntax errors.
7. **Collect declarations** — register namespaces, types, functions, signatures, and scopes.
8. **Resolve names** — connect references to declarations, including references across files.
9. **Resolve types and check semantics** — type expressions, resolve calls and operators, check conversions and access rules, and validate runtime capabilities.
10. **Build typed executable bodies** — represent expressions, storage locations, calls, basic blocks, and control-flow edges in one shared intermediate representation.
11. **Analyze control flow and lifetime** — check initialization, return paths, ownership, borrowing, and cleanup obligations.
12. **Lower executable bodies** — make operations, cleanup, layouts, and ABI calling conventions explicit for the target.
13. **Optimize** — apply supported transformations while preserving behavior.
14. **Generate backend code** — produce LLVM IR from the lowered representation.
15. **Produce object files** — compile changed backend units and reuse valid cached objects.
16. **Link** — combine objects, runtime components, and libraries into the executable or library.
17. **Commit compiler state and report results** — publish the new state, diagnostics, and requested inspection artifacts.

This is a dependency order: independent files and functions can progress in
parallel. Reuse belongs at each stage, so an unchanged input can retain its
output, while a changed declaration invalidates the dependent work.

Cold compilation should use this same path with empty previous state. Running
the resulting program is a separate action after compilation.
