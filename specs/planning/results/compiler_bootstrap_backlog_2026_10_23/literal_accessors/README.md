# Reuse concrete literal fact accessors
Doc Status: planning

2026-10-25; baseline eafa9f7b7 plus this source-only slice. Mixed static-member
redesign remains proposed, not implemented.

Constant_Expression_Preparation's integer, float, boolean and string branches now
retain the concrete syntax view, perform the same preparation call once, and read
the existing typed fact accessor. Removes four invalid downcasts from the erased
nonpolymorphic prepared_expression base. No retained protocol, language contract,
preparation order or constant value behavior changes.

Proof extracts the four production branches into a portable fixture with a
nonpolymorphic fact base. Checks scalar value copying, type identity and exactly
four total preparation calls. PHP, v0.1 native and both v0.2 output modes pass.
The first fixture run also passed; the final recorded run strengthens value copying
by giving constant destinations different defaults from source facts.

Host source_constants, property_defaults and incremental_preparation pass.
The broader enums suite fails on `S2S reference defaults require canonical scalar
types`. An isolated boot using HEAD's unchanged constants.php reproduces the same
failure at the same fixture loop; this is pre-existing, not a green enum suite.
Captured both errors; no unrelated enum policy/test changes made.

Bootstrap: 200.80 seconds, 610412 KiB peak RSS (596.11 MiB), 578.5 MiB PHP heap.
Same 19 diagnostic categories and same first narrowing blocker. This verifies
specific source branches, not elimination of all narrowing failures. Native compiler
build/run and second-generation proof remain incomplete.
