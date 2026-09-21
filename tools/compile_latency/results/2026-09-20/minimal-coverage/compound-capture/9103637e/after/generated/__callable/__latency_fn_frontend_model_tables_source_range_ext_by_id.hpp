#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct SourceRangeExtRow;
SourceRangeExtRow __latency_fn_frontend_model_tables_source_range_ext_by_id(shared_p<FrontendModel> model, int_t<std::uint32_t> sourceRangeId, shared_p<FrontendModelKernelCounters> counters);
}
