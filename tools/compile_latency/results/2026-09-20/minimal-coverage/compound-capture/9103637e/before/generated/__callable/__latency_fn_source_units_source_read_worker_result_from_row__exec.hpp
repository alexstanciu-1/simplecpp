#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct SourceReadResultRow;
class SourceReadWorkerResult;
shared_p<SourceReadWorkerResult> __latency_fn_source_units_source_read_worker_result_from_row__exec(SourceReadResultRow row, string_t& sourceText);
}
