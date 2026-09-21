#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
int_t<std::uint32_t> __latency_fn_frontend_model_builder_source_range_from_offsets(shared_p<FrontendModel> model, int_t<> startOffset, int_t<> endOffset, shared_p<FrontendModelKernelCounters> counters);
}
