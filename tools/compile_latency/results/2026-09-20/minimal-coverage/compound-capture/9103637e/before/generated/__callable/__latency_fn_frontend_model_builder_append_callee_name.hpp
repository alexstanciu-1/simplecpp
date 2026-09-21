#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_callee_name(shared_p<FrontendModel> model, int_t<std::uint32_t> nameRangeId, shared_p<FrontendModelKernelCounters> counters);
}
