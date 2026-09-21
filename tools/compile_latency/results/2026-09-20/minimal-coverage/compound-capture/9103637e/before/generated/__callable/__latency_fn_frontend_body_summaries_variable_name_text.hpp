#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
string_t __latency_fn_frontend_body_summaries_variable_name_text(shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow variableNode, shared_p<FrontendModelKernelCounters> counters);
}
