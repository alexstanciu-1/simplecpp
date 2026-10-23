# Optional supplied-base conditional joins
Doc Status: planning

Candidate: d8c4c9736 plus conditional join registry reuse.
Reuse: Selection_Result_Preparation::ownership_join selects a supplied payload base;
Type_Preparation::nullable_of interns its absence contract. No unseen common ancestor,
new conversion graph, emitter protocol or optional-fallback coalescing behavior.

/tmp/nullable-selection-base-01 passes all fifteen tasks: both native modes preserve
identity and absence for ?Derived/Base in both orders and Elvis; inferred results bind
to exact ?Base references. Sibling inference rejects. Existing required-null, invariant
reference, override, runtime checked-conversion and retained signature tests remain.

Direct legacy runtime deduction fails for ?Derived/Base; evidence in
/tmp/nullable-selection-portable-01 and saved stderr. Existing portable branch-type
normalization resolves this: the optional branch explicitly calls a typed nullable
widening helper. /tmp/nullable-selection-portable-02 passes PHP/legacy (18.30 s task),
ordinary v0.2 (14.69 s) and concrete (16.57 s). No generator/runtime fix implied.

Bootstrap 1:14.31 GNU wall, 342132 KiB RSS, 312 MiB PHP heap; first syntax_node blocker
and diagnostic set unchanged. No native compiler rebuild. Optional-fallback coalescing
remains a separate open consumer.
