#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class ProjectSymbolIndex;
string_t __latency_fn_compiler_export_artifacts_project_cfg_tsv(shared_p<ProjectSymbolIndex> symbols, shared_p<FrontendModel> model);
}
