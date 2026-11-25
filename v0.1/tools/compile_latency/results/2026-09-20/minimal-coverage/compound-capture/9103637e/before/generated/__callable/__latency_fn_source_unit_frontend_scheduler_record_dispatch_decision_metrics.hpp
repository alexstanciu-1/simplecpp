#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct SourceUnitFrontendDispatchDecisionRow;
void __latency_fn_source_unit_frontend_scheduler_record_dispatch_decision_metrics(shared_p<CompilerProjectRunReport>& report, SourceUnitFrontendDispatchDecisionRow row);
}
