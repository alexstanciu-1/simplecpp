# Collect symbols call map
Doc Status: supporting

Compiler_Session runs collection before entry/name resolution, then comparison after name
resolution. collect.php owns File_Collector with handlers/declarations.php; compare.php owns
Comparison_Worker. join.php and comparison_join.php accept their distinct batches. data/ owns
symbols and refresh output. The coordinator decides whether comparison requires repeating
frontend work with full selection.

The following are ordered lifecycle calls, not calls between siblings.

```text
Declaration_Collector                                  main_collect_symbols.php
  init() -> [action] select fixed file frontends
  run() -> File_Collector::collect_file() [each task]
  finalize() -> Declaration_Join::join() -> import(); append()
  result() => Symbol_Refresh / Symbol_Store

Symbol_Comparer                                  main_compare_symbols.php
  init() -> [action] select fixed symbol comparisons
  run() -> Comparison_Worker::compare() [each task]
  finalize() -> Comparison_Join::join()
  result() => Symbol_Refresh / Symbol_Store
```

status() and supports_run() are always available. Result/store access requires
finished status; invalid timing throws without changing the state.

Declaration_Join also receives the fixed optional Runtime_Input_Set and normalized source-family declarations. After source
results it joins provider declarations into the same symbol index and checks
name collisions. Provider records have `external` instead of a frontend/body.
Comparison_Worker compares those declaration references without visiting syntax.

Template structs/functions retain their wrapper and underlying body syntax under
separate symbol kinds; constants receive declaration identities too. Symbol_Store
owns allocated project IDs. resolution_records() includes every source owner;
body_records() requires has_executable_body(), excluding templates. Comparison
includes complete template definitions and constant initializers, so these edits
retain the existing conservative rebuild policy.

Provider families retain `family_declaration` payloads. Their exposed operations
become `family_method` payloads under the family's `owner_symbol_id`; constructors
and cleanup remain lifecycle roles, without global function exposure or AST nodes.
`Declaration_Join::import()` reconciles both ordinary imports and family members.
Members reuse exact contract identity on unchanged input; their names may repeat
under different owners. The symbol store allocates IDs; metadata does not supply
project symbol IDs or pretend to provide concrete type/layout readiness.
