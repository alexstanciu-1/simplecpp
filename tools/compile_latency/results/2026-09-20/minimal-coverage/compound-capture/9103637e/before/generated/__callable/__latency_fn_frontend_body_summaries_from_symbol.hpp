#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendBodySummaryArtifact;
class FrontendModel;
class FrontendModelKernelCounters;
struct ProjectSymbolIndexRow;
shared_p<FrontendBodySummaryArtifact> __latency_fn_frontend_body_summaries_from_symbol(shared_p<FrontendModel> model, const string_t& sourceText, ProjectSymbolIndexRow symbol, shared_p<FrontendModelKernelCounters> counters);
}
