#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
struct SourceRangeRow;
SourceRangeRow __latency_fn_frontend_body_summaries_variable_name_range(shared_p<FrontendModel> model, FrontendNodeRow variableNode, shared_p<FrontendModelKernelCounters> counters);
}
