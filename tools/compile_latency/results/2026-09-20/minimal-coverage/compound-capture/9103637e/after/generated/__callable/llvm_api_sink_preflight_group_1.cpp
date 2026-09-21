#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/LlvmApiHandleMapSummaryRow.hpp"
#include "__types/LlvmApiModuleBuildRow.hpp"
#include "__types/LlvmApiSinkPreflightArtifact.hpp"
#include "__types/LlvmApiSinkPreflightRow.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_sidecar_count_from_emission.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_alloca_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_local_slot_handle_count_from_emission.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_blocked_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_from_emission.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_ready_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_blocked_reason_from_emission.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_blocked_reason_lowering_plan_blocked_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_blocked_reason_no_emission_decisions_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_from_emission.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_ready_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_blocked_reason_from_emission.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_preflight_row_from_emission.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_sidecar_count_from_emission.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_sink_id_llvm_api_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_sink_mode_row_only_preflight_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_source_model_backend_emission_decision_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_from_emission.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_artifact_stable_hash.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_blocked_reason_from_emission.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_module_build_row_from_emission.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_sink_mode_row_only_preflight_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_from_emission.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_handle_summary_row_from_module_build.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_local_slot_handle_count_from_emission.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_append_preflight.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_append_module_build.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_llvm_api_sink_preflight_sidecar_count_from_emission(BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::sidecar_count_from_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[13]);
	return __latency_fn_structure_row_ids_uint32_from_int((((cast<int_t<>>(emission->binary_operand_count) + cast<int_t<>>(emission->local_operand_count)) + cast<int_t<>>(emission->call_argument_count)) + cast<int_t<>>(emission->control_flow_operand_count)));
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_llvm_api_sink_preflight_local_slot_handle_count_from_emission(BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::local_slot_handle_count_from_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[14]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = emission->local_operands;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->local_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_local_operation_alloca_id())))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_api_sink_preflight_status_from_emission(BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::status_from_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[15]);
	if (static_cast<bool>((((cast<int_t<>>(emission->decision_count) > static_cast<int_t<> >(0)) && (cast<int_t<>>(emission->ready_count) > static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(emission->blocked_count), static_cast<int_t<> >(0))))) {
		return __latency_fn_llvm_api_sink_preflight_status_ready_id();
	}
	return __latency_fn_llvm_api_sink_preflight_status_blocked_id();
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_api_sink_preflight_blocked_reason_from_emission(BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::blocked_reason_from_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[16]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(__latency_fn_llvm_api_sink_preflight_status_from_emission(emission)), cast<int_t<>>(__latency_fn_llvm_api_sink_preflight_status_ready_id())))) {
		return __latency_fn_llvm_api_sink_preflight_blocked_reason_none_id();
	}
	if (static_cast<bool>((cast<int_t<>>(emission->blocked_count) > static_cast<int_t<> >(0)))) {
		return __latency_fn_llvm_api_sink_preflight_blocked_reason_lowering_plan_blocked_id();
	}
	return __latency_fn_llvm_api_sink_preflight_blocked_reason_no_emission_decisions_id();
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
LlvmApiSinkPreflightRow __latency_fn_llvm_api_sink_preflight_preflight_row_from_emission(int_t<std::uint32_t> preflightId, BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::preflight_row_from_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[17]);
	LlvmApiSinkPreflightRow row = LlvmApiSinkPreflightRow{};
	row->preflight_id = preflightId;
	row->sink_id = __latency_fn_llvm_api_sink_preflight_sink_id_llvm_api_id();
	row->sink_mode_id = __latency_fn_llvm_api_sink_preflight_sink_mode_row_only_preflight_id();
	row->owner_source_unit_id = emission->owner_source_unit_id;
	row->owner_symbol_id = emission->owner_symbol_id;
	row->source_model_id = __latency_fn_llvm_api_sink_preflight_source_model_backend_emission_decision_id();
	row->emission_decision_count = emission->decision_count;
	row->value_count = emission->value_count;
	row->block_count = emission->block_count;
	row->sidecar_count = __latency_fn_llvm_api_sink_preflight_sidecar_count_from_emission(emission);
	row->status_id = __latency_fn_llvm_api_sink_preflight_status_from_emission(emission);
	row->blocked_reason_id = __latency_fn_llvm_api_sink_preflight_blocked_reason_from_emission(emission);
	return row;
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
LlvmApiModuleBuildRow __latency_fn_llvm_api_sink_preflight_module_build_row_from_emission(int_t<std::uint32_t> moduleBuildId, BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::module_build_row_from_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[18]);
	int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_llvm_api_sink_preflight_status_from_emission(emission));
	LlvmApiModuleBuildRow row = LlvmApiModuleBuildRow{};
	row->module_build_id = moduleBuildId;
	row->sink_mode_id = __latency_fn_llvm_api_sink_preflight_sink_mode_row_only_preflight_id();
	row->owner_source_unit_id = emission->owner_source_unit_id;
	row->owner_symbol_id = emission->owner_symbol_id;
	row->partition_execution_id = __latency_fn_structure_row_ids_none_id();
	row->cache_object_id = __latency_fn_structure_row_ids_none_id();
	row->input_emission_hash = __latency_fn_backend_emission_decisions_artifact_stable_hash(emission);
	row->status_id = statusId;
	row->blocked_reason_id = __latency_fn_llvm_api_sink_preflight_blocked_reason_from_emission(emission);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_llvm_api_sink_preflight_status_ready_id())))) {
		row->function_count = emission->ready_count;
		row->basic_block_count = emission->block_count;
		row->value_count = emission->value_count;
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
LlvmApiHandleMapSummaryRow __latency_fn_llvm_api_sink_preflight_handle_summary_row_from_module_build(int_t<std::uint32_t> summaryId, LlvmApiModuleBuildRow build, BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::handle_summary_row_from_module_build", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[19]);
	LlvmApiHandleMapSummaryRow row = LlvmApiHandleMapSummaryRow{};
	row->summary_id = summaryId;
	row->module_build_id = build->module_build_id;
	row->status_id = build->status_id;
	row->blocked_reason_id = build->blocked_reason_id;
	if (static_cast<bool>(php::identical(cast<int_t<>>(build->status_id), cast<int_t<>>(__latency_fn_llvm_api_sink_preflight_status_ready_id())))) {
		row->function_handle_count = build->function_count;
		row->block_handle_count = build->basic_block_count;
		row->value_handle_count = build->value_count;
		row->local_slot_handle_count = __latency_fn_llvm_api_sink_preflight_local_slot_handle_count_from_emission(emission);
		row->unresolved_handle_count = __latency_fn_structure_row_ids_none_id();
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
void __latency_fn_llvm_api_sink_preflight_append_preflight(LlvmApiSinkPreflightArtifact& artifact, LlvmApiSinkPreflightRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::append_preflight", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[20]);
	row->preflight_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->preflights));
	(void) artifact->preflights.append(row);
	artifact->preflight_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->preflights));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_llvm_api_sink_preflight_status_ready_id())))) {
		artifact->ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->ready_count) + static_cast<int_t<> >(1)));
		return;
	}
	artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_count) + static_cast<int_t<> >(1)));
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
void __latency_fn_llvm_api_sink_preflight_append_module_build(LlvmApiSinkPreflightArtifact& artifact, LlvmApiModuleBuildRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::append_module_build", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[21]);
	row->module_build_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->module_builds));
	(void) artifact->module_builds.append(row);
	artifact->module_build_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->module_builds));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_llvm_api_sink_preflight_status_ready_id())))) {
		artifact->module_ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->module_ready_count) + static_cast<int_t<> >(1)));
		return;
	}
	artifact->module_blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->module_blocked_count) + static_cast<int_t<> >(1)));
}

}
