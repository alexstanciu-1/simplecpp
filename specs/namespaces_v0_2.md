# v0.2 namespaces, symbol paths and imports
Doc Status: normative

Agreed 2026-10-06 for v0.2. This contract defines language meaning, not current
implementation availability. The my-try Chapter 08 catalog tracks delivery.
It supersedes incidental cross-file visibility from v0.1 C++ import emission and
obsolete catalog restrictions rejecting type imports, aliases or grouped imports.

## Flavor notation and initial visibility (2026-11-04)

Source notation is flavor-specific; AST paths use `\` in every flavor. A leading
canonical `\` on a reference means rooted lookup; declaration identities contain
no leading/trailing separator. A null namespace denotes the source root.

| Intent | PHP flavor | C++ flavor | JS flavor |
| --- | --- | --- | --- |
| Namespace declaration | `namespace App\Tools;` or `namespace App\Tools { ... }` | `namespace App::Tools { ... }` | `namespace App.Tools { ... }` |
| Qualified reference | `Lib\ping()` | `Lib::ping()` | `Lib.ping()` |
| Root-qualified reference | `\Lib\ping()` | `::Lib::ping()` | `\Lib.ping()` |

For JS syntax selection, prefer ES6 constructs where they express the concept,
then TypeScript where ES6 has no equivalent. Namespace declarations follow
TypeScript's braced notation. The leading backslash is an explicit Simple C++
extension, not ES6/TypeScript syntax. Flavor syntax does not import C++/PHP/JS
fallback lookup rules; the common exact lookup rules below govern all flavors.
Dotted JS syntax can also denote member access: a visible local value must not be
silently bypassed to interpret that value's name as a namespace. General member
calls remain outside the first executable slice.

Namespace declarations are all externally visible for this initial slice.
TypeScript-style `export`/API visibility is deferred; unsupported `export` syntax
must be diagnosed rather than accepted and ignored. The existing private implicit
entry function remains private and is not a namespace export. This visibility
boundary does not define future export defaults or access-control semantics.

The active PHP bootstrap initially admits one optional leading namespace body per
file (or the implicit global body). PHP also admits `namespace { ... }` for an
explicit global body. Multiple/reopened bodies in one file, nested declarations,
and anonymous C++ namespaces are not yet implemented. Separate files can
contribute supported functions to the same namespace. These are delivery limits;
the full namespace-body and import semantics below remain the intended contract.

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

### Active bootstrap import slice (2026-11-04)

The active compiler implements plain `use` and `use function` for its supported
no-argument void functions, including `as` symbol bindings and homogeneous groups.
This is not namespace aliasing. All three flavors use `use`, `function` and `as`
for this slice, with their existing path separators (`\`, `::`, `.`). For example,
`use function Lib::ping;` and `use function Lib.ping;` correspond to PHP-flavor
`use function Lib\ping;`. This shared `use` notation is a Simple C++ extension in
the C++/JS flavors; it does not implement C++ using-directives or ES6 module loading.
Groups use the same separator before `{`, for example `use Lib::{ping, pong};`.

Imports are accepted at namespace-body level, including after executable syntax,
and apply throughout that body. They are not accepted inside functions. Type and
constant imports remain deferred with their declaration support; `use const` is
recognized but rejected during preparation. Import bindings affect unqualified
names only; rooted and qualified paths retain exact lookup. No filesystem discovery
or runtime API catalog changes are implied.

## Top-level variable visibility (2026-11-04)

Ordinary top-level variables are not published across source units. For the initial
policy they remain locals of the implicit entry function, with no capture by other
functions in that file. Their placement in a named namespace does not change this.
Same-file function visibility and variable shadowing will become configurable through
project profiles; those settings are deferred, not accepted configuration today.

Explicit `global` declarations publish namespace-qualified variable identities across
source units. Ordinary top-level declarations do not gain publication incidentally.

### Explicit globals — initial integer slice

Globals are admitted at file/namespace level, including in non-entry files. They
declare storage, not executable statements in implicit main. The initial syntax is:

| Intent | PHP flavor | C++ flavor | JS flavor |
| --- | --- | --- | --- |
| Explicit type | `global $value int = 10;` | `global int value = 10;` | `global let value: int = 10;` |
| Inferred integer type | `global $value = 10;` | `global auto value = 10;` | `global let value = 10;` |
| Qualified read | `test\$value` | `test::value` | `test.value` |
| Root-qualified read | `\test\$value` | `::test::value` | `\test.value` |

`global` is a Simple C++ storage declaration, not PHP's function-local global binding
statement. Explicit `int` and `int64`, or an omitted/inferred type, all select the
canonical signed int64 type. Initializers must be decimal integer literals, optionally
preceded by `+` or `-`; range checking includes the exact int64 minimum and maximum.
All declarations are checked, including unused globals. Other types, expression
initializers, multiple declarators and function-local global declarations are deferred.

Each valid qualified global has one storage definition, initialized before entry
execution. Initializers cannot read globals or call functions, so source discovery
order does not impose an initialization-order dependency. Globals are visible before
their textual declaration and in functions and other source units. Duplicate qualified
global names are errors; function and variable names occupy separate symbol kinds.

Unqualified reads use an already-visible local first, then a global in the current
namespace. Qualified and rooted reads use exact namespace lookup with no fallback to
parent or root globals. PHP qualification places `$` on the final name only; AST
paths contain no `$`. JS dotted paths must not bypass a visible local in the first
component: such member reads are currently unsupported; root qualification removes
that ambiguity. A local remains unavailable in its own initializer.

Globals have mutable-storage intent, but writes are not supported by this slice.
Their initializers are not constant facts at read sites: lowering reads storage and
entry returns retain checked native-int conversion. Variable imports are deferred;
`use` continues to import the supported function kind only. Configurable visibility
and shadowing remain deferred; current local precedence is the initial fixed policy.

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
storage beyond the explicit integer-global slice above, or additional nested source forms.
Qualified lookup initially applies to supported declaration/expression families;
individual implementation slices must state their admission boundary.

Source namespace meaning must not depend on the generated C++ unit layout. Backend
spelling and import-lowering strategy do not replace semantic resolution.
