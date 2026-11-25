#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PartitionReadinessArtifact;
struct SourceUnitTableRow;
void __latency_fn_resident_source_unit_symbol_states_append_symbol_fact_publication_row(shared_p<PartitionReadinessArtifact>& artifact, SourceUnitTableRow sourceUnit, int_t<std::uint32_t> ownerRunId, const vector_t<int_t<std::uint32_t>>& firstSymbolIds, const vector_t<int_t<std::uint32_t>>& symbolCounts);
}
