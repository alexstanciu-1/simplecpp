#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModelKernelCounters;
struct FrontendNodeRow;
string_t __latency_fn_frontend_model_tables_node_debug_string(FrontendNodeRow row, shared_p<FrontendModelKernelCounters> counters);
}
