#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class ProjectSymbolIndex;
string_t __latency_fn_control_flow_graphs_project_tsv(shared_p<ProjectSymbolIndex> symbols, shared_p<FrontendModel> model);
}
