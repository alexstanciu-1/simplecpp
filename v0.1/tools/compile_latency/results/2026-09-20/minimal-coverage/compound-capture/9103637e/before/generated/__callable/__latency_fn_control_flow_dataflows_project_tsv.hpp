#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class ProjectSymbolIndex;
string_t __latency_fn_control_flow_dataflows_project_tsv(shared_p<ProjectSymbolIndex> symbols, shared_p<FrontendModel> model, const string_t& sourceText);
}
