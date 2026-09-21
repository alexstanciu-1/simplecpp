#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
struct FrontendNodeRow;
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
int_t<> __latency_fn_llvm_text_from_plan_parameter_arg_index_for_variable_node(shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow variableNode, int_t<std::uint32_t>& typeRefIdOut);
}
