# Logical definition and child comparison
Doc Status: supporting

Status: implemented for the prototype's functions and implicit file entries.
Compilation resolves call names and compares pending symbol pairs before type
and body checking. Inspection mode stops before native building; `--output`
also builds the executable. This stage catalogs syntax changes; it does not
choose rebuild reactions, prove type equivalence or authorize backend reuse.
[Declared return-type resolution](return_type_resolution.md) now follows the frontend stages.

## Boundaries

`parse/Syntax_Access` owns structural function-role access shared by consumers.
`Declaration_Syntax` owns which parts contribute to semantic definition
comparison. A function's name, parameter-list syntax and
return-type annotation contribute to its own definition. Its executable body
is tracked child content. An implicit file entry has no declared signature;
its body uses the same child comparison. AST containment alone does not decide
these semantic roles. No body-specific change property is introduced.

`Syntax_Comparer::equal()` is the common subtree algorithm. It compares node
kinds, complete spelling for names/literals, and ordered children. Structural
spans, byte offsets and node numbering do not contribute to equality. The root's
own sibling is outside the selected subtree; descendant sibling links preserve
list order. A flat iterative stack avoids recursion and per-node comparison
records. The first difference ends that subtree comparison.

Literal spelling is deliberately conservative: `011` and `11` differ here.
No numeric conversion, constant folding or type-name canonicalization occurs.
Every supported syntax kind has an explicit payload rule; a new kind must define
its comparison semantics. Typed parameter syntax now uses this same algorithm:
order, names and annotations contribute to the callable definition; argument
expressions contribute to its body. Per-parameter change rows and parameter
semantics remain later work. Classes and other language forms need their own support.

## Work and catalog

Collection first matches identities and emits `uncompared` pairs. Comparison
selects these pairs; additions/removals have no pair and pass through unchanged.
Full selection uses the same comparison worker, following collection's normal
recomputation of all current file contributions.

`Symbol_Comparer::compare()` reads one fixed previous/current pair and returns a
separate change record. `own_status` becomes `unchanged` or `changed`, and
`children_changed` becomes a known boolean for the supported callables. Own and
child comparisons are independent, so an annotation and a body can both change.
The coordinator checks exact task/result pairing and completeness, assembles
results in catalog order, and omits matched rows proved unchanged in both parts.
A changed body therefore has own status unchanged and children changed; there
is no stored list of statement edits. Execution is serial, with the same work
units and join available for future workers.

The current symbol store is shared unchanged with the comparison output. Logical
equality never restores previous AST references: reparsed files retain their new
positions and name bindings. Omitted equality rows no longer retain old origins;
remaining change rows keep previous/current references without copying syntax.
The session accepts results only after every implemented stage succeeds. The
catalog does not alter `full_rebuild`, resolution selection or code-generation
work. Resolved type/ABI effects remain for later checks and impact rules.

## Verification

[Comparison checks](../../tests/04_analyze/collect_symbols/symbol_comparison.php) cover formatting,
comments and declaration reordering, equal-length literal and callee changes,
return annotations, combined own/child edits, statement addition/removal/order,
bare returns, conservative literal spelling, unchanged add/remove handling,
independent workers and joins, full-build equality, current source references,
sparse-catalog lifetime, 5,000-statement bodies and 70,000-digit literals.
The CLI increment simulation exports the completed child-change classification.
