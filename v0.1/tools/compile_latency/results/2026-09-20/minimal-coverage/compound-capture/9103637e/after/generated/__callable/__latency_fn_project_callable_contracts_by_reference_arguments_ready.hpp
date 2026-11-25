#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectReferenceActualArgumentRow;
struct ProjectSymbolParameterRow;
bool_t __latency_fn_project_callable_contracts_by_reference_arguments_ready(const vector_t<ProjectSymbolParameterRow>& expectedParameters, const vector_t<ProjectReferenceActualArgumentRow>& actualArguments, int_t<std::uint32_t> actualArgCount);
}
