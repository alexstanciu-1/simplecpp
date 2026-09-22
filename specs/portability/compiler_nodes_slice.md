# Parser node model portability slice
Doc Status: supporting

The parser's `syntax_kind` vocabulary and `syntax_node` row now live in
`compiler/src/03_parse/data/nodes.php`. Structural role views and expression
cursors remain in `data/structures.php`. Both files stay under the prototype's
existing parser/data owner, and bootstrap loads both. No declaration is copied
between maintained files and no caller API changes.

The extracted declarations are byte-for-byte identical to their adopted definitions:
40 integer-backed kinds, byte-span fields, zero/one-based child/sibling IDs and the
existing ordinary class representation. Managed imports and file-level descriptive
comments are the only new text around them. Node assignment still shares the
object; this slice does not change rows to native value structs or introduce a
new syntax-tree representation. The owning tree and its containers remain later
migration work.

No converter or runtime capability was needed beyond the earlier enum, field and
construction slices. `compiler/portability.json` now lists five production files.
The cumulative shared witness checks initial values, assigned links and byte spans,
shared mutation, separate construction and enum distinctions in PHP and native
strict v0.1.76. Fifteen existing compiler fixtures include parser syntax, parameter
and struct parsing, frontend storage, incremental parse updates and metaprogramming.

[nodes-01 evidence](../planning/compiler_migration/results/nodes-01/summary.json)
contains the generated files, command logs and the shared runner. Its provenance
record verifies unchanged declaration bytes; the extraction patch records the
local source-file split. The standard component-runner command remains in the
[test README](../../tests/portability/compiler_context/README.md).

Constructors, readonly role views, cursor containers and parser execution are not
claimed portable by this node-model slice. There is no added compiler functionality.
