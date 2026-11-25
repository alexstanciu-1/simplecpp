#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ControlFlowDataflowArtifact;
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
struct FrontendStatementPayloadRow;
void __latency_fn_control_flow_dataflows_append_if_statement(shared_p<ControlFlowDataflowArtifact>& artifact, shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow ifNode, FrontendStatementPayloadRow ifStatement, shared_p<FrontendModelKernelCounters> counters);
}
