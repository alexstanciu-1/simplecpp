#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionAdapterDescriptorArtifact.hpp"
#include "__types/BackendEmissionAdapterDescriptorRow.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_descriptor_by_step_kind.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_descriptors.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_descriptor_by_step_kind.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_value_role_for_step_kind.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_return_value_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_descriptor_by_step_kind.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_type_policy_any_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_type_policy_for_step_kind.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_artifact_kind_backend_emission_adapter_routes_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_new_artifact.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_append_row.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_llvm_emitter_echo_i64_printf_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_llvm_emitter_echo_string_abi_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_status_blocked_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_status_descriptor_backed_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_type_policy_any_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_echo_scalar_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_echo_string_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_return_value_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_append_row.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_build.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_descriptors.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_new_artifact.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_debug_string.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_backend_emission_adapter_routes[]; }
namespace scpp {
shared_p<BackendEmissionAdapterDescriptorRow> __latency_fn_backend_emission_adapter_routes_descriptor_by_step_kind(int_t<std::uint16_t> stepKindId) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_adapter_routes::descriptor_by_step_kind", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_adapter_routes.phs", __latency_lines_backend_emission_adapter_routes[21]);
	auto __latency_local_0 = __latency_fn_backend_emission_adapter_routes_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->step_kind_id), cast<int_t<>>(stepKindId)))) {
			return row;
		}
	}
	shared_p<BackendEmissionAdapterDescriptorRow> empty = create<BackendEmissionAdapterDescriptorRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_backend_emission_adapter_routes[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_adapter_routes_value_role_for_step_kind(int_t<std::uint16_t> stepKindId) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_adapter_routes::value_role_for_step_kind", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_adapter_routes.phs", __latency_lines_backend_emission_adapter_routes[22]);
	shared_p<BackendEmissionAdapterDescriptorRow> row = __latency_fn_backend_emission_adapter_routes_descriptor_by_step_kind(cast<int_t<std::uint16_t>>(stepKindId));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->step_kind_id), cast<int_t<>>(stepKindId)))) {
		return row->value_role_id;
	}
	return __latency_fn_backend_emission_decisions_value_role_return_value_id();
}

}

namespace scpp { extern const int __latency_lines_backend_emission_adapter_routes[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_adapter_routes_type_policy_for_step_kind(int_t<std::uint16_t> stepKindId) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_adapter_routes::type_policy_for_step_kind", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_adapter_routes.phs", __latency_lines_backend_emission_adapter_routes[23]);
	shared_p<BackendEmissionAdapterDescriptorRow> row = __latency_fn_backend_emission_adapter_routes_descriptor_by_step_kind(cast<int_t<std::uint16_t>>(stepKindId));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->step_kind_id), cast<int_t<>>(stepKindId)))) {
		return row->type_policy_id;
	}
	return __latency_fn_backend_emission_adapter_routes_type_policy_any_id();
}

}

