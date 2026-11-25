#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectSymbolIndex;
int_t<std::uint32_t> __latency_fn_project_symbol_index_append_function_import_by_names(shared_p<ProjectSymbolIndex> index, int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> sourceRowId, const string_t& namespaceName, const string_t& aliasName, const string_t& targetQualifiedName);
}
