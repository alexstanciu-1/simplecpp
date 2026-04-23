# Runtime Structure - Shared Base and Language Adapters
Doc Status: normative
Status: active normative architecture rule.

## Purpose

Defines physical organization of runtime code to prevent semantic conflicts, enforce ownership, and avoid premature abstraction.

## 1. Shared base versus language adapters

### Rule 1.1 - Shared base semantic families

The shared Prism++ semantic base lives in shared runtime folders and namespaces.

Current direction:
- namespace root: `scpp::`
- operator families under `runtime/include/operators/`
- cast families under `runtime/include/casts/`

These shared families are the default semantic authorities.

### Rule 1.2 - Language adapter families

Each language has its own dedicated language adapter folder.

Current example:
- `runtime/include/lang/php/`

Language-facing semantic adapters live under language-owned subfolders such as:
- `runtime/include/lang/php/operators/`
- `runtime/include/lang/php/casts/`

These adapters expose stable frontend-facing entrypoints such as `scpp::php::*`.

### Rule 1.3 - Forward by default, override explicitly

When a language uses the shared Prism++ semantics unchanged, the language adapter should forward directly to the shared `scpp::*` family.

If a language later requires different behavior, the language adapter may override that family explicitly.

Current rule:
- Prism++ semantics and PHP semantics are currently the same by default
- PHP adapters should therefore be thin wrappers unless a deliberate divergence is introduced later

## 2. Language isolation

### Rule 2.1 - Language syntax/frontends remain isolated

Each supported language MUST still have its own dedicated runtime adapter folder.

Example:
- `runtime/include/lang/php/`
- `runtime/include/lang/js/`
- `runtime/include/lang/python/`

### Rule 2.2 - Do not prematurely split shared families

Do not duplicate a shared semantic family into per-language implementations unless a real language-specific divergence is required.

At this stage:
- shared semantics should live once in the shared runtime base
- language-specific behavior should be introduced only when needed

## 3. Operator and cast organization

### Rule 3.1 - Use family folders

Shared semantic families MUST be grouped by behavior family.

Examples:
- `runtime/include/operators/conditional/`
- `runtime/include/operators/coalesce/`
- `runtime/include/operators/logical/`
- `runtime/include/casts/`

Current shared-base examples:
- `runtime/include/operators/coalesce/coalesce.hpp`
- `runtime/include/operators/conditional/condition_truthiness.hpp`
- `runtime/include/operators/conditional/conditional_selection.hpp`

### Rule 3.2 - Mirror family shape in language adapters

Language adapters should mirror the shared family shape whenever possible.

Examples:
- `runtime/include/lang/php/operators/conditional/`
- `runtime/include/lang/php/operators/coalesce/`
- `runtime/include/lang/php/casts/`

Current PHP adapter examples:
- `runtime/include/lang/php/operators/coalesce/coalesce.hpp`
- `runtime/include/lang/php/operators/conditional/condition_truthiness.hpp`
- `runtime/include/lang/php/operators/conditional/conditional_selection.hpp`

This keeps future overrides localized and discoverable.

### Rule 3.3 - No semantic family ownership in `support/`

Operator and cast semantics MUST NOT be placed in `support/`, `misc/`, or unrelated runtime files.

`support/` is for non-semantic utilities only.

Current PHP-builtins note:
- PHP builtin/string helper implementation may still live in `runtime/include/lang/php/support/` while the project is PHP-only
- this applies to areas such as `php_string.hpp` and `php_common.hpp`
- these files should match PHP-facing behavior first and can be structurally revisited when multi-language builtin organization is needed

## 4. Supporting structure

Allowed shared-base roots:
- `operators/`
- `casts/`
- `intrinsics/`
- `support/`
- `detail/`

Allowed language-adapter roots:
- `lang/<lang>/operators/`
- `lang/<lang>/casts/`
- `lang/<lang>/support/`
- `lang/<lang>/detail/`

## 5. Forbidden patterns

- semantic family authorities hidden in `support/`
- multiple unrelated entrypoints for one family
- generator-facing code calling shared `scpp::*` families directly
- language adapters silently reimplementing shared semantics without explicit need
- parallel real implementations of the same family

## 6. Summary

- shared Prism++ semantic families live in shared runtime roots
- language layers expose stable frontend-facing adapters
- language adapters forward by default
- language-specific overrides are explicit
- operators and casts are centralized by family
- `support/` is non-semantic
