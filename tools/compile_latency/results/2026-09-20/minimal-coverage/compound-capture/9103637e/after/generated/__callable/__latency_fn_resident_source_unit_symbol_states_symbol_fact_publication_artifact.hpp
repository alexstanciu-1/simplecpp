#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PartitionReadinessArtifact;
class SourceUnitTable;
shared_p<PartitionReadinessArtifact> __latency_fn_resident_source_unit_symbol_states_symbol_fact_publication_artifact(shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId, const vector_t<int_t<std::uint32_t>>& firstSymbolIds, const vector_t<int_t<std::uint32_t>>& symbolCounts, bool_t simulatedOrder);
}
