#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__types/TypeTraitRow.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_literal_scalar_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_literal_scalar_blocked_reason_from_trait.hpp"
#include "__callable/__latency_fn_type_capability_readiness_literal_scalar_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_frontend_literal_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_type_ref_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_row_from_type_ref_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_plus_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_plus_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_minus_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_minus_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_multiply_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_multiply_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_divide_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_divide_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_modulo_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_modulo_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_feature_blocked_reason_from_trait.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_feature_supported_by_trait.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_frontend_expression_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_type_ref_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_row_from_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_feature_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_numeric_binary_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_feature_supported_by_trait.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_bool_logical_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_comparison_feature_blocked_reason_from_trait.hpp"
#include "__callable/__latency_fn_type_capability_readiness_logical_feature_blocked_reason_from_trait.hpp"
#include "__callable/__latency_fn_type_capability_readiness_numeric_feature_supported_by_trait.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_feature_blocked_reason_from_trait.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_bool_logical_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_comparison_feature_blocked_reason_from_trait.hpp"
#include "__callable/__latency_fn_type_capability_readiness_logical_feature_blocked_reason_from_trait.hpp"
#include "__callable/__latency_fn_type_capability_readiness_numeric_feature_blocked_reason_from_trait.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_literal_scalar_consumer(int_t<std::uint32_t> literalSourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::literal_scalar_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[108]);
	CapabilityConsumerRow row = CapabilityConsumerRow{};
	row->capability_id = __latency_fn_type_capability_readiness_capability_type_ref_known_id();
	row->source_row_id = literalSourceRowId;
	row->provider_source_row_id = typeRefId;
	row->feature_id = __latency_fn_type_capability_readiness_feature_literal_scalar_id();
	row->source_key_id = __latency_fn_type_capability_readiness_source_key_frontend_literal_row_id();
	row->provider_source_key_id = __latency_fn_type_capability_readiness_source_key_type_ref_row_id();
	row->provider_type_ref_id = typeRefId;
	TypeTraitRow trait = __latency_fn_type_traits_row_from_type_ref_id(typeRefId);
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_type_capability_readiness_literal_scalar_blocked_reason_from_trait(trait));
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

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_binary_operator_plus_consumer(int_t<std::uint32_t> binarySourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::binary_operator_plus_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[109]);
	return __latency_fn_type_capability_readiness_binary_operator_consumer(cast<int_t<std::uint32_t>>(binarySourceRowId), cast<int_t<std::uint32_t>>(typeRefId), __latency_fn_type_capability_readiness_feature_binary_operator_plus_id());
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_binary_operator_minus_consumer(int_t<std::uint32_t> binarySourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::binary_operator_minus_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[110]);
	return __latency_fn_type_capability_readiness_binary_operator_consumer(cast<int_t<std::uint32_t>>(binarySourceRowId), cast<int_t<std::uint32_t>>(typeRefId), __latency_fn_type_capability_readiness_feature_binary_operator_minus_id());
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_binary_operator_multiply_consumer(int_t<std::uint32_t> binarySourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::binary_operator_multiply_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[111]);
	return __latency_fn_type_capability_readiness_binary_operator_consumer(cast<int_t<std::uint32_t>>(binarySourceRowId), cast<int_t<std::uint32_t>>(typeRefId), __latency_fn_type_capability_readiness_feature_binary_operator_multiply_id());
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_binary_operator_divide_consumer(int_t<std::uint32_t> binarySourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::binary_operator_divide_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[112]);
	return __latency_fn_type_capability_readiness_binary_operator_consumer(cast<int_t<std::uint32_t>>(binarySourceRowId), cast<int_t<std::uint32_t>>(typeRefId), __latency_fn_type_capability_readiness_feature_binary_operator_divide_id());
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_binary_operator_modulo_consumer(int_t<std::uint32_t> binarySourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::binary_operator_modulo_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[113]);
	return __latency_fn_type_capability_readiness_binary_operator_consumer(cast<int_t<std::uint32_t>>(binarySourceRowId), cast<int_t<std::uint32_t>>(typeRefId), __latency_fn_type_capability_readiness_feature_binary_operator_modulo_id());
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_binary_operator_consumer(int_t<std::uint32_t> binarySourceRowId, int_t<std::uint32_t> typeRefId, int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::binary_operator_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[114]);
	CapabilityConsumerRow row = CapabilityConsumerRow{};
	row->capability_id = __latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature(cast<int_t<std::uint16_t>>(featureId));
	row->source_row_id = binarySourceRowId;
	row->provider_source_row_id = typeRefId;
	row->feature_id = featureId;
	row->source_key_id = __latency_fn_type_capability_readiness_source_key_frontend_expression_row_id();
	row->provider_source_key_id = __latency_fn_type_capability_readiness_source_key_type_ref_row_id();
	row->provider_type_ref_id = typeRefId;
	TypeTraitRow trait = __latency_fn_type_traits_row_from_type_ref_id(typeRefId);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_type_capability_readiness_binary_operator_feature_supported_by_trait(trait, cast<int_t<std::uint16_t>>(featureId))))) {
		row->status_id = __latency_fn_type_capability_readiness_status_ready_id();
		row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_none_id();
		return row;
	}
	row->status_id = __latency_fn_type_capability_readiness_status_blocked_id();
	row->blocked_reason_id = __latency_fn_type_capability_readiness_binary_operator_feature_blocked_reason_from_trait(trait, cast<int_t<std::uint16_t>>(featureId));
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature(int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::binary_operator_capability_id_for_feature", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[115]);
	shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_feature_id(featureId);
	if (static_cast<bool>(((cast<int_t<>>(operatorRow->operator_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(operatorRow->capability_id) > static_cast<int_t<> >(0))))) {
		return operatorRow->capability_id;
	}
	return __latency_fn_type_capability_readiness_capability_numeric_binary_operator_id();
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
bool_t __latency_fn_type_capability_readiness_binary_operator_feature_supported_by_trait(TypeTraitRow trait, int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::binary_operator_feature_supported_by_trait", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[116]);
	int_t<std::uint16_t> capabilityId = required_cast<int_t<std::uint16_t>>(__latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature(cast<int_t<std::uint16_t>>(featureId)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(capabilityId), cast<int_t<>>(__latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id())))) {
		return bool_t(php::identical(cast<int_t<>>(__latency_fn_type_capability_readiness_comparison_feature_blocked_reason_from_trait(trait, cast<int_t<std::uint16_t>>(featureId))), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_none_id())));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(capabilityId), cast<int_t<>>(__latency_fn_type_capability_readiness_capability_bool_logical_operator_id())))) {
		return bool_t(php::identical(cast<int_t<>>(__latency_fn_type_capability_readiness_logical_feature_blocked_reason_from_trait(trait, cast<int_t<std::uint16_t>>(featureId))), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_none_id())));
	}
	return __latency_fn_type_capability_readiness_numeric_feature_supported_by_trait(trait, cast<int_t<std::uint16_t>>(featureId));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_binary_operator_feature_blocked_reason_from_trait(TypeTraitRow trait, int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::binary_operator_feature_blocked_reason_from_trait", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[117]);
	int_t<std::uint16_t> capabilityId = required_cast<int_t<std::uint16_t>>(__latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature(cast<int_t<std::uint16_t>>(featureId)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(capabilityId), cast<int_t<>>(__latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id())))) {
		return __latency_fn_type_capability_readiness_comparison_feature_blocked_reason_from_trait(trait, cast<int_t<std::uint16_t>>(featureId));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(capabilityId), cast<int_t<>>(__latency_fn_type_capability_readiness_capability_bool_logical_operator_id())))) {
		return __latency_fn_type_capability_readiness_logical_feature_blocked_reason_from_trait(trait, cast<int_t<std::uint16_t>>(featureId));
	}
	return __latency_fn_type_capability_readiness_numeric_feature_blocked_reason_from_trait(trait, cast<int_t<std::uint16_t>>(featureId));
}

}
