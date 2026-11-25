#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
class ReferenceContractWorkerInput;
void __latency_fn_project_reference_resolution_append_worker_symbol_snapshot(shared_p<ReferenceContractWorkerInput> input, shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow row);
}
