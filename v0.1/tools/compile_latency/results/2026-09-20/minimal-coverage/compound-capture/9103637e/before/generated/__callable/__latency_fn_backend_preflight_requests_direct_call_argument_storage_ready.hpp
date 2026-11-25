#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectCallableContractRow;
struct ProjectReferenceActualArgumentRow;
class ProjectSymbolIndex;
bool_t __latency_fn_backend_preflight_requests_direct_call_argument_storage_ready(ProjectCallableContractRow contract, shared_p<ProjectSymbolIndex> symbols, const vector_t<ProjectReferenceActualArgumentRow>& actualArguments);
}
