#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class ReferenceContractWorkerInput;
void __latency_fn_project_reference_resolution_record_reference_contract_worker_handoff_metrics(shared_p<CompilerProjectRunReport>& report, const vector_t<shared_p<ReferenceContractWorkerInput>>& inputs, const vector_t<int_t<>>& workerDescriptors, int_t<std::uint32_t> snapshotSymbolRows, int_t<std::uint32_t> coordinatorSemanticHash, bool_t matches);
}
