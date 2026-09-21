#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
class ReferenceContractWorkerInput;
class SourceUnitTable;
vector_t<shared_p<ReferenceContractWorkerInput>> __latency_fn_project_reference_resolution_reference_contract_worker_inputs(shared_p<SourceUnitTable> sourceUnits, shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow entrySymbol, const string_t& entryText);
}
