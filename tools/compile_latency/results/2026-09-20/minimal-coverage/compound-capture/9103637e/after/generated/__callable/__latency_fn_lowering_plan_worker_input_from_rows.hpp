#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct AnalysisEntryContextRow;
class BackendLoweringWorkerInput;
struct OperationReadiness;
struct ProjectCallableContractRow;
struct ProjectSymbolIndexRow;
struct StorageLifetimeRequestRow;
shared_p<BackendLoweringWorkerInput> __latency_fn_lowering_plan_worker_input_from_rows(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, AnalysisEntryContextRow entry, ProjectCallableContractRow contract, OperationReadiness operation, StorageLifetimeRequestRow storage);
}
