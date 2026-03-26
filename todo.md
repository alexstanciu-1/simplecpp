
# Simple C++ – First Practical Use Roadmap

## Scope
This document lists the minimal missing features required to make Simple C++ usable for a first real project.

---

## Gaps Table

| Area | Feature | Status | Why it matters | Minimal scope for v1 |
|------|--------|--------|----------------|----------------------|
| **Core structure** | `require` (multi-file) | 🔴 missing | Cannot scale beyond single file | include + compile/link model |
| | Cross-file symbols | 🔴 missing | Functions/classes unusable across files | global registry / TU merge |
| | `__DIR__` / path base | 🔴 missing | Needed for relative includes | constant + basic path join |
| **Runtime basics** | Builtin functions (string) | 🔴 missing | 80% of real code uses them | strlen, strpos, substr, trim, explode, implode |
| | Builtin functions (file/dir) | 🔴 missing | Any CLI/tooling needs this | file_get_contents, file_put_contents, is_file, scandir, mkdir, unlink |
| | Builtin functions (json) | 🔴 missing | Config / APIs | json_encode, json_decode |
| | Builtin functions (env/process) | 🔴 missing | CLI + config | getenv, argv |
| **Control flow** | `throw` / `try-catch` | 🔴 missing | Required for non-trivial logic | basic exception type + catch |
| | `exit` / `die` | 🔴 missing | Control termination | simple exit(int/string) |
| **Data structures** | Typed map/dictionary | 🔴 missing | Needed before full PHP array | map<string, T>-like |
| | php-like-array | 🟡 optional | Needed for PHP parity, not first use | defer |
| | `stdClass` | 🟡 optional | Dynamic object use | simple key→value object |
| | variants | 🟡 optional | Flex typing convenience | defer if strict typing ok |
| **Interop / utility** | Reflection-lite | 🟡 missing | Common patterns depend on it | class_exists, method_exists |
| | Basic path utilities | 🔴 missing | Avoid manual string hacks | join / normalize paths |

---

## Priority Order

1. Core structure (require + symbol model + __DIR__)
2. Builtin function layer (string + file + json)
3. Typed map/dictionary
4. Exceptions (try/catch)
5. exit / die
6. Optional: stdClass, variants, php-array

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


builtin function layer
	This is probably the biggest one in general terms. Not just curl, but a usable first batch:
	string: strlen, strpos, substr, explode, implode, trim
	file/dir: file_get_contents, file_put_contents, is_file, is_dir, scandir, mkdir, unlink, basename, dirname, realpath, glob
	json: json_encode, json_decode
	process/env: getenv, argv
	network/http later: curl_*

error handling
	throw, try/catch, finally are still open in the catalog. For real project code, this becomes a gap fast.

exit / die
	Small feature, high practical value.

associative container support
	I would not keep this fully under “optional php-like-array”.
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
	stdClass
	variants
	full php-like-array

