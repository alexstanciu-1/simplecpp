#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendBodySummaryArtifact;
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
bool_t __latency_fn_frontend_body_summaries_append_statement(shared_p<FrontendBodySummaryArtifact>& artifact, shared_p<FrontendModel> model, const string_t& sourceText, int_t<std::uint32_t> scopeNodeId, int_t<std::uint32_t> parentStatementNodeId, FrontendNodeRow statementNode, bool_t allowImplicitLocal, shared_p<FrontendModelKernelCounters> counters);
}
