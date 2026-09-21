#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectCallableContractRow;
class ProjectReferenceResolution;
class ProjectSymbolIndex;
string_t __latency_fn_project_callable_contracts_from_symbol_key(ProjectCallableContractRow row, shared_p<ProjectReferenceResolution> references, shared_p<ProjectSymbolIndex> symbols);
}
