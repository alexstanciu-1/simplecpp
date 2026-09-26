# Preparation
Doc Status: supporting

- `file.php`: prepare the supported file body and attach facts to specialized nodes.
- `literals.php`: exact integer literal preparation under the Simple C++ contract.
- `cleanup.php`: tree traversal invoking each specialized node's cleanup.
- `structures.php`: preparation records, including experimental name/check records.
- `names.php`: the existing Name_Preparation used by the LLVM experiment.
- `templates.php`: existing experimental template checking; development remains deferred.

Shared types live in `../../compiler/types/`; scope representation and lookup live in
`../../03_parse/scopes/`. This grouping changes locations, not processing behavior.

Prepared facts are accessed through the specialized nodes: `preparation()`,
`require_preparation()` and `set_preparation()`. Per-node cleanup stays on the node.
