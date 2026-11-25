# Shared types
Doc Status: supporting

`structures.php` defines canonical type data and enum tags for the application.
`language.php` installs hardcoded language definitions when Model initializes or
reinitializes its language scope. `source.php` constructs type definitions from
collected source declarations. Analysis consumes these shared definitions; backend
representation mapping belongs in `05_backend/cpp` or `05_backend/llvm`.

Runtime/library JSON ingestion remains deferred. This move does not change type
identity, scope lookup, or the existing initialization/reset contract.

Current scalar definitions are `int` (signed 64-bit), `bool` (one semantic value
bit), `float` (signed 64-bit floating representation), and binary-safe `string`.
The runtime float wrapper uses binary64 on the current target; C++ spellings and
string-literal escaping stay in the backend mapping.
