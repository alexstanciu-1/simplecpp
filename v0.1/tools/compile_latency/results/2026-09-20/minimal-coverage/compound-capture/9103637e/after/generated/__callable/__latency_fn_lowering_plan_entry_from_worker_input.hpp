#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct AnalysisEntryContextRow;
class BackendLoweringWorkerInput;
AnalysisEntryContextRow __latency_fn_lowering_plan_entry_from_worker_input(shared_p<BackendLoweringWorkerInput> input);
}
