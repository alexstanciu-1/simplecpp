# Logical syntax comparison
Doc Status: supporting

Syntax_Comparer retains iterative subtree comparison: kind, spelling and ordered
children matter; source IDs, node IDs and offsets do not. Each selected root's own
siblings remain outside the comparison boundary. Zero denotes absence, and invalid
node IDs or unsupported kinds preserve their LogicException messages.

Comparison_Stack owns reusable typed Comparison_Frame rows instead of heterogeneous
PHP triples. A popped frame's scalar values are copied to locals before another push
may reuse its slot. Siblings are pushed before children, preserving traversal and
failure order. Storage retains its high-water capacity until comparison completes;
no performance improvement is claimed. No input syntax or source record is modified.

Spelling uses explicit byte slices. The supported-kind classification remains an
exhaustive vocabulary contract, not a guessed rule based on enum numbering. Enum
names for unsupported-kind errors use the existing Syntax_Kinds owner.

The converter now permits while through its existing structural expression/block
path. This does not add generators, dynamic calls, alternate loop syntax or general
PHP control flow. The comparison algorithm supplies the production loop proof.

A frozen original comparer is the host oracle for 1,500 deterministic acyclic graph
pairs, including mutations, absent/out-of-range roots and unsupported kinds. The
oracle compares values/errors and verifies unchanged serialized inputs. The native
fixture independently checks remapped IDs and offsets, multibyte spelling, excluded
root siblings, absence, changed kinds/lengths/membership, invalid IDs, unknown kinds
and simultaneous pending sibling/child frames.

Evidence: `specs/planning/compiler_migration/results/syntax-comparer-01/summary.json`.
PHP/native validation passes on `2f0d667f38a35ff02ef77e813f409189cba2d032`, as do
all seventeen retained compiler fixtures. Thirty production files are ready.
The complete parser and semantic comparison phase still need migration.
