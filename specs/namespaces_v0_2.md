# v0.2 namespaces, symbol paths and imports
Doc Status: normative

Agreed 2026-10-06 for v0.2. This contract defines language meaning, not current
implementation availability. The my-try Chapter 08 catalog tracks delivery.
It supersedes incidental cross-file visibility from v0.1 C++ import emission and
obsolete catalog restrictions rejecting type imports, aliases or grouped imports.

## Namespace identity and source bodies

A namespace has a shared qualified identity across source files. Each namespace
body is a distinct source occurrence contributing declarations to that identity.
Semicolon and braced namespace notation use the same namespace semantics. Reopening
a namespace does not merge its source bodies or their import environments.
Project module names do not introduce source namespaces.

## Name lookup

- A rooted path such as `\Lib\f` starts at the source-language root.
- A non-root qualified path such as `Sub\f` starts under the current namespace.
- An unqualified symbol uses the current namespace and applicable local imports.
  Ordinary local/parameter scopes retain their existing rules.
- There is no PHP-style fallback to unrelated global user functions. A nested
  namespace does not gain implicit user-symbol fallback through parent namespaces.
- Registered builtins retain their explicit language-scope availability; this is
  not fallback to arbitrary global source definitions.
- The requested symbol kind remains significant: types, functions and constants
  retain their own lookup/validation rules.

## Imports

Imports belong only to the declaring file's namespace body. They do not affect
another body of the same namespace, including another body in the same file.
Import paths are absolute, independent of the importing namespace.

Supported contract forms include plain symbol/type imports, explicit `use function`
and `use const`, aliases with `as`, and homogeneous grouped imports:

```php
use Lib\Box;
use function Lib\compute as calculate;
use const Lib\LIMIT;
use function Lib\{first, second as next};
```

Plain `use` is a symbol import, not a namespace-alias feature. Grouping introduces
individual local bindings. An alias denotes the existing target; it does not create
a new definition or re-export a qualified member of the importing namespace.
For example, importing `Lib\compute as calculate` in `App` does not define
`\App\calculate`.

Missing targets, wrong symbol kinds and conflicting bindings are preparation-time
errors under the v0.2 diagnostic policy, not deferred C++ name-resolution behavior.
Imports do not load files or alter project discovery/execution order.

## Execution and scope of delivery

The selected-entry-file rule remains unchanged. Non-entry files contribute only
declarations. In the selected file, executable statements belong to at most one
namespace body and keep their source order; executable bodies are not concatenated
across namespace boundaries. Declaration-only bodies may coexist with that entry.

Namespace support does not itself enable later class/static-member features,
namespace variables with persistent storage, or additional nested source forms.
Qualified lookup initially applies to supported declaration/expression families;
individual implementation slices must state their admission boundary.

Source namespace meaning must not depend on the generated C++ unit layout. Backend
spelling and import-lowering strategy do not replace semantic resolution.
