#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
class ScalarBodyBackendCollection;
class ScalarLoopBodyStatementSequence;
shared_p<ScalarLoopBodyStatementSequence> __latency_fn_scalar_body_statement_collection_append_loop_body_statement_sequence(shared_p<FrontendModel> model, const string_t& sourceText, shared_p<FrontendModelKernelCounters> counters, int_t<std::uint32_t> bodyFirstNodeId, int_t<std::uint32_t> bodyLastNodeId, shared_p<ScalarBodyBackendCollection>& body);
}
