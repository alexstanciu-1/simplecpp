#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectReferenceResolution;
struct ProjectReferenceResolutionRow;
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
ProjectReferenceResolutionRow __latency_fn_project_reference_resolution_append_direct_call_from_call_expression_fields_with_first_argument_type(shared_p<ProjectReferenceResolution> artifact, shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow fromSymbol, int_t<std::uint32_t> callExpressionNodeId, const string_t& calleeName, int_t<std::uint32_t> actualArgCount, int_t<std::uint32_t> actualFirstArgumentTypeRefId, int_t<std::uint16_t> actualFirstArgumentStatusId);
}
