#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
struct FrontendNodeRow;
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
string_t __latency_fn_llvm_text_from_plan_parameter_return_operand_text(shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow node, int_t<std::uint32_t>& typeRefIdOut);
}
