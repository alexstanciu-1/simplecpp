#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class ProjectSymbolIndex;
bool_t __latency_fn_control_flow_transfers_project_has_blocking_diagnostic(shared_p<ProjectSymbolIndex> symbols, shared_p<FrontendModel> model);
}
