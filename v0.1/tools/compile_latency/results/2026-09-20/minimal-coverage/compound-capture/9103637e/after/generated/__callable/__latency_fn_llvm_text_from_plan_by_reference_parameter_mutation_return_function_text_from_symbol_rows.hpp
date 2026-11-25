#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
string_t __latency_fn_llvm_text_from_plan_by_reference_parameter_mutation_return_function_text_from_symbol_rows(const string_t& llvmFunctionName, shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText);
}
