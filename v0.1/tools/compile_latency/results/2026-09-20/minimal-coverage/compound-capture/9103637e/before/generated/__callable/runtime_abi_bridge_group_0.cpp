#include <scpp/lang/php.hpp>
#include "__types/RuntimeAbiBridgeArtifact.hpp"
#include "__types/SemanticRuntimeAbiBridgeDescriptorRow.hpp"
#include "__types/runtime_abi_bridge.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_artifact_kind_runtime_abi_bridge_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_artifact_kind_runtime_abi_bridge_id.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_new_artifact.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_authority_identity_hash.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_bridge_row_count.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_schema_version.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_text_coercion_family_count.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_text_coercion_family_deferred_member_count.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_text_coercion_family_member_count.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_append_row.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_consumes_owned_argument.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_has_owned_return.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_optimization_visibility_opaque_call_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_source_consumption_blocked_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_status_blocked_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_append_row.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_build.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_new_artifact.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_rows.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_row_by_bridge_row_id.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_text_coercion_row_by_source_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_text_coercion_family_id_for_source_type_ref_id.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_carrier_name.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_c_string_bytes_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_mut_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_vector_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i32_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i64_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_void_id.hpp"
namespace scpp { extern const int __latency_lines_runtime_abi_bridge[]; }
namespace scpp {
bool_t runtime_abi_bridge::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == runtime_abi_bridge::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_runtime_abi_bridge_artifact_kind_runtime_abi_bridge_id() {
	SCPP_CALL_DEPTH_GUARD("runtime_abi_bridge::artifact_kind_runtime_abi_bridge_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_abi_bridge.phs", __latency_lines_runtime_abi_bridge[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_runtime_abi_bridge[]; }
namespace scpp {
shared_p<RuntimeAbiBridgeArtifact> __latency_fn_runtime_abi_bridge_new_artifact() {
	SCPP_CALL_DEPTH_GUARD("runtime_abi_bridge::new_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_abi_bridge.phs", __latency_lines_runtime_abi_bridge[1]);
	shared_p<RuntimeAbiBridgeArtifact> artifact = create<RuntimeAbiBridgeArtifact>();
	artifact->artifact_kind_id = __latency_fn_runtime_abi_bridge_artifact_kind_runtime_abi_bridge_id();
	artifact->schema_version = __latency_fn_semantic_runtime_abi_bridge_schema_version();
	artifact->authority_identity_hash = __latency_fn_semantic_runtime_abi_bridge_authority_identity_hash();
	artifact->policy_state = string_t("runtime_abi_bridge_metadata_ready_partial_source_consumption_ready");
	artifact->text_coercion_family_count = __latency_fn_semantic_runtime_abi_bridge_text_coercion_family_count();
	artifact->text_coercion_family_member_count = __latency_fn_semantic_runtime_abi_bridge_text_coercion_family_member_count();
	artifact->text_coercion_family_deferred_member_count = __latency_fn_semantic_runtime_abi_bridge_text_coercion_family_deferred_member_count();
	php::vector_reserve(artifact->rows, cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_bridge_row_count()));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_runtime_abi_bridge[]; }
namespace scpp {
void __latency_fn_runtime_abi_bridge_append_row(shared_p<RuntimeAbiBridgeArtifact>& artifact, shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row) {
	SCPP_CALL_DEPTH_GUARD("runtime_abi_bridge::append_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_abi_bridge.phs", __latency_lines_runtime_abi_bridge[2]);
	(void) artifact->rows.append(row);
	artifact->row_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->rows));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->declaration_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_ready_id())))) {
		artifact->declaration_ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->declaration_ready_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->call_lowering_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_ready_id())))) {
		artifact->call_lowering_ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->call_lowering_ready_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->call_lowering_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_blocked_id())))) {
		artifact->call_lowering_blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->call_lowering_blocked_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->source_consumption_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_source_consumption_blocked_id())))) {
		artifact->source_consumption_blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->source_consumption_blocked_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->source_consumption_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id())))) {
		artifact->source_consumption_ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->source_consumption_ready_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->optimization_visibility_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_optimization_visibility_opaque_call_id())))) {
		artifact->opaque_call_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->opaque_call_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_runtime_helper_effects_has_owned_return(row)))) {
		artifact->owned_return_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->owned_return_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_runtime_helper_effects_consumes_owned_argument(row)))) {
		artifact->consumes_owned_argument_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->consumes_owned_argument_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_runtime_abi_bridge[]; }
namespace scpp {
shared_p<RuntimeAbiBridgeArtifact> __latency_fn_runtime_abi_bridge_build() {
	SCPP_CALL_DEPTH_GUARD("runtime_abi_bridge::build", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_abi_bridge.phs", __latency_lines_runtime_abi_bridge[3]);
	shared_p<RuntimeAbiBridgeArtifact> artifact = __latency_fn_runtime_abi_bridge_new_artifact();
	auto __latency_local_0 = __latency_fn_semantic_runtime_abi_bridge_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		__latency_fn_runtime_abi_bridge_append_row(artifact, row);
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_runtime_abi_bridge[]; }
namespace scpp {
shared_p<SemanticRuntimeAbiBridgeDescriptorRow> __latency_fn_runtime_abi_bridge_row_by_bridge_row_id(shared_p<RuntimeAbiBridgeArtifact> artifact, int_t<std::uint16_t> bridgeRowId) {
	SCPP_CALL_DEPTH_GUARD("runtime_abi_bridge::row_by_bridge_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_abi_bridge.phs", __latency_lines_runtime_abi_bridge[4]);
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->bridge_row_id), cast<int_t<>>(bridgeRowId)))) {
			return row;
		}
	}
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> empty = create<SemanticRuntimeAbiBridgeDescriptorRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_runtime_abi_bridge[]; }
namespace scpp {
shared_p<SemanticRuntimeAbiBridgeDescriptorRow> __latency_fn_runtime_abi_bridge_text_coercion_row_by_source_type_ref_id(shared_p<RuntimeAbiBridgeArtifact> artifact, int_t<std::uint32_t> sourceTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("runtime_abi_bridge::text_coercion_row_by_source_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_abi_bridge.phs", __latency_lines_runtime_abi_bridge[5]);
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(row->source_type_ref_id), cast<int_t<>>(sourceTypeRefId)) && (cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_text_coercion_family_id_for_source_type_ref_id(row->source_type_ref_id)) > static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(row->return_carrier_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id()))))) {
			return row;
		}
	}
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> empty = create<SemanticRuntimeAbiBridgeDescriptorRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_runtime_abi_bridge[]; }
namespace scpp {
string_t __latency_fn_runtime_abi_bridge_carrier_name(int_t<std::uint16_t> carrierId) {
	SCPP_CALL_DEPTH_GUARD("runtime_abi_bridge::carrier_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_abi_bridge.phs", __latency_lines_runtime_abi_bridge[6]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(carrierId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id())))) {
		return string_t("opaque_runtime_string");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(carrierId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i64_id())))) {
		return string_t("primitive_i64");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(carrierId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_abi_carrier_c_string_bytes_id())))) {
		return string_t("c_string_bytes");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(carrierId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_abi_carrier_void_id())))) {
		return string_t("void");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(carrierId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_mut_id())))) {
		return string_t("opaque_runtime_string_mut");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(carrierId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_vector_id())))) {
		return string_t("opaque_runtime_vector");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(carrierId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i32_id())))) {
		return string_t("primitive_i32");
	}
	return string_t("unknown");
}

}
