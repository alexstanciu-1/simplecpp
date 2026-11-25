#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct FrontendDeclarationPayloadRow;
class FrontendModel;
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
void __latency_fn_project_symbol_index_append_parameter_rows_from_declaration(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow& symbolRow, shared_p<FrontendModel> model, FrontendDeclarationPayloadRow declaration);
}
