#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class ProjectFrontendModel;
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
struct ResidentFunctionBodySnapshotRow;
ResidentFunctionBodySnapshotRow __latency_fn_resident_function_body_ownership_row_from_symbol(shared_p<ProjectFrontendModel> project, shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow symbol, shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, vector_t<int_t<std::uint32_t>>& modelIndexIds, vector_t<int_t<std::uint32_t>>& frontendStateIds, vector_t<int_t<std::uint32_t>>& frontendSnapshotIds);
}
