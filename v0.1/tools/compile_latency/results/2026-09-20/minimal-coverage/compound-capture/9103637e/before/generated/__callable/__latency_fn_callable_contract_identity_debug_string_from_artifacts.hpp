#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectCallableContractRow;
class ProjectReferenceResolution;
class ProjectSymbolIndex;
string_t __latency_fn_callable_contract_identity_debug_string_from_artifacts(ProjectCallableContractRow row, shared_p<ProjectReferenceResolution> references, shared_p<ProjectSymbolIndex> symbols);
}
