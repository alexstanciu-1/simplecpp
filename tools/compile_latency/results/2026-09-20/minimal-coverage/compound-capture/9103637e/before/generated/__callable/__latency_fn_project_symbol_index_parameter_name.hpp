#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class ProjectSymbolIndex;
struct ProjectSymbolParameterRow;
string_t __latency_fn_project_symbol_index_parameter_name(shared_p<ProjectSymbolIndex> index, shared_p<FrontendModel> model, const string_t& sourceText, ProjectSymbolParameterRow row);
}
