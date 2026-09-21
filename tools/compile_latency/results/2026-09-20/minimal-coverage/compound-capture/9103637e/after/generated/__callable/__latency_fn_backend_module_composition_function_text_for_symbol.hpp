#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
string_t __latency_fn_backend_module_composition_function_text_for_symbol(shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText, int_t<> depth);
}
