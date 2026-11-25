#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectSymbolFunctionImportRow;
class ProjectSymbolIndex;
struct SourceUnitTableRow;
int_t<std::uint32_t> __latency_fn_project_symbol_index_append_rebased_function_import_from_index(shared_p<ProjectSymbolIndex> target, shared_p<ProjectSymbolIndex> source, SourceUnitTableRow sourceUnit, ProjectSymbolFunctionImportRow sourceImport);
}
