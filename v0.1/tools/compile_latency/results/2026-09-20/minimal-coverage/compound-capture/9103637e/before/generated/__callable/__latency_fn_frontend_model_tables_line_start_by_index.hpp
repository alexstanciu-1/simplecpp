#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct LineStartRow;
LineStartRow __latency_fn_frontend_model_tables_line_start_by_index(shared_p<FrontendModel> model, int_t<std::uint32_t> lineStartIndex, shared_p<FrontendModelKernelCounters> counters);
}
