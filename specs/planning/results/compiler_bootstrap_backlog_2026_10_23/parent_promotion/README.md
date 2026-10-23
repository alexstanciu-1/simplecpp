# Bounded parent-constructor promotion
Doc Status: planning

Base 58b55c26c plus recorded candidate hashes. User approved a converter-owned
leading-parent rule and audited PHP adaptations. Existing constructor Node parsing
owns the rule; no type lookup, source assignment inference, generator or runtime
change. Only generated legacy promotion assignments move behind a complete leading
parent constructor call. Later/conditional calls remain in authored order.

Adapted collected_source_callable, collected_constant and collected_enum. Each
inherits collected_name's collection-only constructor through constructor-free
intermediate classes. Plain collection argument, no child observation, callbacks or
receiver publication. Required syntax links become private promoted properties;
reference annotations remain with the promoted declaration. Positional caller shape
is unchanged. Other constructors were not blanket-adapted.

constructor-parent-approved-01 proves PHP, legacy and both v0.2 modes, required object
identity and base initialization; existing root ordering/default/failure cases pass.
Native success tasks: legacy 17.65 s, ordinary 18.29 s, concrete 17.96 s; failure
proof tasks 21.07/19.18/18.59 s. Added structural-order controls ran separately
(0.22 s), plus actual compiler retained recovery (0.59 s). Shared runner used CLI
fallback because FPM socket creation was unavailable.

Bootstrap: 79.80 s wall, 350156 KiB peak RSS, 320 MiB PHP heap; preparation still
fails. Diagnostic messages are aggregated/deduplicated, so remaining syntax_node
constructors keep that message present; see diagnostic_delta.json rather than
claiming all required-field blockers removed. collected_member_owner and collected_field
still have body-assigned required syntax links. No native compiler rebuild claimed.
The portable no-child-observation contract is documented in constructor_promotion.md;
arbitrary constructor reordering and broader source rollout remain outside this slice.
