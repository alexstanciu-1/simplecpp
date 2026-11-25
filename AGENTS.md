# Working in Simple C++

Read [the specification map](specs/spec_map.md), [the authoring guide](specs/authoring_guide.md), and the contract owning the change. For compiler work, follow [compiler instructions](compiler/AGENTS.md) and [compiler code style](compiler/docs/code_style.md).

The active compiler is authored in PHP 8.5. Source programs use the supported Simple C++ language and its syntax flavors. Semantic preparation owns type and admission decisions. Lowering consumes prepared facts. Generated code and test observations are evidence for the contract.

Preserve single evaluation, lifetime, short-circuiting and ordered effects. Use direct C++ when its behavior is defined and the source permits its evaluation order. A toolchain observation describes the tested expression and build. Follow the [evaluation contract](specs/expression_evaluation.md) and [toolchain qualification](compiler/docs/arithmetic.md#verification).

For a slot with at most one valid element, use one nullable reference. The declaring owner maintains a typed duplicate chain for invalid extras. Secondary indexes reference it. Keep collections for legitimate multiplicity and define publication and incremental relinking before mutating shared links. See [singular relationships](compiler/docs/singular_duplicates.md).

Keep the active compiler, tooling, tests and documentation usable when the historical archive is absent. Review historical designs with the user before adopting them.

## Validation

Run focused checks locally. Run long tests, full matrices and sustained benchmarks only on dev002. Use the root CMake presets for a clean configure and build. Run the default suite on dev002 with `ctest --preset default -j24`. Rerun a test exceeding three seconds in isolation before moving its full matrix to on-demand. Retain a fast default check for the same behavior. If reasonable attempts still exceed three seconds in isolation, document what was tried and why in the test.

## Planning and integration

Put planning notes under `specs/planning/` and mark them `Doc Status: planning`. They record proposals and evidence. Use the [integration and release procedure](specs/git_workflow_release_procedure.md) for branch targets and release notes.
