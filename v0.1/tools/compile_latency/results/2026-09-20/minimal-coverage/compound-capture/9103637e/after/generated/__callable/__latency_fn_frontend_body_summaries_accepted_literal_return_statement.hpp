#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendBodySummaryArtifact;
struct FrontendLiteralPayloadRow;
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
struct ProjectSymbolIndexRow;
FrontendNodeRow __latency_fn_frontend_body_summaries_accepted_literal_return_statement(shared_p<FrontendBodySummaryArtifact> artifact, shared_p<FrontendModel> model, ProjectSymbolIndexRow symbol, FrontendLiteralPayloadRow& literal, shared_p<FrontendModelKernelCounters> counters);
}
