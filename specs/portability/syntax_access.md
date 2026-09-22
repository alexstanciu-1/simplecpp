# Syntax role access and logical comparison
Doc Status: supporting

This component exposes read-only structural queries over the parser's Syntax_Arena
and Parse_Result. It retains the prototype ownership layout: `data/role_views.php`,
`utilities/syntax_access.php`, `utilities/metaprogramming_syntax.php`,
`utilities/struct_member_cursor.php` and `utilities/syntax_comparer.php`.
Project selection, joins, name/type resolution and semantic change policy stay with
later owners. The converter gained no additional responsibilities.

Syntax_Access validates the requested child roles and returns existing source-local
IDs for functions, parameters, locals, assignments, control flow, structs/fields,
templates/constants, calls and declaration wrappers. This is not complete validation
of arbitrary graphs. Use fixed parsed trees; callers must not mutate a tree during
queries or cursor traversal. Arena row reads remain explicit value copies. Returned
role views are fresh scalar value records, not retained tree copies or shared owners.

Each role field is uint32, matching the existing bounded arena IDs. Zero denotes an
absent optional role. `parameter_parts.reference` is a syntax-kind tag, not a node ID;
zero replaces the prototype's null modifier. Readers normalize compact tags and IDs
with `(int)` where signed comparisons or return boundaries require it. Struct-backed
views have no PHP readonly/identity contract; changing one result does not change the
stored syntax or a subsequent independently produced view.

Struct_Member_Cursor validates lazily, selects matching field/method IDs in source
order, supports stable repeated current() reads and becomes terminal on exhaustion
or failure. It does not materialize a member list or retain copied nodes. The input
tree must remain fixed. Unsupported/malformed role queries fail at their local
boundary; queries are not a replacement for the parser's valid-tree guarantees.

Syntax_Comparer walks paired subtrees iteratively. It compares kinds, ordered children
and the exact spelling of names, variables and scalar literals. It ignores offsets,
comments and whitespace outside those spellings. It does not compare evaluated values
or canonical types: differently spelled literals can differ. A selected root's own
siblings are excluded. Two zero roots mean equal absence; one zero root means unequal.
Nonzero roots are checked before entering compact traversal storage. Comparison frames
are values in a vector; explicit push publication avoids PHP object-alias behavior
when a slot is reused. No generated-code patch or runtime helper was needed.

## Proof and performance boundary

The component passes 134 PHP/native outcomes:

- Role queries over 35 accepted inputs reused from the file-parser corpus, compared
  with the retained query implementation using role-node kinds and source spans.
- 73 comparisons: moved/commented equal input, changed entry bodies, selected-root
  sibling exclusion and changed declaration/string spellings. Expected equal/unequal
  results are independently specified and also checked against the old comparer.
- 26 independent boundary checks: malformed role roots, absent roles, invalid and
  unknown comparison roots/kinds, field/method cursor filtering, const receiver flags,
  stable reads, lazy failure/exhaustion and independent role results.

This includes deep/wide grammar inputs from the preceding parser proof. Original
project-wide semantic/session unit bodies are not claimed to pass unchanged.
The generated headers confirm value-struct views/frames; no aggregate memory saving
or optimized release-performance claim is made without a dedicated measurement.

```sh
python3 compiler/tests/syntax_access/run.py --results FRESH
python3 compiler/tests/syntax_access/run.py --results FRESH_NATIVE --target-checkout TARGET
```

See [checkpoint timing and evidence](../planning/compiler_migration/results/syntax-access-01/README.md).
[Project parser selection, joins and reuse](parser_project.md) now has a focused proof. src-runtime-preparation remains PHP
as-is, outside this conversion scope.
