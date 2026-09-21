#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SourceUnitFrontendWorkerPayloadInput;
struct SourceUnitTableRow;
shared_p<SourceUnitFrontendWorkerPayloadInput> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_input(const SourceUnitTableRow& sourceUnit, const string_t& sourceText);
}
