#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct TokenRow;
int_t<std::uint32_t> __latency_fn_frontend_model_builder_source_range_from_token(shared_p<FrontendModel> model, TokenRow token, shared_p<FrontendModelKernelCounters> counters);
}
