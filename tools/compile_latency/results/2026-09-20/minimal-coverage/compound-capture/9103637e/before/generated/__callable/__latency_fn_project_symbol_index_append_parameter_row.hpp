#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
struct FrontendNodeRow;
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
int_t<std::uint32_t> __latency_fn_project_symbol_index_append_parameter_row(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow& symbolRow, shared_p<FrontendModel> model, FrontendNodeRow parameterNode, int_t<std::uint32_t> typeRefId, int_t<std::uint16_t> position);
}
