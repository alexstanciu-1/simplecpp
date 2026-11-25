#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
struct FrontendNodeRow;
struct ProjectSymbolIndexRow;
FrontendNodeRow __latency_fn_project_reference_resolution_call_expression_node_from_symbol(shared_p<FrontendModel> model, ProjectSymbolIndexRow fromSymbol);
}
