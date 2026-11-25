#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class ProjectSymbolIndex;
class SourceUnitTable;
struct SourceUnitTableRow;
void __latency_fn_project_symbol_index_append_from_model(shared_p<ProjectSymbolIndex> index, shared_p<SourceUnitTable> sourceUnits, SourceUnitTableRow sourceUnit, const string_t& sourceText, shared_p<FrontendModel> model);
}
