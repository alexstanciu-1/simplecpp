# Global portable function convention
Doc Status: supporting

Portable source files need no function imports. Load the shared PHP runtime bootstrap
once from host composition; unqualified function calls in a namespace fall back to
these global helpers. Fully rooted calls such as `\q_count($items)` are also accepted.
Do not declare/rebind helper names locally or import alternate implementations.

- A helper with no PHP equivalent keeps its plain global name: `fs_read_snapshot`,
  `json_read`, `take_false`, `string_byte_at`, and the other registered helpers.
- An existing PHP function uses a `q_` name. The current fixed catalog provides
  `q_count`, `q_is_bool`, `q_strlen`, `q_substr`, `q_strpos`, `q_strrpos`,
  `q_str_starts_with` and `q_str_ends_with`.

```php
<?php
declare(strict_types=1);
namespace example;

$items /** vector<int> */ = [1, 2];
echo q_count($items);
echo q_strlen('é');
```

`function_map.php` owns spelling, arity, internal PHP implementation and native
binding. Names are fixed by the contract, never selected by function_exists or the
extensions installed on a machine. The q_ prefix does not make arbitrary PHP builtins
convertible. These mappings retain their existing behavioral contracts, including
UTF-8 text semantics versus explicit byte helpers and by-reference take outputs.
The generated PHS uses native helper names, not necessarily the PHP facade spelling.

`runtime/global_functions.php` is the static global facade, loaded by the shared
bootstrap. Internal implementations stay under scpp/scpp\compat. The host generator
`php tools/php_portability/generate_global_functions.php` mirrors their PHP signatures,
including reference parameters and supported defaults. `--check` verifies it is current.
There is no dynamic function dispatch or runtime alias registration in authored code.
Bare original builtins and direct internal namespace calls are rejected by conversion;
there is no per-file choice between PHP and target-like semantics.

The old generated import blocks are obsolete. `sync_imports.php` is retained as a
migration/prologue validation command: it removes well-formed old generated blocks,
never inserts imports, and refuses to erase unexpected authored content. It does not
rename old builtin calls. The active source and maintained proof generators have
been explicitly migrated. Converter/checker refuse leftover import blocks and manual
imports. New code starts directly with its optional strict/lowercase namespace prologue.

Historical prototype/reference trees and saved proof artifacts retain their original
spelling. `src-runtime-preparation` remains PHP as-is and is not migrated. Old slice
examples using imports or bare PHP builtin names are historical spellings; use this
convention and the current function map for new portable source.
