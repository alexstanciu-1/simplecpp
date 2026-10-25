# Typed foreach source lists — 2026-10-25
Doc Status: planning

Under broad user authorization to fix portable PHP where existing constructs suffice,
11 bare string-list foreach expressions in eight files now have explicitly annotated
vector<string> locals. List evaluation stays immediately before the loop; order and
values are unchanged, including dynamic Ninja dependency arguments.

Production list declarations extracted by tests/portability/typed_foreach_lists.py
pass PHP, v0.1 native and both v0.2 native output modes with exact ordered output.
No converter, runtime or semantic changes. This is not a native compiler build.

Bootstrap: 1:40.77 wall, 625804 KiB RSS, 592.5 MiB PHP heap; preparation fails.
Diagnostic categories 16 -> 15; missing concrete construction target clears.
First remaining diagnostic: required object boundary rejects null (previous owner
audit points to the portability JSON binding). Other source fixes continue independently.
