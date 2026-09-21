#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_name(shared_p<FrontendModel> model, int_t<std::uint32_t> nameRangeId, int_t<std::uint16_t> roleId, shared_p<FrontendModelKernelCounters> counters);
}
