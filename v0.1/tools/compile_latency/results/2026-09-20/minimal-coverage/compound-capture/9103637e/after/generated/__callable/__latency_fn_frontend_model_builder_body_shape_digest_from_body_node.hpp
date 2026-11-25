#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
void __latency_fn_frontend_model_builder_body_shape_digest_from_body_node(shared_p<FrontendModel> model, const string_t& source, int_t<std::uint32_t> bodyNodeId, int_t<std::uint32_t>& bodyLengthOut, int_t<std::uint32_t>& bodyHashOut, int_t<std::uint32_t>& walkRowsOut, shared_p<FrontendModelKernelCounters> counters);
}
