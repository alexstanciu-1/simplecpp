#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectSymbolFunctionImportRow;
class ProjectSymbolIndex;
class ReferenceContractWorkerInput;
void __latency_fn_project_reference_resolution_append_worker_function_import_snapshot(shared_p<ReferenceContractWorkerInput> input, shared_p<ProjectSymbolIndex> symbols, ProjectSymbolFunctionImportRow row);
}
