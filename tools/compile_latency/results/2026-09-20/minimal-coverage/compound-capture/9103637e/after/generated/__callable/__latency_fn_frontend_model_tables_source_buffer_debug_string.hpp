#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModelKernelCounters;
struct SourceBufferRow;
string_t __latency_fn_frontend_model_tables_source_buffer_debug_string(SourceBufferRow row, shared_p<FrontendModelKernelCounters> counters);
}
