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

A qualified type name may have only one source declaration. Classes, structs,
interfaces, traits and enums share this type namespace; different declaration kinds
do not permit the same type name to be redeclared. This applies across files and
reopened namespace bodies, including unused declarations. Reject duplicates during
semantic preparation, before backend emission. Imports do not create declarations;
functions and constants retain their separate symbol namespaces.

## Name lookup

- A rooted path such as `\Lib\f` starts at the source-language root.
- A non-root qualified path such as `Sub\f` starts under the current namespace.
- An unqualified symbol uses the current namespace and applicable local imports.
  Ordinary local/parameter scopes retain their existing rules.
- There is no PHP-style fallback to unrelated global user functions. A nested
  namespace does not gain implicit user-symbol fallback through parent namespaces.
- Registered builtins retain their explicit language-scope availability; this is
  not fallback to arbitrary global source definitions.
- Root-qualified names also expose registered language/runtime symbols when no
  root source declaration of that kind shadows them. Imports may alias these
  existing type, function and constant identities. Named namespace qualification
  remains exact: `Missing\\vector` does not expose the runtime `vector`.
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

Plain imports expose the supported symbol kinds at the target name independently:
a type and a function with the same qualified name may both be imported by one
plain `use`. Explicit `use function` / `use const` expose only that kind. Binding
conflicts are checked per kind, including collisions with declarations in the
current namespace. Repeating the same target binding is idempotent; different
targets for the same local name and kind conflict. Every import is validated even
when unused. Imports apply throughout their declaring namespace body, irrespective
of their textual position among declarations and executable statements.

Missing targets, wrong symbol kinds and conflicting bindings are preparation-time
errors under the v0.2 diagnostic policy, not deferred C++ name-resolution behavior.
Imports do not load files or alter project discovery/execution order.

## Source constants — bounded v0.2 slice

Global and named namespace bodies admit `const NAME = initializer;` declarations.
The type is inferred from the initializer. Constants are immutable values, not
executable statements or mutable namespace storage. The initial supported values
are `bool`, canonical signed `int`, `float` and `string`.

Initializers accept scalar literals, references to supported constants, parentheses
and numeric unary `+`/`-`. Numeric signs require `int` or `float`; they do not apply
truthiness or numeric-string coercion. Preserve exact integer values and decoded
string bytes; floating literal rounding follows the target floating representation.
Out-of-range integer values/negation reject rather than wrapping.

Forward and cross-file references use ordinary namespace and body-local import
rules. All declarations are checked, including unused ones. Missing or ambiguous
references, duplicate constant names in the same namespace, dependency cycles and
unsupported initializers reject during preparation. A constant's initializer sees
its declaring body’s imports, not the environment of a consuming reference.

This first slice does not enable arithmetic folding, function calls, null/wrapper/
container/object constants, explicit constant type annotations, comma-separated
declarations, local constant declarations or class constants. These are delivery
boundaries, not a commitment to forbid later extensions. Existing parameter-default
admission is unchanged. Source constants do not expand general scalar-operator
availability; compile-time numeric signs are evaluated by constant preparation.

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
