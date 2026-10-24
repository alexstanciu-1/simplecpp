# Required type syntax checked accessors — 2026-10-24
Doc Status: planning

Base b0a3571f6 plus candidate hashes. User approved source-only private nullable
storage and checked required accessors for variable_declaration_node and
callable_input_type_node. No language, converter or runtime changes.

Each owning class has private type_syntax_value, require_type_syntax and
set_type_syntax. Existing constant initializer protocol is reused as the source
pattern; no new shared base/trait abstraction. Parser setters remain at the old
assignment points, including array-type normalization and catch binding. Node
allocation, collection, span finalization and input insertion order stay unchanged.
Required getters feed preparation, maintenance and child traversal. Optional
parameter/property type_syntax fields remain optional. LLVM direct reads are
mechanically updated; LLVM semantics/development/testing is not resumed.

## Proofs

Extracted production fields/accessors, maintenance and both child iterators pass
PHP, legacy, ordinary and concrete native routes. Native task times
24.35/26.55/23.73 s. Dependency doubles bound the fixture; no full native compiler
claim. Proof covers early-read and early-child failure, identity, failed RHS keeping
the old type, optional initializer omission, traversal order and independent updates.

Actual AST, structure-access, nullable, qualified-lookup and callable-input tests
pass. AST proof covers fixed-array parsing. constructed_type's canonical vector
identity assertion and exceptions' catch_lifetime null rejection fail identically
against an isolated baseline with HEAD production files and original test sources;
these are recorded separately and not counted passing or repaired.

New required_type_syntax.php passes both ordinary/concrete preparation/publication,
plain/vector declarations, callable inputs, catch type shape, exact no-op node/type
identity and malformed-type repair preserving function owner and generated output
(0.50 s task). Its first attempt requested fixed-array preparation, which is already
gated; changed only that fixture to supported vector syntax. Fixed-array parsing
coverage remains in the passing AST test. Shared runner used CLI fallback because
FPM socket startup was unavailable.

## Bootstrap

79.29 s wall / 355740 KiB peak RSS (347.40 MiB) / 324 MiB PHP heap.
Removes required type_syntax diagnostic, adds none. First remaining preparation
failure is required base initialization; left unchanged. Total task 84.46 s includes
conversion/setup. No completed native compiler bootstrap is claimed.
