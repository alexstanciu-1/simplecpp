# Bounded concrete-instantiation policy
Doc Status: supporting

`instantiate/Instantiation_Policy` reads one explicit policy path before work
selection. Its `parse(path, content)` method validates a JSON object containing
`max_instances`, an integer in `1..collect_symbols/MAX_SYMBOL_ID`. Missing values,
booleans, strings, fractional/exponent numbers, out-of-range integers and malformed
JSON are rejected. Additional object members remain accepted, matching the
prototype. The result is one immutable integer snapshot; the session must include
that selected value in its cache-validity decision.

`load(path)` retains a path-attributed read error and delegates to the same parser.
`input_path(language_directory)` spells the default data filename relative to a
host-selected compiler language-data directory. Explicit overrides go directly to
`load`. A native executable must not use the converter's PHP source location as
its installation root. Wiring default data discovery and tracking the input file
belongs to the future compiler session integration; no hardcoded reference-tree
location or fabricated default limit is introduced here.

The retained default file is still
`compiler/reference/pre-rewrite/language/instantiation_limits.json` (4096).
Migrating deployment/data discovery is separate from validating its content.

`compiler/tests/instantiation_policy` compares 22 accepted/rejected documents in
portable PHP and native execution, with the preserved prototype as an additional
host oracle. It also exercises explicit path spelling, a real file read and a
missing-file error. The production symbol bound remains authoritative; this
component does not redefine it or enforce the budget during allocation.
