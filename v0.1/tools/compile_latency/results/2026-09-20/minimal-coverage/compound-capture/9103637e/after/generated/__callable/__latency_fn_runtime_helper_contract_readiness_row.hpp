#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class RuntimeHelperContractReadinessRow;
shared_p<RuntimeHelperContractReadinessRow> __latency_fn_runtime_helper_contract_readiness_row(int_t<std::uint32_t> rowId, int_t<std::uint16_t> helperId, int_t<std::uint16_t> helperKindId, const string_t& helperKey, const string_t& requiredBy, bool_t requiresStringStorage);
}
