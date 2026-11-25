#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct LineStartRow;
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_line_start(shared_p<FrontendModel> model, LineStartRow row, shared_p<FrontendModelKernelCounters> counters);
}
