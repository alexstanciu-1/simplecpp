# Typed static-member facts
Doc Status: planning

2026-10-25; user-approved design, baseline ebb405c21 plus this slice.

Static-member syntax retains exactly one private typed enum/constant/property fact.
Typed setters clear alternatives and retain the supplied object; generic queries
upcast it. Required reads reject absent/wrong alternatives. Member_Preparation
publishes fully constructed facts, then the existing expression epilogue updates
that same object. Consumers in constants/defaults/selection/property writes and
C++ emission use typed syntax accessors. Field-access writes reuse their existing
concrete accessor. No generic-base downcast or duplicate generic slot is introduced.
Existing Preparation_Cleanup maintenance clears every alternative. Language contracts,
lookup rules, qualifier order and prepared_expression polymorphism remain unchanged.

Validation:
- Extracted production fields/methods execute on PHP, v0.1 native, v0.2 ordinary
  and concrete output: identity, generic mutation, replacement, wrong-read rejection,
  clear, required-read rejection and external retained identity all pass.
- Actual host syntax node and Preparation_Cleanup traversal pass all alternative
  replacement and wrong-read checks; reattachment preserves externally held facts.
- source_constants, property_defaults, incremental_preparation, static_members and
  qualified_static_members host suites pass. The latter exercises qualifier effects
  and rejected accesses; these host runs prove preparation/emission, not all emitted
  programs' native execution.
- nullable_defaults initially lacked its required output-directory argument; corrected
  invocation passes. static_redeclarations fails on required local c; an isolated boot
  loading all eight original production files reproduces the identical baseline error.
  Neither failure was concealed or repaired as part of this slice.
- First portable fixture conversion rejected a reserved local name ($enum). Renamed
  the fixture local; one intermediate extraction typo was corrected before final run.

Full bootstrap: 191.95 seconds; 612668 KiB RSS (598.31 MiB); 580.5 MiB PHP heap.
First diagnostic is now canonical-int binary operands. Same 19 message categories
remain, with polymorphic-source narrowing elsewhere. No new categories appeared.
Native compiler build/run and second-generation proof remain incomplete.
