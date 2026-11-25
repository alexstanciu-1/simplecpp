#include <scpp/lang/php.hpp>
#include "__types/CapabilityProviderRow.hpp"
#include "__types/OperationReadiness.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectReferenceActualArgumentRow.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__types/StorageLifetimeRequestRow.hpp"
#include "__types/TypeTraitRow.hpp"
#include "__callable/__latency_fn_project_reference_resolution_actual_argument_is_stable_local.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_callable_contract_ready.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_consumer_actual_argument_by_reference_stable_local_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_actual_argument_by_reference_stable_local.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_scalar_call_boundary_request.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_storage_context_actual_argument_by_reference_stable_local_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_actual_argument_by_reference_stable_local_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_lifetime_policy_from_trait.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_lifetime_policy_runtime_owned_temp_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_lifetime_policy_trivial_id.hpp"
#include "__callable/__latency_fn_type_traits_cleanup_policy_runtime_release_required_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_ref.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_provider_result_storage_ready.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_runtime_text_coercion_echo_id.hpp"
#include "__callable/__latency_fn_type_traits_row_from_type_ref_id.hpp"
#include "__callable/__latency_fn_type_traits_storage_policy_runtime_opaque_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_equals.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_consumer_name.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_debug_string.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_status_name.hpp"
#include "__callable/__latency_fn_type_ref_identity_name.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_stable_hash.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_semantic_hash_modulus.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_semantic_hash_modulus.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_semantic_hash_mix_request.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_is_present.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_semantic_hash_for_requests.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_semantic_hash_mix_request.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_is_present.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_publication_request_count.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_is_present.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
StorageLifetimeRequestRow __latency_fn_storage_lifetime_readiness_request_from_actual_argument_by_reference_stable_local(int_t<std::uint32_t> requestId, ProjectCallableContractRow contract, ProjectReferenceActualArgumentRow argument) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::request_from_actual_argument_by_reference_stable_local", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[44]);
	bool_t argumentReady = required_cast<bool_t>(__latency_fn_project_reference_resolution_actual_argument_is_stable_local(argument));
	return __latency_fn_storage_lifetime_readiness_scalar_call_boundary_request(cast<int_t<std::uint32_t>>(requestId), __latency_fn_type_capability_readiness_feature_actual_argument_by_reference_stable_local_id(), __latency_fn_storage_lifetime_readiness_consumer_actual_argument_by_reference_stable_local_id(), __latency_fn_storage_lifetime_readiness_storage_context_actual_argument_by_reference_stable_local_id(), argument->type_ref_id, argument->argument_source_row_id, (__latency_fn_storage_lifetime_readiness_callable_contract_ready(contract) && argumentReady));
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_storage_lifetime_readiness_lifetime_policy_from_trait(TypeTraitRow trait) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::lifetime_policy_from_trait", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[45]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(trait->cleanup_policy_id), cast<int_t<>>(__latency_fn_type_traits_cleanup_policy_runtime_release_required_id())))) {
		return __latency_fn_storage_lifetime_readiness_lifetime_policy_runtime_owned_temp_id();
	}
	return __latency_fn_storage_lifetime_readiness_lifetime_policy_trivial_id();
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
bool_t __latency_fn_storage_lifetime_readiness_provider_result_storage_ready(OperationReadiness operation, CapabilityProviderRow provider) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::provider_result_storage_ready", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[46]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(provider->type_ref_id), cast<int_t<>>(operation->result_type_ref_id)))) {
		return bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(provider->type_ref_id), cast<int_t<>>(operation->provider_type_ref_id))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	TypeTraitRow resultTrait = __latency_fn_type_traits_row_from_type_ref_id(operation->result_type_ref_id);
	if (static_cast<bool>((php::identical(cast<int_t<>>(operation->consumer_feature_id), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_runtime_text_coercion_echo_id())) && php::identical(cast<int_t<>>(resultTrait->storage_policy_id), cast<int_t<>>(__latency_fn_type_traits_storage_policy_runtime_opaque_id()))))) {
		return bool_t(static_cast<bool_t>(true));
	}
	shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_ref(operation->consumer_feature_id, operation->provider_type_ref_id);
	return ((cast<int_t<>>(operatorRow->operator_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(operatorRow->result_type_ref_id), cast<int_t<>>(operation->result_type_ref_id)));
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
bool_t __latency_fn_storage_lifetime_readiness_equals(StorageLifetimeRequestRow left, StorageLifetimeRequestRow right) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::equals", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[47]);
	return (((((php::identical(cast<int_t<>>(left->request_id), cast<int_t<>>(right->request_id)) && php::identical(cast<int_t<>>(left->consumer_kind_id), cast<int_t<>>(right->consumer_kind_id))) && php::identical(cast<int_t<>>(left->consumer_feature_id), cast<int_t<>>(right->consumer_feature_id))) && php::identical(cast<int_t<>>(left->type_ref_id), cast<int_t<>>(right->type_ref_id))) && php::identical(cast<int_t<>>(left->source_row_id), cast<int_t<>>(right->source_row_id))) && php::identical(cast<int_t<>>(left->readiness_status_id), cast<int_t<>>(right->readiness_status_id)));
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
string_t __latency_fn_storage_lifetime_readiness_debug_string(StorageLifetimeRequestRow row) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[48]);
	return (string_t("storage_lifetime:") + cast<string_t>(__latency_fn_storage_lifetime_readiness_consumer_name(row->consumer_kind_id)) + string_t(":") + cast<string_t>(__latency_fn_type_ref_identity_name(row->type_ref_id)) + string_t(":") + cast<string_t>(__latency_fn_storage_lifetime_readiness_status_name(row->readiness_status_id)));
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_storage_lifetime_readiness_stable_hash(StorageLifetimeRequestRow row) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[49]);
	string_t identity = required_cast<string_t>((string_t("storage_lifetime:v2:") + cast<string_t>(cast<int_t<>>(row->request_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->consumer_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->capability_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->consumer_feature_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->storage_context_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->readiness_status_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
int_t<> __latency_fn_storage_lifetime_readiness_semantic_hash_modulus() {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::semantic_hash_modulus", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[50]);
	return static_cast<int_t<> >(1000000007);
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
int_t<> __latency_fn_storage_lifetime_readiness_semantic_hash_mix_int(int_t<> hash, int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::semantic_hash_mix_int", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[51]);
	int_t<> mixed = required_cast<int_t<>>((((hash * static_cast<int_t<> >(131)) + value) % __latency_fn_storage_lifetime_readiness_semantic_hash_modulus()));
	if (static_cast<bool>((mixed < static_cast<int_t<> >(0)))) {
		return (mixed + __latency_fn_storage_lifetime_readiness_semantic_hash_modulus());
	}
	return mixed;
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
int_t<> __latency_fn_storage_lifetime_readiness_semantic_hash_mix_request(int_t<> hash, StorageLifetimeRequestRow row) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::semantic_hash_mix_request", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[52]);
	int_t<> next = required_cast<int_t<>>(__latency_fn_storage_lifetime_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->request_id)));
	next = __latency_fn_storage_lifetime_readiness_semantic_hash_mix_int(next, cast<int_t<>>(row->consumer_kind_id));
	next = __latency_fn_storage_lifetime_readiness_semantic_hash_mix_int(next, cast<int_t<>>(row->capability_id));
	next = __latency_fn_storage_lifetime_readiness_semantic_hash_mix_int(next, cast<int_t<>>(row->consumer_feature_id));
	next = __latency_fn_storage_lifetime_readiness_semantic_hash_mix_int(next, cast<int_t<>>(row->storage_context_id));
	next = __latency_fn_storage_lifetime_readiness_semantic_hash_mix_int(next, cast<int_t<>>(row->type_ref_id));
	next = __latency_fn_storage_lifetime_readiness_semantic_hash_mix_int(next, cast<int_t<>>(row->source_row_id));
	next = __latency_fn_storage_lifetime_readiness_semantic_hash_mix_int(next, cast<int_t<>>(row->storage_policy_id));
	next = __latency_fn_storage_lifetime_readiness_semantic_hash_mix_int(next, cast<int_t<>>(row->copy_policy_id));
	next = __latency_fn_storage_lifetime_readiness_semantic_hash_mix_int(next, cast<int_t<>>(row->cleanup_policy_id));
	next = __latency_fn_storage_lifetime_readiness_semantic_hash_mix_int(next, cast<int_t<>>(row->lifetime_policy_id));
	next = __latency_fn_storage_lifetime_readiness_semantic_hash_mix_int(next, cast<int_t<>>(row->readiness_status_id));
	return next;
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_storage_lifetime_readiness_semantic_hash_for_requests(StorageLifetimeRequestRow readyRow, StorageLifetimeRequestRow blockedRow) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::semantic_hash_for_requests", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[53]);
	int_t<> hash = required_cast<int_t<>>(static_cast<int_t<> >(17));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_storage_lifetime_readiness_request_is_present(readyRow)))) {
		hash = __latency_fn_storage_lifetime_readiness_semantic_hash_mix_request(hash, readyRow);
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_storage_lifetime_readiness_request_is_present(blockedRow)))) {
		hash = __latency_fn_storage_lifetime_readiness_semantic_hash_mix_request(hash, blockedRow);
	}
	return __latency_fn_structure_row_ids_uint32_from_int(hash);
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
bool_t __latency_fn_storage_lifetime_readiness_request_is_present(StorageLifetimeRequestRow row) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::request_is_present", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[54]);
	return bool_t((cast<int_t<>>(row->request_id) > static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_storage_lifetime_readiness_publication_request_count(StorageLifetimeRequestRow readyRow, StorageLifetimeRequestRow blockedRow) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::publication_request_count", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[55]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_storage_lifetime_readiness_request_is_present(readyRow)))) {
		total = (total + static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_storage_lifetime_readiness_request_is_present(blockedRow)))) {
		total = (total + static_cast<int_t<> >(1));
	}
	return __latency_fn_structure_row_ids_uint32_from_int(total);
}

}
