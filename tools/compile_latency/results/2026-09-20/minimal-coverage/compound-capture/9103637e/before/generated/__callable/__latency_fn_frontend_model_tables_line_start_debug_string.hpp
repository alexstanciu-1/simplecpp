#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModelKernelCounters;
struct LineStartRow;
string_t __latency_fn_frontend_model_tables_line_start_debug_string(LineStartRow row, shared_p<FrontendModelKernelCounters> counters);
}
