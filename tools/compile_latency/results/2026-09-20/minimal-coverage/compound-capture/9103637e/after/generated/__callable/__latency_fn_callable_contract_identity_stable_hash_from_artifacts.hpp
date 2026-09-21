#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectCallableContractRow;
class ProjectReferenceResolution;
class ProjectSymbolIndex;
int_t<std::uint64_t> __latency_fn_callable_contract_identity_stable_hash_from_artifacts(ProjectCallableContractRow row, shared_p<ProjectReferenceResolution> references, shared_p<ProjectSymbolIndex> symbols);
}
