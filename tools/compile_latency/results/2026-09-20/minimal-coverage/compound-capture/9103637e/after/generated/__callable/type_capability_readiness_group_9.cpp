#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/RuntimeAbiBridgeArtifact.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__types/TypeTraitRow.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_string_comparison_runtime_helper_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_comparison_operator_feature_has_lowering_adapter.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_build.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_ref.hpp"
#include "__callable/__latency_fn_string_runtime_abi_comparison_source_slice_ready.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_backend_numeric_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_non_numeric_type_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_runtime_string_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_unknown_type_trait_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_comparison_feature_blocked_reason_from_trait.hpp"
#include "__callable/__latency_fn_type_traits_comparability_scalar_value_id.hpp"
#include "__callable/__latency_fn_type_traits_status_ready_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_logical_operator_feature_has_lowering_adapter.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_backend_numeric_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_non_numeric_type_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_unknown_type_trait_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_logical_feature_blocked_reason_from_trait.hpp"
#include "__callable/__latency_fn_type_traits_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_truthiness_bool_value_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_numeric_feature_blocked_reason_from_trait.hpp"
#include "__callable/__latency_fn_type_capability_readiness_numeric_feature_supported_by_trait.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_numeric_operator_feature_has_lowering_adapter.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_backend_numeric_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_non_numeric_type_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_numeric_operator_not_in_trait_mask_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_unknown_type_trait_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_unsupported_numeric_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_numeric_feature_blocked_reason_from_trait.hpp"
#include "__callable/__latency_fn_type_capability_readiness_numeric_operator_bit_for_feature.hpp"
#include "__callable/__latency_fn_type_traits_declares_numeric_operator.hpp"
#include "__callable/__latency_fn_type_traits_numeric_blocked_reason_backend_numeric_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_blocked_reason_not_numeric_type_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_mask_none_id.hpp"
#include "__callable/__latency_fn_type_traits_status_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_divide_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_minus_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_modulo_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_multiply_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_plus_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_numeric_operator_bit_for_feature.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_divide_bit_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_mask_none_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_minus_bit_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_modulo_bit_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_multiply_bit_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_plus_bit_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_storage_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_value_storage_blocked_reason_from_trait.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_frontend_statement_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_type_ref_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_row_from_type_ref_id.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_comparison_feature_blocked_reason_from_trait(TypeTraitRow trait, int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::comparison_feature_blocked_reason_from_trait", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[118]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(trait->status_id), cast<int_t<>>(__latency_fn_type_traits_status_ready_id()))))) {
		return __latency_fn_type_capability_readiness_blocked_reason_unknown_type_trait_id();
	}
	shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_ref(featureId, trait->type_ref_id);
	if (static_cast<bool>(((cast<int_t<>>(operatorRow->operator_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(operatorRow->contract_id), cast<int_t<>>(__latency_fn_operation_readiness_contract_string_comparison_runtime_helper_id()))))) {
		if (static_cast<bool>(php::condition_truthy(__latency_fn_string_runtime_abi_comparison_source_slice_ready(__latency_fn_runtime_abi_bridge_build())))) {
			return __latency_fn_type_capability_readiness_blocked_reason_none_id();
		}
		return __latency_fn_type_capability_readiness_blocked_reason_runtime_string_abi_not_ready_id();
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(trait->comparability_id), cast<int_t<>>(__latency_fn_type_traits_comparability_scalar_value_id()))))) {
		return __latency_fn_type_capability_readiness_blocked_reason_non_numeric_type_id();
	}
	if (static_cast<bool>((!__latency_fn_primitive_abi_adapter_matrix_comparison_operator_feature_has_lowering_adapter(trait->type_ref_id, featureId)))) {
		return __latency_fn_type_capability_readiness_blocked_reason_backend_numeric_abi_not_ready_id();
	}
	return __latency_fn_type_capability_readiness_blocked_reason_none_id();
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_logical_feature_blocked_reason_from_trait(TypeTraitRow trait, int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::logical_feature_blocked_reason_from_trait", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[119]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(trait->status_id), cast<int_t<>>(__latency_fn_type_traits_status_ready_id()))))) {
		return __latency_fn_type_capability_readiness_blocked_reason_unknown_type_trait_id();
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(trait->truthiness_id), cast<int_t<>>(__latency_fn_type_traits_truthiness_bool_value_id()))))) {
		return __latency_fn_type_capability_readiness_blocked_reason_non_numeric_type_id();
	}
	if (static_cast<bool>((!__latency_fn_primitive_abi_adapter_matrix_logical_operator_feature_has_lowering_adapter(trait->type_ref_id, featureId)))) {
		return __latency_fn_type_capability_readiness_blocked_reason_backend_numeric_abi_not_ready_id();
	}
	return __latency_fn_type_capability_readiness_blocked_reason_none_id();
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
bool_t __latency_fn_type_capability_readiness_numeric_feature_supported_by_trait(TypeTraitRow trait, int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::numeric_feature_supported_by_trait", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[120]);
	return bool_t(php::identical(cast<int_t<>>(__latency_fn_type_capability_readiness_numeric_feature_blocked_reason_from_trait(trait, cast<int_t<std::uint16_t>>(featureId))), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_none_id())));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_numeric_feature_blocked_reason_from_trait(TypeTraitRow trait, int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::numeric_feature_blocked_reason_from_trait", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[121]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(trait->status_id), cast<int_t<>>(__latency_fn_type_traits_status_ready_id()))))) {
		return __latency_fn_type_capability_readiness_blocked_reason_unknown_type_trait_id();
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(trait->numeric_status_id), cast<int_t<>>(__latency_fn_type_traits_status_ready_id()))))) {
		if (static_cast<bool>(php::identical(cast<int_t<>>(trait->numeric_blocked_reason_id), cast<int_t<>>(__latency_fn_type_traits_numeric_blocked_reason_not_numeric_type_id())))) {
			return __latency_fn_type_capability_readiness_blocked_reason_non_numeric_type_id();
		}
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(trait->numeric_blocked_reason_id), cast<int_t<>>(__latency_fn_type_traits_numeric_blocked_reason_backend_numeric_abi_not_ready_id()))))) {
			return __latency_fn_type_capability_readiness_blocked_reason_unknown_type_trait_id();
		}
	}
	int_t<std::uint16_t> operatorBit = required_cast<int_t<std::uint16_t>>(__latency_fn_type_capability_readiness_numeric_operator_bit_for_feature(cast<int_t<std::uint16_t>>(featureId)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(operatorBit), cast<int_t<>>(__latency_fn_type_traits_numeric_operator_mask_none_id())))) {
		return __latency_fn_type_capability_readiness_blocked_reason_unsupported_numeric_operator_id();
	}
	if (static_cast<bool>((!__latency_fn_type_traits_declares_numeric_operator(trait, operatorBit)))) {
		return __latency_fn_type_capability_readiness_blocked_reason_numeric_operator_not_in_trait_mask_id();
	}
	if (static_cast<bool>((!__latency_fn_primitive_abi_adapter_matrix_numeric_operator_feature_has_lowering_adapter(trait->type_ref_id, featureId)))) {
		return __latency_fn_type_capability_readiness_blocked_reason_backend_numeric_abi_not_ready_id();
	}
	return __latency_fn_type_capability_readiness_blocked_reason_none_id();
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_numeric_operator_bit_for_feature(int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::numeric_operator_bit_for_feature", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[122]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_plus_id())))) {
		return __latency_fn_type_traits_numeric_operator_plus_bit_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_minus_id())))) {
		return __latency_fn_type_traits_numeric_operator_minus_bit_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_multiply_id())))) {
		return __latency_fn_type_traits_numeric_operator_multiply_bit_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_divide_id())))) {
		return __latency_fn_type_traits_numeric_operator_divide_bit_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_modulo_id())))) {
		return __latency_fn_type_traits_numeric_operator_modulo_bit_id();
	}
	return __latency_fn_type_traits_numeric_operator_mask_none_id();
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_local_storage_consumer(int_t<std::uint32_t> statementSourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::local_storage_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[123]);
	CapabilityConsumerRow row = CapabilityConsumerRow{};
	row->capability_id = __latency_fn_type_capability_readiness_capability_scalar_local_storage_id();
	row->source_row_id = statementSourceRowId;
	row->provider_source_row_id = typeRefId;
	row->feature_id = __latency_fn_type_capability_readiness_feature_local_storage_id();
	row->source_key_id = __latency_fn_type_capability_readiness_source_key_frontend_statement_row_id();
	row->provider_source_key_id = __latency_fn_type_capability_readiness_source_key_type_ref_row_id();
	row->provider_type_ref_id = typeRefId;
	TypeTraitRow trait = __latency_fn_type_traits_row_from_type_ref_id(typeRefId);
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_type_capability_readiness_local_value_storage_blocked_reason_from_trait(trait));
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_none_id())))) {
		row->status_id = __latency_fn_type_capability_readiness_status_ready_id();
		row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_none_id();
		return row;
	}
	row->status_id = __latency_fn_type_capability_readiness_status_blocked_id();
	row->blocked_reason_id = blockedReasonId;
	return row;
}

}
