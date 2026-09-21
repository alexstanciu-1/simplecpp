#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ControlFlowGraphArtifact;
class FrontendModel;
struct ProjectSymbolIndexRow;
shared_p<ControlFlowGraphArtifact> __latency_fn_control_flow_graphs_from_frontend_model(ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model);
}
