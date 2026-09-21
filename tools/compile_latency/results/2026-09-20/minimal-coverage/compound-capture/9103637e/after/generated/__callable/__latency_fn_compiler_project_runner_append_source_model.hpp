#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct CompilerProjectRunRow;
class FrontendModel;
class ProjectSymbolIndex;
class SourceUnitTable;
struct SourceUnitTableRow;
shared_p<FrontendModel> __latency_fn_compiler_project_runner_append_source_model(CompilerProjectRunRow& row, shared_p<ProjectSymbolIndex>& symbols, shared_p<SourceUnitTable> sourceUnits, SourceUnitTableRow sourceUnit);
}
