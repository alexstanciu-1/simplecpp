# First namespace slice

Doc Status: supporting

The [namespace specification](../../../specs/namespaces_v0_2.md) owns language
meaning. This implementation adds namespaces and body-local imports to existing
no-argument void functions and their statement calls. The separate
[integer-global slice](globals.md) adds explicit namespace storage; ordinary locals stay unchanged.

## Syntax and delivery boundary

One optional namespace declaration must precede the file's code. PHP accepts
`namespace App\Tools;` or `namespace App\Tools { ... }`; C++ accepts
`namespace App::Tools { ... }`; JS accepts `namespace App.Tools { ... }`.
The braced body must consume the file; a namespace may not contain another namespace.
No header means the existing global source body. PHP `namespace { ... }` is also global.
Semicolon namespaces in C++/JS and anonymous C++ namespaces are rejected.

PHP calls use `Lib\ping()` / `\Lib\ping()`; C++ uses `Lib::ping()` /
`::Lib::ping()`; JS uses `Lib.ping()` / `\Lib.ping()`. The leading qualifier means
source root. All AST paths use backslashes; source tokens retain original notation.
In `App`, `ping` resolves to `App\ping`, `Sub\ping` to `App\Sub\ping`, and
`\Lib\ping` to `Lib\ping`. There is no parent-namespace or arbitrary-global fallback.
Paths retain spelling/case; no case-folding is introduced by normalization.

A JS dotted call first resolves a visible receiver value. Its type selects a published
runtime member. A missing method on a value is a diagnostic. When the receiver
has no value binding, the dotted path selects a namespace function. Preparation
watches candidate published values so adding or removing one invalidates an
unchanged caller. Root qualification always selects a namespace path.

Namespace declarations are visible without export. `export` is reserved/rejected
in JS; export/API visibility is deferred and not silently ignored. JS syntax design
uses ES6 first, then TypeScript for constructs such as namespaces that ES6 lacks.
Its leading root backslash is a documented Simple C++ extension.

## Ownership and identity

- `ast_declaration` owns readonly `short_name` and nullable `namespace`. `name()`
  derives qualified identity; `declaration_key()` remains the short local key.
- `scope.namespace` records source context. File construction supplies it; lexical
  body scopes inherit it during construction, independently of later publication parents.
- Public function keys are `func:App\ping`. Duplicate chains compare complete symbol
  keys and keep the existing single declaring owner. Equal short names in different
  namespaces are distinct; same-qualified-name duplicates are preparation errors.
- Local declarations keep a null namespace and `var:name` lexical keys.
- The implicit entry carries source context but stays private, first in the file
  inventory and indexed as `func:main` only in the file reference scope.
- `ast_call_statement.name` is canonical authored reference spelling, including
  a leading backslash when rooted. It is not the selected declaration's copied name.
  Its direct body scope supplies the namespace, without walking to the AST root.
- `Namespace_Syntax` owns flavor syntax; `Source_Names` owns exact canonical path
  qualification. Neither publishes symbols nor builds additional namespace registries.
- The existing file root is the sole namespace-body occurrence for this bounded
  slice. No namespace AST wrapper, copied prepared tree or namespace member index
  is introduced. Its import inventory belongs to this occurrence; multiple bodies
  in one file will require a separate occurrence owner.

Collection publishes qualified identities through the existing scope path. Preparation
watches the exact `symbol_entry`, including a tombstone for a missing qualified name.
A body-only change preserves caller readiness; changing the namespace removes the
old key and adds the new one. Reintroduction reactivates the old entry and invalidates
its observers. No wildcard namespace watches or fallback subscriptions are needed.

C++ lowering mangles the resolved declaration's full identity into its existing
flat function spelling; it never asks Clang to perform source-language lookup.
Syntax inspection reports file context and both short/qualified declaration names;
qualified call spans cover the entire authored path, including its root marker.

## Imports

Plain `use` imports supported symbol kinds independently; `use function` selects functions and `use const` selects constants. `as` renames a local symbol
binding, not a namespace; homogeneous groups create individual bindings. Every flavor
uses these keywords with its existing path separator. These are Simple C++ extensions
in C++/JS, not C++ using-directives or ES6 module loading. For example:

```php
namespace App;
use function Lib\{ping as run, pong};
run(); pong(); return 8;
```

`ast_file_node.imports` owns the syntax occurrences. Its private `function_imports` and `constant_imports`
maps index first references by local name. Repeated imports are retained because
the same binding is valid and idempotent; this inventory is not an error-only
candidate list. No aliases are added to the published compilation scope.
`function_key()` and `constant_key()` select imported targets for unqualified names; other paths
retain exact namespace lookup. Parsing the entire file makes imports independent
of textual order, including imports after calls or after an entry return.

