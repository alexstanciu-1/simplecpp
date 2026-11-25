#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
class PhsParserState;
void __latency_fn_frontend_model_builder_append_implicit_return_after_statement_list(shared_p<PhsParserState> state, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> firstStatementNodeId, int_t<std::uint32_t> expectedReturnTypeRefId, int_t<> implicitReturnOffset, shared_p<FrontendModelKernelCounters> counters);
}
