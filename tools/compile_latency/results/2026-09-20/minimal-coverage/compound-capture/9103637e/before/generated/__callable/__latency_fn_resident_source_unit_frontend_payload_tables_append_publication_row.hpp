#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PartitionReadinessArtifact;
struct ResidentSourceUnitFrontendPayloadTableRow;
void __latency_fn_resident_source_unit_frontend_payload_tables_append_publication_row(shared_p<PartitionReadinessArtifact>& artifact, vector_t<int_t<std::uint32_t>>& publishedFirstBySourceUnitId, ResidentSourceUnitFrontendPayloadTableRow payloadTable);
}
