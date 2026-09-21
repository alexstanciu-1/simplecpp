#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct SourceRangeRow;
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_source_range(shared_p<FrontendModel> model, SourceRangeRow row, shared_p<FrontendModelKernelCounters> counters);
}
