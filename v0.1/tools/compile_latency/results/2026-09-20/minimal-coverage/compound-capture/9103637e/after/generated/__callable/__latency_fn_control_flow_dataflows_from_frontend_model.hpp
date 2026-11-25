#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ControlFlowDataflowArtifact;
class FrontendModel;
struct ProjectSymbolIndexRow;
shared_p<ControlFlowDataflowArtifact> __latency_fn_control_flow_dataflows_from_frontend_model(ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText);
}
