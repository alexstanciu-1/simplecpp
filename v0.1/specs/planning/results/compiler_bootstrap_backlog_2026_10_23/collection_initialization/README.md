# Collection constructor initialization checkpoint
Doc Status: planning

Candidate: ee832ae99 plus the five constructor-promotion edits recorded in the execution ledger.
Reuse: existing PHP promoted properties and converter set-parameter lowering. Inputs
are already available at construction. No parent-call ordering or staged-field change.

Six PHP checks pass: parse_collection, collected_roles, collected_query_dispatch,
trait_model, trait_instances and traits. Logs: /tmp/collection-init-checks-01.

Bootstrap: /tmp/compiler-bootstrap-collection-init-01. Conversion succeeds; preparation
stops first at syntax_node initialization, previously collection. GNU wall 73.72 s,
342324 KiB peak RSS (334.3 MiB), 312 MiB peak PHP heap. No fatal/OOM or native
compiler rebuild. Extended extracted-production parsed_file and trait_member_instance proof passes
PHP, v0.1 native (21.97 s), ordinary v0.2 (24.68 s), and concrete prelowering
(26.20 s) in /tmp/collection-init-portable-01. It checks input identity, source/root
queries, pending completion, aliased scope membership, and separate origin/working
identities. Supporting scope/body/collection declarations are fixture stubs; this
is not a native parser/collector or whole-compiler execution proof.
