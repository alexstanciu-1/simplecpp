#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModelKernelCounters;
struct SourceRangeRow;
string_t __latency_fn_frontend_model_tables_source_range_debug_string(SourceRangeRow row, shared_p<FrontendModelKernelCounters> counters);
}
