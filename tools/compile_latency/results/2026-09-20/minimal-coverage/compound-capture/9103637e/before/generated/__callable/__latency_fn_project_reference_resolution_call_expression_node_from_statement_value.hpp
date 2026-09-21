#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
FrontendNodeRow __latency_fn_project_reference_resolution_call_expression_node_from_statement_value(shared_p<FrontendModel> model, FrontendNodeRow statementNode, shared_p<FrontendModelKernelCounters> counters);
}
