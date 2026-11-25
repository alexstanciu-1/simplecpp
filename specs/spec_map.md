# Specification map

Doc Status: normative

Language contracts under this directory own source meaning. The [frontend contract](compiler_frontends.md) describes source flavors. Read the contract for the operation being changed, including the [evaluation rules](expression_evaluation.md), [type-use modifiers](type_use_modifiers.md), [async calls](async_await.md) and [collection traversal](collection_traversal.md).

The [runtime API summary](runtime_api_summary.md) and its family specifications define the published native surfaces. The [compiler documentation](../compiler/README.md) explains preparation, lowering and implementation coverage. A source rule may have a smaller implemented surface in a particular frontend.

Normative documents define contracts in their stated scope. Supporting documents explain their use and implementation. Derived documents report generated information. Planning and historical documents record proposals and prior evidence. Active decisions supersede dated checkpoints where they explicitly revise the same behavior.
