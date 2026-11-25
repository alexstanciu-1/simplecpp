#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct FrontendDeclarationPayloadRow;
class FrontendModel;
struct FrontendNodeRow;
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
class SourceUnitTable;
struct SourceUnitTableRow;
ProjectSymbolIndexRow __latency_fn_project_symbol_index_row_from_declaration(shared_p<ProjectSymbolIndex> index, shared_p<SourceUnitTable> sourceUnits, SourceUnitTableRow sourceUnit, const string_t& sourceText, shared_p<FrontendModel> model, FrontendNodeRow declarationNode, FrontendDeclarationPayloadRow declaration);
}
