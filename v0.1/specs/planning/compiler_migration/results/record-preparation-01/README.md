# Record preparation and lifecycle body extraction
Doc Status: planning

33 PHP/native outcomes and 27 host invariants pass on native build one without
correction. The existing 46-case template-body PHP regression also passes after
the shared lifecycle-role refactor. Final native production bytes were audited.

Selection, source/template/provider fields, exact extents, lifecycle body IDs and
complete batch acceptance are covered. The maximum extent test checks normalization
only. Lifecycle type/signature validation and complete preparation remain unfinished.
Timing retains the first 33-case PHP pass before the host purity assertion correction.
