# Nullable local container annotations
Doc Status: planning

Candidate: 0156af69a plus converter local nullable annotation parsing.
Reuse: strip one explicit absence layer, pass its payload to existing Container_Type
through containerAnnotation. No symbol resolution, capabilities or target semantics
in the converter. Existing generic container syntax only; arbitrary user templates,
nested nullable layers and direct nullable literals remain outside this slice.

/tmp/nullable-local-annotations-01 passes PHP and v0.1 native (portable task 18.45 s),
ordinary v0.2 (18.17 s) and concrete prelowering (23.24 s). Local present/absent vectors
and nested hash<vector<int>> extraction preserve existing copy/identity assertions.
/tmp/nullable-local-contracts-01 passes both converter targets, including malformed,
repeated-nullable, wrong-arity and forbidden-payload rejection and prior declaration
site checks. Converter fingerprints already include converter.php.

Bootstrap GNU wall 1:14.20, peak RSS 342344 KiB, PHP heap 312 MiB. First blocker remains
syntax_node initialization; diagnostic set unchanged. No native compiler rebuild.
