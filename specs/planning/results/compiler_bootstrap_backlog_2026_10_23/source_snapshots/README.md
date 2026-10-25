# Snapshot source recognition — 2026-10-25
Doc Status: planning

Call_Snapshot_Preparation recognizes scalar literals and variable references through
existing polymorphic syntax nodes instead of narrowing nonpolymorphic prepared facts.
Prepared type, conversion, place, purity and evaluation-order checks remain unchanged.
The integer/float/boolean/string expression producers own the corresponding scalar
literal facts. Null is already excluded by the scalar-family guard. Interpolation
text is not an expression_node; default-preparation facts are not explicit arguments.
Variable reference syntax includes self references. No retained protocol is added.

The extracted production helper passes PHP/v0.1/both v0.2 native proofs, including
all four literal shapes and rejection of other expressions. All nine focused
call_snapshot_borrow tasks pass: facts, allocations, behavior and argument/conversion
failure ordering across ordinary and concrete output modes.

Completed owner-instrumented bootstrap: 1:28.80 / 633492 KiB RSS / 600.5 MiB PHP heap.
Timings include deduplicated failure-owner logging overhead. Snapshot failure clears.
Two categories remain in three owners: json_read required-null reference binding,
CPP_Data_Construction::generate and CPP_Default_Constructors::decision narrowing
nonpolymorphic construction facts. No native compiler or second generation built yet.

Next review: correct the JSON nullable output-reference binding contract; design
node-owned typed construction-fact publication/accessors using existing static-member
facts precedent. The latter crosses retained fact publication and consumption owners
and needs discussion before implementation. No dummy polymorphism or language change
has been introduced to accommodate the converter.
