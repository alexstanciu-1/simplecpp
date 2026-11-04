# Explicit scalar globals

Doc Status: supporting

The [namespace specification](../../../specs/namespaces_v0_2.md) owns semantics.
PHP `global $value = 10;`, C++ `global auto value = 10;` and JS
`global let value = 10;` create the same namespace-qualified int64 storage.
Explicit `int`/`int64`, `bool`, `null`, `byte` and `float`/`float64` destinations are supported.
Initializers admit signed decimal integer/floating, boolean and null literals; omitted types infer
the literal type. Written types authorize metadata-driven checked conversion.
See [the scalar contract](../../../specs/compiler_scalars_v0_2.md). Writes remain deferred.

## Ownership and preparation

- `ast_global_declaration` owns its name/namespace, optional written type and scalar
  initializer child. The prepared type and initialization conversion are on the declaration; the checked literal
  value is on its initializer. There is no duplicate prepared-global record.
- `ast_file_node.declaration_nodes` and `declarations` now contain functions and
  globals. Private implicit main remains first when present. `globals()` and
  `published_declarations()` provide filtered views, not additional retained inventories.
- `Symbol_Publication` publishes both kinds through the existing compilation scope.
  Global keys are `var:qualified_name`; function keys remain `func:qualified_name`.
  Compilation scope owns global duplicate chains; file indexes only reference them.
- `Global_Syntax` owns the small flavor-specific declaration syntax.
  `Namespace_Syntax.variable_path()` normalizes qualified reads, including PHP's
  final `$variable` token. Qualifying a read does not create a new declaration.
- `Global_Preparation` validates and settles all globals before bodies. The common
  `type_int64` checks magnitude before host conversion, including int64 minimum.
- `ast_name_expression.binding` is one nullable reference: a local AST declaration
  or a stable global `symbol_entry`. `declaration()` follows the current global target;
  `local_def()` remains the checked local-only view for local consumers.

Body preparation first selects a visible local, otherwise resolves the exact global
key in its namespace. It observes globals through the existing `Dependency_Watches`.
Global reads have unknown constant value; consumers do not copy initializers or inspect
foreign bodies. Ordinary top-level variables remain implicit-main locals and are not
captured by other functions. Shadowing/visibility settings remain
[configuration debt](../../specs/planning/variable_visibility_debt.md).

## Incremental behavior

Publication compares global existence, ambiguity and written storage-type contracts,
alongside existing function contracts. Removal/type change invalidates readers; a
reintroduced key reuses its stable entry. No global-specific dependency coordinator
or observer type is introduced.

An initializer-only edit replaces the declaration and prepares its initializer but
does not invalidate an unchanged reader body: it consumes storage type, not the
initializer value. Its stable handle follows the replacement declaration. Future
constant propagation or writes must revisit dependencies if they introduce new facts.
Unsupported types and literal overflow are diagnosed even for unused globals.
The existing mtime-plus-size file detection limitation remains unchanged.

## Lowering

Each file emits its own globals as `constinit` native int64 definitions, including
files without functions. Reads emit native variable accesses and only the required
external declarations. Names use a global-specific prefix plus the canonical name's
hexadecimal bytes, independently of translation-unit layout. No global registry or
initialization dispatcher is introduced; literal initialization has no dynamic order.

`Cpp_Emitter::source_definitions()` replaces the function-only secondary-file entry
point. The generated native-value metadata still supplies the type/literal spelling.
Runtime headers are included only through the existing public API. Checked returns
remain checked even when a global currently has a literal initializer.

Stage inspection includes global declarations and prepared initializer values. The
panel preserves its name-grouped publication rows, with a kind field distinguishing
globals from functions, and reports prepared globals per source file.

## Proof

`php -n v0.2/compiler/tests/globals.php` checks all flavors, mixed-flavor composition,
forward and cross-file reads, local shadowing, exact namespace lookup, static storage,
int64 boundaries, diagnostics, duplicate chains, stage inspection, emission purity,
incremental AST reuse, changed initializer targets, removal/reactivation and type changes.

`python3 -B v0.2/compiler/tests/namespaces_native.py --globals` compiles and executes
the fixtures with separate Clang frontend/LLVM/linker traces. See
[the retained global proof](global_slice_proof.md) for measurements.
