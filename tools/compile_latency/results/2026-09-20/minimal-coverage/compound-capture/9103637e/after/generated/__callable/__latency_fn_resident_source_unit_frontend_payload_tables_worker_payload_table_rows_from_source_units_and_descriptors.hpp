#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ResidentSourceUnitFrontendPayloadTableRow;
class SourceUnitTable;
vector_t<ResidentSourceUnitFrontendPayloadTableRow> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_table_rows_from_source_units_and_descriptors(shared_p<SourceUnitTable> sourceUnits, const vector_t<int_t<>>& descriptors, int_t<std::uint32_t> ownerRunId);
}
