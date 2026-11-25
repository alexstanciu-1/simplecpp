# Purpose-aware checked conversions
Doc Status: supporting

`check_bodies/Conversion_Resolver` is the single read-only selector over the
resolved type snapshot. It materializes no types and searches no conversion chains.

- Void participation and condition-purpose requests return no selection, including
  void/condition identity requests. Condition truthiness retains its existing owner.
- Equal canonical IDs select identity for other purposes.
- Implicit boundaries permit only strictly wider representations in the same
  declared integer family with equal signedness. Missing family, narrowing,
  equal-width aliases and signedness changes grant no primitive conversion.
- Other purposes consult the exact purpose/source/destination provider index.
  Explicit casts and text conversion remain distinct permissions. An indexed
  implicit or condition provider does not bypass the preceding rules.

`Conversion_Request` keeps canonical IDs and a checked purpose tag. Selection
uses checked form/primitive tags, with primitive zero meaning absent; callable
zero likewise means no provider target. Identity carries neither payload,
primitive selection carries only the supported integer-widen operation, and
provider selection carries only a positive callable ID. These replace the
prototype enum/nullable-enum forms without using a generic mixed payload.

The migrated Named_Definition uses an empty family string for absence instead of
null; the selector follows that existing model. Width access occurs only after
family eligibility, preserving safe evaluation on the current native target.
Source boundary categories (argument/return/initialization/assignment/condition)
and applying the selected conversion in the body worker remain separate work.

`compiler/tests/body_conversions` covers 196 type/purpose combinations, including
same-width signedness differences, narrowing, bool/float/void, implicit widening,
identity, purpose-specific providers and forbidden provider fallback. Twelve
selection payload combinations check mutual exclusion. PHP/native results agree
with the retained selector. The host oracle uses original classes with only the
snapshot fields read by selection initialized via reflection; it does not prove
runtime provider execution or an end-to-end source pipeline.