`Import_Preparation` validates every occurrence after function declaration settlement
and before bodies, including unused imports and files with no functions. It checks
absolute targets, repeated binding conflicts and namespace declaration conflicts
across files. [Typed literal constants](constants.md) participate in the same import mechanism; type imports remain deferred.

The incremental coordinator rechecks imports on every preparation pass. This small
linear check catches removed unused targets and newly introduced conflicts even
when the importing AST and body caches are reused. Calls watch the target's existing
stable entry through `Dependency_Watches`; there is no alias entry, copied signature,
import watcher type or extra prepared tree. Changing import syntax replaces the
file's AST through the normal incremental path.

Ordinary top-level variables remain implicit-main locals. Explicit integer globals
are implemented in [their own slice](globals.md). Same-file visibility and shadowing configuration are tracked in
[the policy debt](../../specs/planning/variable_visibility_debt.md).

## Mined concepts and deferred work

Reviewed `compiler/my-try/03_parse/scopes/namespaces.php`, `scopes/lookup.php`,
`03_parse/parser.php` and `04_analyze/prepare/imports.php`. Retained the distinction
between namespace identity, source occurrence and path interpretation. Did not copy
its synchronized registry, retained-body reconciliation or candidate collections.

Deferred: multiple/reopened bodies within one file, nested namespace forms,
type imports, export visibility/API rules, additional declaration
kinds, namespace-aware builtin catalogs and general JS member resolution. Namespace
support does not change project module loading or add dependencies automatically.

## Verification

`tests/namespaces.php` checks all three surface notations, canonical AST names,
source spelling, same-namespace/cross-file and rooted/relative calls, duplicate
qualified names, absence of global/parent fallback, unsupported syntax and
incremental namespace rename/reintroduction with unchanged caller AST reuse.
`tests/namespaces_native.py` builds emitted code for each flavor at O2/g0 using
Ninja -j8 and mold, checks stdout/exit 8, and retains separate Clang frontend/LLVM
and mold traces. These are single-run correctness timings, not comparative benchmarks.

See [the retained native proof](namespace_slice_proof.md) for frontend/LLVM/linker
timings and validation details, including the mixed-flavor project.

`tests/imports.php` checks all three flavors, grouped/renamed bindings, repeated
bindings, textual-order independence, file isolation, unused failures, conflicts,
AST/target identity, inspection, lowering purity and incremental target retirement
and reactivation. Run `tests/namespaces_native.py --imports` for its Clang executable
proof and separate frontend/LLVM/linker measurements.
See [the import slice proof](import_slice_proof.md) for structure changes and retained measurements.

For project-controlled native names, generated provider headers and runtime flavor
lookup, see the [application namespace and adapter plan](../../specs/planning/app_namespace_and_flavor_adapters.md).

## Native application and runtime names

The selected app’s `module.json` controls the C++ namespace for emitted definitions and
cross-unit declarations. The default is `App`. For example, `"cpp_namespace": "acme::ledger"`
produces a global `main()` that calls `::acme::ledger::scpp_entry_body()`. JSON `null`
selects global emission. Each namespace component starts with an ASCII letter and
contains letters, digits or underscores. C++ keywords, double underscores and the
reserved components `main`, `std` and `scpp` are rejected. Changing this option
requires re-emitting all app units. Source-language namespace lookup and stable symbol keys
are independent of this setting.

`scpp::types` exposes the same canonical C++ types and ABI through using-declarations.
Each provider contributes its own names when included. For example, the string provider
makes `string` available alongside the core scalar provider’s `int64`, `byte` and
`bool_t`. An adapter-consuming implementation can write:

```cpp
#include <scpp/php/string.hpp>
namespace App {
using namespace ::scpp::types;
using namespace ::scpp::php;
bool_t matches(const string& value, const string& prefix) {
    return str_starts_with(value, prefix);
}
}
```

Public app headers use qualified types. In global-emission mode the using-directives
are function-local. Prefix, suffix and containment predicates share one header per flavor.
PHP exposes `str_starts_with`, `str_ends_with` and `str_contains`. JS uses
`startsWith`, `endsWith` and `includes`; C++ uses `starts_with`, `ends_with`
and `contains`. Each inline adapter forwards to its canonical byte predicate in `::scpp::str`.

First and last byte search use the same flavor headers. PHP spells them `strpos` and
`strrpos`, JS uses `indexOf` and `lastIndexOf`, and C++ uses `find` and `rfind`.
PHP declares `result_or_false<int>` for both calls. The inferred local keeps the
carrier. Use `isset($pos)` to distinguish a successful zero offset from the false
sentinel. JS and C++ return an `int64` byte offset and −1 on a miss. An empty needle matches at zero for first search and at the byte length
for last search.
