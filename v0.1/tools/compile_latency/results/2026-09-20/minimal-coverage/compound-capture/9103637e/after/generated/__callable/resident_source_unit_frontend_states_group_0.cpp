#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/ResidentSourceUnitFrontendStateRow.hpp"
#include "__types/resident_source_unit_frontend_states.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_state_kind_built_current_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_state_kind_reused_previous_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_state_kind_rebuilt_replacement_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_previous_state_by_owner_and_source.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_state_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_model_by_sidecar_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_model_sidecar.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_model_sidecar_debug_install_enabled.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_resident_source_unit_frontend_model_sidecar_debug_blocked.hpp"
#include "__callable/__latency_fn_proof_metrics_key_resident_source_unit_frontend_model_sidecar_debug_copied.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_model_sidecar.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_debug_model_sidecar_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_model_sidecar_debug_install_enabled.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
bool_t resident_source_unit_frontend_states::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_source_unit_frontend_states::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_source_unit_frontend_states_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_states_state_kind_built_current_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::state_kind_built_current_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_states_state_kind_reused_previous_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::state_kind_reused_previous_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_states_state_kind_rebuilt_replacement_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::state_kind_rebuilt_replacement_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_states_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_states_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[5]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
ResidentSourceUnitFrontendStateRow __latency_fn_resident_source_unit_frontend_states_previous_state_by_owner_and_source(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::previous_state_by_owner_and_source", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[6]);
	auto __latency_local_0 = report->resident_source_unit_frontend_states;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto state = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(state->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(state->source_unit_id), cast<int_t<>>(sourceUnitId))))) {
			return state;
		}
	}
	ResidentSourceUnitFrontendStateRow empty = ResidentSourceUnitFrontendStateRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
ResidentSourceUnitFrontendStateRow __latency_fn_resident_source_unit_frontend_states_state_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> stateId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::state_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[7]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(stateId, php::count(report->resident_source_unit_frontend_states))))) {
		ResidentSourceUnitFrontendStateRow row = report->resident_source_unit_frontend_states[__latency_fn_structure_row_ids_dense_index(stateId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->state_id), cast<int_t<>>(stateId)))) {
			return row;
		}
	}
	auto __latency_local_0 = report->resident_source_unit_frontend_states;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->state_id), cast<int_t<>>(stateId)))) {
			return row;
		}
	}
	ResidentSourceUnitFrontendStateRow empty = ResidentSourceUnitFrontendStateRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
shared_p<FrontendModel> __latency_fn_resident_source_unit_frontend_states_model_by_sidecar_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> sidecarId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::model_by_sidecar_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[8]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sidecarId, php::count(report->resident_source_unit_frontend_models))))) {
		return report->resident_source_unit_frontend_models[__latency_fn_structure_row_ids_dense_index(sidecarId)];
	}
	shared_p<FrontendModel> empty = create<FrontendModel>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_source_unit_frontend_states_append_model_sidecar(shared_p<CompilerProjectRunReport>& report, shared_p<FrontendModel>& model) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::append_model_sidecar", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[9]);
	(void) report->resident_source_unit_frontend_models.append(model);
	return __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_source_unit_frontend_models));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
bool_t __latency_fn_resident_source_unit_frontend_states_model_sidecar_debug_install_enabled() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::model_sidecar_debug_install_enabled", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[10]);
	return bool_t(php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_RESIDENT_FRONTEND_FULL_MODEL_SIDECAR_DEBUG_INSTALL")), string_t("1")));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_source_unit_frontend_states_debug_model_sidecar_id(shared_p<CompilerProjectRunReport>& report, shared_p<FrontendModel>& model) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::debug_model_sidecar_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[11]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_source_unit_frontend_states_model_sidecar_debug_install_enabled()))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_resident_source_unit_frontend_model_sidecar_debug_copied(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		return __latency_fn_resident_source_unit_frontend_states_append_model_sidecar(report, model);
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_resident_source_unit_frontend_model_sidecar_debug_blocked(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	return __latency_fn_structure_row_ids_none_id();
}

}
