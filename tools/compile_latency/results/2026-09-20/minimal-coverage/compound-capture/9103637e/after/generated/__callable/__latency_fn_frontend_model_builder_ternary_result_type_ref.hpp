#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
int_t<std::uint32_t> __latency_fn_frontend_model_builder_ternary_result_type_ref(shared_p<FrontendModel> model, int_t<std::uint32_t> thenNodeId, int_t<std::uint32_t> elseNodeId, int_t<std::uint32_t> fallbackTypeRefId, shared_p<FrontendModelKernelCounters> counters);
}
