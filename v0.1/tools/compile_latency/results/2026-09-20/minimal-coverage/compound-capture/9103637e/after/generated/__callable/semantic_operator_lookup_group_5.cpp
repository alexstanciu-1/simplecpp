#include <scpp/lang/php.hpp>
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_none_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_has_lowering_adapter.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_lowering_adapter_id_for_operator_id.hpp"
namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
bool_t __latency_fn_semantic_operator_lookup_has_lowering_adapter(int_t<std::uint16_t> operatorId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::has_lowering_adapter", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[57]);
	return bool_t(php::not_identical(cast<int_t<>>(__latency_fn_semantic_operator_lookup_lowering_adapter_id_for_operator_id(cast<int_t<std::uint16_t>>(operatorId))), cast<int_t<>>(__latency_fn_operation_readiness_lowering_adapter_none_id())));
}

}
