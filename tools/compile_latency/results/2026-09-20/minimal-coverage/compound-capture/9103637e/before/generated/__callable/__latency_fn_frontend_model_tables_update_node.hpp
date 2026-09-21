#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
bool_t __latency_fn_frontend_model_tables_update_node(shared_p<FrontendModel> model, FrontendNodeRow row, shared_p<FrontendModelKernelCounters> counters);
}
