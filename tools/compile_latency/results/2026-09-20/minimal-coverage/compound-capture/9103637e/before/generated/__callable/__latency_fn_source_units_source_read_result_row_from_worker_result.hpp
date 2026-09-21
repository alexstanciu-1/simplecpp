#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct SourceReadResultRow;
class SourceReadWorkerResult;
SourceReadResultRow __latency_fn_source_units_source_read_result_row_from_worker_result(shared_p<SourceReadWorkerResult> result);
}
