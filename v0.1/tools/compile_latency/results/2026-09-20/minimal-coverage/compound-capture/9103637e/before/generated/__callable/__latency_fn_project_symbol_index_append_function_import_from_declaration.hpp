#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct FrontendDeclarationPayloadRow;
class FrontendModel;
struct FrontendNodeRow;
class ProjectSymbolIndex;
struct SourceUnitTableRow;
int_t<std::uint32_t> __latency_fn_project_symbol_index_append_function_import_from_declaration(shared_p<ProjectSymbolIndex> index, SourceUnitTableRow sourceUnit, const string_t& sourceText, shared_p<FrontendModel> model, FrontendNodeRow declarationNode, FrontendDeclarationPayloadRow declaration);
}
