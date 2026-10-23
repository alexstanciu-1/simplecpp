# Nullable object and key consumer checkpoint
Doc Status: supporting

Optional class/interface operands now enter the existing presence-aware object-cast
and instanceof protocol. Semantic source identity stays optional; runtime behavior
and both emission paths are reused. Indexing_Preparation owns one checked key-value
conversion for reads, writes, nested paths, probes and removal. Payload key-domain
validation remains separate from conversion; nullable stored keys are not admitted.

PHP/legacy and both v0.2 cast modes pass in /tmp/nullable-object-casts-01, including
empty/mismatched failure and identity preservation. All 33 native object-type
regression programs pass in /tmp/nullable-object-casts-native-01. Six tasks pass in
/tmp/nullable-keys-01: positive and absent-key executions in both modes plus existing
indexing/key-contract preparation regressions. Absence fails before the write value
expression; present object keys safely upcast without losing identity.

Bootstrap conversion: 4.76 s / 195,184 KiB RSS. Preparation: 200.43 s wall time /
486,208 KiB RSS (474.8 MiB) / 465 MiB PHP heap. No fatal/OOM or C++ emission.
The nullable object-operation and indexed-key diagnostics disappear; none are added.
Class-self retention is now first, still separately gated. This is generated-program
validation and PHP-hosted preparation, not a native compiler rebuild. Full NULL-01
initialization/consumer work and broader source migration remain outstanding.

Reuse review for the next initialization slice: Constructor_Preparation already
requires set: for fields without valid defaults. Object_Construction_Preparation's
supports_default currently admits empty required nominal handles; that source-value
policy must be distinguished from native backing storage and local predeclarations.
Body_Preparation and Completion_Preparation remain the local-flow owners. No new
constructor-body assignment inference is needed for the accepted direct-init contract.