namespace scpp { extern const int __latency_lines_backend_emission_adapter_routes[]; }
namespace scpp {
shared_p<BackendEmissionAdapterDescriptorArtifact> __latency_fn_backend_emission_adapter_routes_new_artifact() {
	SCPP_CALL_DEPTH_GUARD("backend_emission_adapter_routes::new_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_adapter_routes.phs", __latency_lines_backend_emission_adapter_routes[24]);
	shared_p<BackendEmissionAdapterDescriptorArtifact> artifact = create<BackendEmissionAdapterDescriptorArtifact>();
	artifact->artifact_kind_id = __latency_fn_backend_emission_adapter_routes_artifact_kind_backend_emission_adapter_routes_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
	php::vector_reserve(artifact->rows, static_cast<int_t<> >(30));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_emission_adapter_routes[]; }
namespace scpp {
void __latency_fn_backend_emission_adapter_routes_append_row(shared_p<BackendEmissionAdapterDescriptorArtifact>& artifact, shared_p<BackendEmissionAdapterDescriptorRow> row) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_adapter_routes::append_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_adapter_routes.phs", __latency_lines_backend_emission_adapter_routes[25]);
	(void) artifact->rows.append(row);
	artifact->row_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->rows));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_backend_emission_adapter_routes_status_descriptor_backed_id())))) {
		artifact->descriptor_backed_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->descriptor_backed_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_backend_emission_adapter_routes_status_blocked_id())))) {
		artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->value_role_id), cast<int_t<>>(__latency_fn_backend_emission_decisions_value_role_return_value_id())))) {
		artifact->return_value_role_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->return_value_role_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->value_role_id), cast<int_t<>>(__latency_fn_backend_emission_decisions_value_role_echo_scalar_id())))) {
		artifact->echo_scalar_role_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->echo_scalar_role_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->value_role_id), cast<int_t<>>(__latency_fn_backend_emission_decisions_value_role_echo_string_id())))) {
		artifact->echo_string_role_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->echo_string_role_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(row->type_policy_id), cast<int_t<>>(__latency_fn_backend_emission_adapter_routes_type_policy_any_id()))))) {
		artifact->typed_policy_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->typed_policy_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->llvm_emitter_id), cast<int_t<>>(__latency_fn_backend_emission_adapter_routes_llvm_emitter_echo_i64_printf_id())))) {
		artifact->echo_i64_emitter_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->echo_i64_emitter_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->llvm_emitter_id), cast<int_t<>>(__latency_fn_backend_emission_adapter_routes_llvm_emitter_echo_string_abi_id())))) {
		artifact->echo_string_emitter_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->echo_string_emitter_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_backend_emission_adapter_routes[]; }
namespace scpp {
shared_p<BackendEmissionAdapterDescriptorArtifact> __latency_fn_backend_emission_adapter_routes_build() {
	SCPP_CALL_DEPTH_GUARD("backend_emission_adapter_routes::build", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_adapter_routes.phs", __latency_lines_backend_emission_adapter_routes[26]);
	shared_p<BackendEmissionAdapterDescriptorArtifact> artifact = __latency_fn_backend_emission_adapter_routes_new_artifact();
	auto __latency_local_0 = __latency_fn_backend_emission_adapter_routes_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		__latency_fn_backend_emission_adapter_routes_append_row(artifact, row);
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_emission_adapter_routes[]; }
namespace scpp {
string_t __latency_fn_backend_emission_adapter_routes_debug_string(shared_p<BackendEmissionAdapterDescriptorArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_adapter_routes::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_adapter_routes.phs", __latency_lines_backend_emission_adapter_routes[27]);
	return (string_t("backend_emission_adapter_routes:rows=") + cast<string_t>(cast<int_t<>>(artifact->row_count)) + string_t(":descriptor_backed=") + cast<string_t>(cast<int_t<>>(artifact->descriptor_backed_count)) + string_t(":return_value_roles=") + cast<string_t>(cast<int_t<>>(artifact->return_value_role_count)) + string_t(":echo_scalar_roles=") + cast<string_t>(cast<int_t<>>(artifact->echo_scalar_role_count)) + string_t(":echo_string_roles=") + cast<string_t>(cast<int_t<>>(artifact->echo_string_role_count)) + string_t(":typed_policies=") + cast<string_t>(cast<int_t<>>(artifact->typed_policy_count)) + string_t(":echo_i64_emitters=") + cast<string_t>(cast<int_t<>>(artifact->echo_i64_emitter_count)) + string_t(":echo_string_emitters=") + cast<string_t>(cast<int_t<>>(artifact->echo_string_emitter_count)) + string_t(":blocked=") + cast<string_t>(cast<int_t<>>(artifact->blocked_count)));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_adapter_routes[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_backend_emission_adapter_routes_stable_hash(shared_p<BackendEmissionAdapterDescriptorArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_adapter_routes::stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_adapter_routes.phs", __latency_lines_backend_emission_adapter_routes[28]);
	string_t identity = required_cast<string_t>((string_t("backend_emission_adapter_routes:v2:") + cast<string_t>(cast<int_t<>>(artifact->row_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->descriptor_backed_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->return_value_role_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->echo_scalar_role_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->echo_string_role_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->typed_policy_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->echo_i64_emitter_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->echo_string_emitter_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->blocked_count))));
	return php::stable_hash_string_u64(identity);
}

}
