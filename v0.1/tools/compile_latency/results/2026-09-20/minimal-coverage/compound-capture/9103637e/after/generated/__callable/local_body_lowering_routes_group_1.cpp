#include <scpp/lang/php.hpp>
#include "__types/LocalBodyLoweringRouteDescriptorArtifact.hpp"
#include "__types/LocalBodyLoweringRouteDescriptorRow.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_descriptor_by_step_kind.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_descriptors.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_descriptor_by_step_kind.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_step_kind_name.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_sidecar_binary_operand_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_sidecar_call_argument_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_sidecar_control_flow_operand_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_sidecar_local_operand_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_sidecar_name.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_sidecar_none_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_sidecar_string_literal_operand_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_artifact_kind_local_body_lowering_routes_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_new_artifact.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_append_row.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_sidecar_none_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_status_blocked_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_status_descriptor_backed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_append_row.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_build.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_descriptors.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_new_artifact.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_debug_string.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_local_body_lowering_routes[]; }
namespace scpp {
shared_p<LocalBodyLoweringRouteDescriptorRow> __latency_fn_local_body_lowering_routes_descriptor_by_step_kind(int_t<std::uint16_t> stepKindId) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering_routes::descriptor_by_step_kind", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering_routes.phs", __latency_lines_local_body_lowering_routes[11]);
	auto __latency_local_0 = __latency_fn_local_body_lowering_routes_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->step_kind_id), cast<int_t<>>(stepKindId)))) {
			return row;
		}
	}
	shared_p<LocalBodyLoweringRouteDescriptorRow> empty = create<LocalBodyLoweringRouteDescriptorRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering_routes[]; }
namespace scpp {
string_t __latency_fn_local_body_lowering_routes_step_kind_name(int_t<std::uint16_t> stepKindId) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering_routes::step_kind_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering_routes.phs", __latency_lines_local_body_lowering_routes[12]);
	shared_p<LocalBodyLoweringRouteDescriptorRow> row = __latency_fn_local_body_lowering_routes_descriptor_by_step_kind(cast<int_t<std::uint16_t>>(stepKindId));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->step_kind_id), cast<int_t<>>(stepKindId)))) {
		return row->step_kind_key;
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering_routes[]; }
namespace scpp {
string_t __latency_fn_local_body_lowering_routes_sidecar_name(int_t<std::uint16_t> sidecarKindId) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering_routes::sidecar_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering_routes.phs", __latency_lines_local_body_lowering_routes[13]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(sidecarKindId), cast<int_t<>>(__latency_fn_local_body_lowering_routes_sidecar_none_id())))) {
		return string_t("none");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(sidecarKindId), cast<int_t<>>(__latency_fn_local_body_lowering_routes_sidecar_binary_operand_id())))) {
		return string_t("binary_operand");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(sidecarKindId), cast<int_t<>>(__latency_fn_local_body_lowering_routes_sidecar_local_operand_id())))) {
		return string_t("local_operand");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(sidecarKindId), cast<int_t<>>(__latency_fn_local_body_lowering_routes_sidecar_call_argument_id())))) {
		return string_t("call_argument");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(sidecarKindId), cast<int_t<>>(__latency_fn_local_body_lowering_routes_sidecar_control_flow_operand_id())))) {
		return string_t("control_flow_operand");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(sidecarKindId), cast<int_t<>>(__latency_fn_local_body_lowering_routes_sidecar_string_literal_operand_id())))) {
		return string_t("string_literal_operand");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering_routes[]; }
namespace scpp {
shared_p<LocalBodyLoweringRouteDescriptorArtifact> __latency_fn_local_body_lowering_routes_new_artifact() {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering_routes::new_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering_routes.phs", __latency_lines_local_body_lowering_routes[14]);
	shared_p<LocalBodyLoweringRouteDescriptorArtifact> artifact = create<LocalBodyLoweringRouteDescriptorArtifact>();
	artifact->artifact_kind_id = __latency_fn_local_body_lowering_routes_artifact_kind_local_body_lowering_routes_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	php::vector_reserve(artifact->rows, static_cast<int_t<> >(32));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering_routes[]; }
namespace scpp {
void __latency_fn_local_body_lowering_routes_append_row(shared_p<LocalBodyLoweringRouteDescriptorArtifact>& artifact, shared_p<LocalBodyLoweringRouteDescriptorRow> row) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering_routes::append_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering_routes.phs", __latency_lines_local_body_lowering_routes[15]);
	(void) artifact->rows.append(row);
	artifact->row_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->rows));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_local_body_lowering_routes_status_descriptor_backed_id())))) {
		artifact->descriptor_backed_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->descriptor_backed_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_local_body_lowering_routes_status_blocked_id())))) {
		artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(row->sidecar_kind_id), cast<int_t<>>(__latency_fn_local_body_lowering_routes_sidecar_none_id()))))) {
		artifact->sidecar_required_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->sidecar_required_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering_routes[]; }
namespace scpp {
shared_p<LocalBodyLoweringRouteDescriptorArtifact> __latency_fn_local_body_lowering_routes_build() {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering_routes::build", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering_routes.phs", __latency_lines_local_body_lowering_routes[16]);
	shared_p<LocalBodyLoweringRouteDescriptorArtifact> artifact = __latency_fn_local_body_lowering_routes_new_artifact();
	auto __latency_local_0 = __latency_fn_local_body_lowering_routes_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		__latency_fn_local_body_lowering_routes_append_row(artifact, row);
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering_routes[]; }
namespace scpp {
string_t __latency_fn_local_body_lowering_routes_debug_string(shared_p<LocalBodyLoweringRouteDescriptorArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering_routes::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering_routes.phs", __latency_lines_local_body_lowering_routes[17]);
	return (string_t("local_body_lowering_routes:rows=") + cast<string_t>(cast<int_t<>>(artifact->row_count)) + string_t(":descriptor_backed=") + cast<string_t>(cast<int_t<>>(artifact->descriptor_backed_count)) + string_t(":sidecar_required=") + cast<string_t>(cast<int_t<>>(artifact->sidecar_required_count)) + string_t(":blocked=") + cast<string_t>(cast<int_t<>>(artifact->blocked_count)));
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering_routes[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_local_body_lowering_routes_stable_hash(shared_p<LocalBodyLoweringRouteDescriptorArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering_routes::stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering_routes.phs", __latency_lines_local_body_lowering_routes[18]);
	string_t identity = required_cast<string_t>((string_t("local_body_lowering_routes:v1:") + cast<string_t>(cast<int_t<>>(artifact->row_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->descriptor_backed_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->sidecar_required_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->blocked_count))));
	return php::stable_hash_string_u64(identity);
}

}
