#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct SourceUnitFrontendDispatchDecisionRow;
class SourceUnitTable;
SourceUnitFrontendDispatchDecisionRow __latency_fn_source_unit_frontend_scheduler_dispatch_decision_row(shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId, int_t<> workerCount, bool_t physicalPayloadReady, bool_t productionInstallReady, bool_t trialAuthorized, bool_t fullPayloadTrialRequested);
}
