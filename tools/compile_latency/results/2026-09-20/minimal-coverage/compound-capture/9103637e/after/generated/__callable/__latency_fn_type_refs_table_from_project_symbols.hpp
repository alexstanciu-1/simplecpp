#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectSymbolIndex;
struct TypeRefTable;
TypeRefTable __latency_fn_type_refs_table_from_project_symbols(shared_p<ProjectSymbolIndex> symbols);
}
