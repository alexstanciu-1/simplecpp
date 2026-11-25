#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SourceUnitFrontendWorkerPayloadInput;
class SourceUnitTable;
struct SourceUnitTableRow;
shared_p<SourceUnitFrontendWorkerPayloadInput> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_input_from_source_units(const shared_p<SourceUnitTable>& sourceUnits, const SourceUnitTableRow& sourceUnit);
}
