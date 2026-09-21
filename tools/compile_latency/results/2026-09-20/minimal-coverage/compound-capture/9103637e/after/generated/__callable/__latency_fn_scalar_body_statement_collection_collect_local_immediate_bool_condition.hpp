#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
bool_t __latency_fn_scalar_body_statement_collection_collect_local_immediate_bool_condition(shared_p<FrontendModel> model, const string_t& sourceText, shared_p<FrontendModelKernelCounters> counters, FrontendNodeRow conditionNode, const vector_t<string_t>& localNames, const vector_t<int_t<std::uint32_t>>& localSourceRowIds, const vector_t<int_t<std::uint32_t>>& localTypeRefIds, int_t<std::uint32_t>& conditionTypeRefId, int_t<std::uint32_t>& conditionLocalSourceRowId, int_t<std::uint32_t>& conditionProviderTypeRefId, int_t<std::uint16_t>& conditionFeatureId, int_t<std::uint16_t>& conditionLocalOperationId, int_t<std::int32_t>& conditionValue);
}
