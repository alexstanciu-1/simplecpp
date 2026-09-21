#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SourceUnitFrontendWorkerPayloadInput;
class SourceUnitTable;
struct SourceUnitTableRow;
shared_p<SourceUnitTable> __latency_fn_resident_source_unit_symbol_states_source_unit_table_for_worker_input(shared_p<SourceUnitFrontendWorkerPayloadInput> input, SourceUnitTableRow sourceUnit, const string_t& sourceText);
}
