#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
bool_t __latency_fn_frontend_model_builder_statement_node_is_return(shared_p<FrontendModel> model, int_t<std::uint32_t> statementNodeId, shared_p<FrontendModelKernelCounters> counters);
}
