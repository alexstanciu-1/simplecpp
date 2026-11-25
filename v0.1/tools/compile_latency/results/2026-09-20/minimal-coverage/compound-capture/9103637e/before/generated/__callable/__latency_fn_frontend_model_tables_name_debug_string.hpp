#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModelKernelCounters;
struct FrontendNamePayloadRow;
string_t __latency_fn_frontend_model_tables_name_debug_string(FrontendNamePayloadRow row, shared_p<FrontendModelKernelCounters> counters);
}
