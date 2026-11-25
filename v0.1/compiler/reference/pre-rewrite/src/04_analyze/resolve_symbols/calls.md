# Resolve symbols call map
Doc Status: supporting

Caller: Phases::run_symbols(). Fixed inputs are the complete project symbol index,
provider catalog and immutable source frontends. body.php owns Resolution_Worker,
composed with handlers/ for declaration annotations, parameters, constants,
statements and expressions. Workers produce private results; join.php accepts them.

The following are ordered lifecycle calls, not calls between siblings.

```text
Symbol_Resolver                                  main_resolve_symbols.php
  init() -> [action] select fixed source-owner tasks
  run() -> Resolution_Worker::run() [each task]    body.php
    -> definition(); template_scope()             handlers/names.php
    -> [if body] parameters(); statements()        handlers/declarations.php; statements.php
    -> expression(); bind_name()                   handlers/expressions.php; names.php
  finalize() -> Resolution_Join::join()             join.php
    -> Resolution_Validity::is_current()           utilities/resolution_validity.php
    -> [each new result] Binding_Coverage::complete() utilities/binding_coverage.php
  result() => Resolution_Set                       data/store.php
```

status() and supports_run() are always available. Result/store access requires
finished status; invalid timing throws without changing the state.

Symbol_Store::resolution_records() supplies source declarations and entries,
including templates and constants. Template bodies bind without entering executable
body selection. Function_Lookup and Declaration_Lookup bind exact project/provider
declarations; they never prepare canonical types or layouts. Template parameter
slots, runtime locals and scoped constants retain distinct identity domains.

Resolution_Validity checks current syntax, lookup targets and formal parameter
schemas. Binding_Coverage checks new output coverage and lexical ownership without
repeating global lookup. The accepted set also retains its fixed symbol context
for consumers following source declaration references into concrete preparation.

Provider family applications resolve through the existing template-type symbol lookup.
`Resolution_Worker::application_arguments()` reads formal arity from a normalized
family declaration, so multiple ordered type slots are bound without specialization.
Storage families retain their separate one-element contract. Member occurrences keep
receiver/name bindings; symbolic checking resolves the member from its family owner.
