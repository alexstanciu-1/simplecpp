#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectReferenceResolution;
struct ProjectReferenceResolutionRow;
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
ProjectReferenceResolutionRow __latency_fn_project_reference_resolution_append_direct_call_from_call_expression_source_range(shared_p<ProjectReferenceResolution> artifact, shared_p<ProjectSymbolIndex> symbols, const string_t& sourceText, ProjectSymbolIndexRow fromSymbol, int_t<std::uint32_t> callExpressionNodeId, int_t<std::uint32_t> calleeStartOffset, int_t<std::uint32_t> calleeLength, int_t<std::uint32_t> actualArgCount);
}
