#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendBodySummaryArtifact;
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
int_t<std::uint32_t> __latency_fn_frontend_body_summaries_local_id_for_variable_node(shared_p<FrontendBodySummaryArtifact>& artifact, shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow variableNode, shared_p<FrontendModelKernelCounters> counters);
}
