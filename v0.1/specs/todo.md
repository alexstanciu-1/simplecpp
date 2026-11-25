Doc Status: planning



# Prism++ â€“ First Practical Use Roadmap

## Scope
This document lists the minimal missing features required to make Prism++ usable for a first real project.

---

## Quick ones

- throw _ try/catch/finally
- Anonymous functions / closures, plus closure binding behavior.
- Arrow functions and first-class callable syntax, both listed in the PHP language reference.
- Enums, which are part of modern PHPâ€™s language surface. Named argumentsconstructor property promotion, union types, and match,

---

## Gaps Table

| Area | Feature | Status | Why it matters | Minimal scope for v1 |
|------|--------|--------|----------------|----------------------|
| **Core structure** | `require` (multi-file) | ðŸ”´ missing | Cannot scale beyond single file | include + compile/link model |
| | Cross-file symbols | ðŸ”´ missing | Functions/classes unusable across files | global registry / TU merge |
| | `__DIR__` / path base | ðŸ”´ missing | Needed for relative includes | constant + basic path join |
| **Runtime basics** | Builtin functions (string) | ðŸ”´ missing | 80% of real code uses them | strlen, strpos, substr, trim, explode, implode |
| | Builtin functions (file/dir) | ðŸ”´ missing | Any CLI/tooling needs this | file_get_contents, file_put_contents, is_file, scandir, mkdir, unlink |
| | Builtin functions (json) | ðŸ”´ missing | Config / APIs | json_encode, json_decode |
| | Builtin functions (env/process) | ðŸ”´ missing | CLI + config | getenv, argv |
| **Control flow** | `throw` / `try-catch` | ðŸ”´ missing | Required for non-trivial logic | basic exception type + catch |
| | `exit` / `die` | ðŸ”´ missing | Control termination | simple exit(int/string) |
| **Data structures** | Typed map/dictionary | ðŸ”´ missing | Needed before full PHP array | map<string, T>-like |
| | php-like-array | ðŸŸ¡ optional | Needed for PHP parity, not first use | defer |
| | `dynamic_t/stdClass` | ðŸŸ¡ optional | Dynamic object use | simple keyâ†’value object |
| | variants | ðŸŸ¡ optional | Flex typing convenience | defer if strict typing ok |
| **Interop / utility** | Reflection-lite | ðŸŸ¡ missing | Common patterns depend on it | class_exists, method_exists |
| | Basic path utilities | ðŸ”´ missing | Avoid manual string hacks | join / normalize paths |

---

## Priority Order

1. Core structure (require + symbol model + __DIR__)
2. Builtin function layer (string + file + json)
3. Typed map/dictionary
4. Exceptions (try/catch)
5. exit / die
6. Optional: dynamic_t/stdClass, variants, php-array

---

## Key Insight

The main blocking factor is not language syntax but runtime surface:
- Builtins
- Filesystem
- Basic containers

Without these, real-world usage is severely limited.

=========================


TODOs in big lines (my notes)

"known semantic mismatch buckets"
	 ... areas where your system is intentionally not PHP-equivalent, or is only partially equivalent.

condition/runtime alignment
	make object-handle truthiness explicit in the shared condition helper
		- shared_p<T>: non-null true, null false
		- unique_p<T>: non-null true, null false
		- weak_p<T>: live target true, expired/empty false
	finish mixed_t condition delegation so approved active kinds follow the carried/lowered kind centrally
	unify logical operators and conditional operators behind the same condition_truthiness authority


builtin function layer
	This is probably the biggest one in general terms. Not just curl, but a usable first batch:
	string: strlen, strpos, substr, explode, implode, trim
	file/dir: file_get_contents, file_put_contents, is_file, is_dir, scandir, mkdir, unlink, basename, dirname, realpath, glob
	json: json_encode, json_decode
	process/env: getenv, argv
	network/http later: curl_*

installer / toolchain completeness
	Add CMake to the installer-managed toolchain on Linux/WSL so runtime-native bring-up and CMake-based smoke paths work without manual follow-up.

error handling
	throw, try/catch, finally are still open in the catalog. For real project code, this becomes a gap fast.

exit / die
	Small feature, high practical value.

associative container support
	I would not keep this fully under â€œoptional php-like-arrayâ€.
	Even before full PHP array semantics, a typed map/dictionary is very useful for first projects.

multi-file symbol resolution model
	require is only half the work. You also need:
	cross-file function/class visibility
	ordering / entry rules
	duplicate-definition policy

dynamic dispatch helpers / basic reflection-lite
	Not full PHP reflection, but things like:
	class_exists
	method_exists
	maybe is_a
	These are often used in practical code.

filesystem/path convenience
	Separate from generic builtins because it matters early:
		__DIR__
		path join helper
		normalized relative include/import resolution

My compact ranking for first practical use:

	require + cross-file model
	builtin function layer
	typed map/dictionary support
	throw / try-catch
	exit / die
	dynamic_t/stdClass
	variants
	full php-like-array
