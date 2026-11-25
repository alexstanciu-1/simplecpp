#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendSinkBoundaryRow.hpp"
#include "__types/FunctionBodyTextEmissionPreflightArtifact.hpp"
#include "__types/FunctionBodyTextEmissionPreflightRow.hpp"
#include "__types/LoweringPlan.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_next_step_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_next_step_restore_ready_lowering_steps_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_status_from_plan.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_blocked_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_ready_id.hpp"
#include "__callable/__latency_fn_lowering_plan_backend_emit_status_ready_for_text_sink_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_status_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_blocked_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_ready_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_from_lowering_plan.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_backend_sink_boundary_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_backend_sink_boundary_from_plan.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_backend_sink_boundary_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_id_llvm_text_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_kind_llvm_text_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_status_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_artifact_kind_function_body_text_preflight_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_new_preflight_artifact_for_source.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_preflight_model_llvm_text_sink_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_new_preflight_artifact.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_new_preflight_artifact_for_source.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_source_model_lowering_plan_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_from_lowering_plan.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_preflight_row_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_preflight_row_from_plan.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_blocked_reason_lowering_plan_blocked_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_next_step_restore_ready_lowering_steps_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_preflight_row_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_status_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_ready_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_text_sink_policy_llvm_text_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_preflight.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_preflight.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_new_preflight_artifact.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_preflight_from_plan.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_preflight_row_from_plan.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_next_step_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::next_step_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[15]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_llvm_text_from_plan_next_step_restore_ready_lowering_steps_id())))) {
		return string_t("restore_ready_lowering_steps");
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_text_from_plan_sink_status_from_plan(shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::sink_status_from_plan", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[16]);
	if (static_cast<bool>(((php::identical(cast<int_t<>>(plan->backend_emit_status_id), cast<int_t<>>(__latency_fn_lowering_plan_backend_emit_status_ready_for_text_sink_id())) && php::identical(cast<int_t<>>(plan->blocked_request_count), static_cast<int_t<> >(0))) && (cast<int_t<>>(plan->step_count) > static_cast<int_t<> >(0))))) {
		return __latency_fn_llvm_text_from_plan_status_ready_id();
	}
	return __latency_fn_llvm_text_from_plan_status_blocked_id();
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_text_from_plan_sink_status_from_emission(BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::sink_status_from_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[17]);
	if (static_cast<bool>((((cast<int_t<>>(emission->decision_count) > static_cast<int_t<> >(0)) && (cast<int_t<>>(emission->ready_count) > static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(emission->blocked_count), static_cast<int_t<> >(0))))) {
		return __latency_fn_llvm_text_from_plan_status_ready_id();
	}
	return __latency_fn_llvm_text_from_plan_status_blocked_id();
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
BackendSinkBoundaryRow __latency_fn_llvm_text_from_plan_backend_sink_boundary_from_plan(shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::backend_sink_boundary_from_plan", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[18]);
	BackendEmissionDecisionArtifact emission = __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
	return __latency_fn_llvm_text_from_plan_backend_sink_boundary_from_emission(emission);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
BackendSinkBoundaryRow __latency_fn_llvm_text_from_plan_backend_sink_boundary_from_emission(BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::backend_sink_boundary_from_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[19]);
	BackendSinkBoundaryRow row = BackendSinkBoundaryRow{};
	row->sink_id = __latency_fn_llvm_text_from_plan_sink_id_llvm_text_id();
	row->sink_kind_id = __latency_fn_llvm_text_from_plan_sink_kind_llvm_text_id();
	row->status_id = __latency_fn_llvm_text_from_plan_sink_status_from_emission(emission);
	return row;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
FunctionBodyTextEmissionPreflightArtifact __latency_fn_llvm_text_from_plan_new_preflight_artifact_for_source(int_t<> rowCapacity, int_t<std::uint16_t> sourceModelId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::new_preflight_artifact_for_source", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[20]);
	FunctionBodyTextEmissionPreflightArtifact artifact = FunctionBodyTextEmissionPreflightArtifact{};
	artifact->artifact_kind_id = __latency_fn_llvm_text_from_plan_artifact_kind_function_body_text_preflight_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	artifact->source_model_id = sourceModelId;
	artifact->preflight_model_id = __latency_fn_llvm_text_from_plan_preflight_model_llvm_text_sink_id();
	php::vector_reserve(artifact->rows, rowCapacity);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
FunctionBodyTextEmissionPreflightArtifact __latency_fn_llvm_text_from_plan_new_preflight_artifact(int_t<> rowCapacity) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::new_preflight_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[21]);
	return __latency_fn_llvm_text_from_plan_new_preflight_artifact_for_source(rowCapacity, __latency_fn_llvm_text_from_plan_source_model_lowering_plan_id());
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
FunctionBodyTextEmissionPreflightRow __latency_fn_llvm_text_from_plan_preflight_row_from_plan(int_t<std::uint32_t> preflightId, shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::preflight_row_from_plan", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[22]);
	BackendEmissionDecisionArtifact emission = __latency_fn_backend_emission_decisions_from_lowering_plan(plan);
	return __latency_fn_llvm_text_from_plan_preflight_row_from_emission(cast<int_t<std::uint32_t>>(preflightId), emission);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
FunctionBodyTextEmissionPreflightRow __latency_fn_llvm_text_from_plan_preflight_row_from_emission(int_t<std::uint32_t> preflightId, BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::preflight_row_from_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[23]);
	int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_llvm_text_from_plan_sink_status_from_emission(emission));
	FunctionBodyTextEmissionPreflightRow row = FunctionBodyTextEmissionPreflightRow{};
	row->preflight_id = preflightId;
	row->symbol_id = emission->owner_symbol_id;
	row->source_unit_id = emission->owner_source_unit_id;
	row->body_readiness_status_id = statusId;
	row->text_sink_policy_id = __latency_fn_llvm_text_from_plan_text_sink_policy_llvm_text_id();
	row->emission_order_status_id = statusId;
	row->status_id = statusId;
	row->body_readiness_lowering_lookup_status_id = statusId;
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_llvm_text_from_plan_status_ready_id())))) {
		row->body_readiness_reason_id = __latency_fn_llvm_text_from_plan_blocked_reason_none_id();
		row->blocked_reason_id = __latency_fn_llvm_text_from_plan_blocked_reason_none_id();
		row->next_step_id = __latency_fn_structure_row_ids_none_kind_id();
		row->body_readiness_lowering_blocked_reason_id = __latency_fn_llvm_text_from_plan_blocked_reason_none_id();
		return row;
	}
	row->body_readiness_reason_id = __latency_fn_llvm_text_from_plan_blocked_reason_lowering_plan_blocked_id();
	row->blocked_reason_id = __latency_fn_llvm_text_from_plan_blocked_reason_lowering_plan_blocked_id();
	row->next_step_id = __latency_fn_llvm_text_from_plan_next_step_restore_ready_lowering_steps_id();
	row->body_readiness_lowering_blocked_reason_id = __latency_fn_llvm_text_from_plan_blocked_reason_lowering_plan_blocked_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_preflight(FunctionBodyTextEmissionPreflightArtifact& artifact, FunctionBodyTextEmissionPreflightRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_preflight", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[24]);
	row->preflight_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->rows));
	(void) artifact->rows.append(row);
	artifact->row_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->rows));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_llvm_text_from_plan_status_ready_id())))) {
		artifact->ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->ready_count) + static_cast<int_t<> >(1)));
	}
	else {
		artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
FunctionBodyTextEmissionPreflightArtifact __latency_fn_llvm_text_from_plan_preflight_from_plan(shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::preflight_from_plan", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[25]);
	FunctionBodyTextEmissionPreflightArtifact artifact = __latency_fn_llvm_text_from_plan_new_preflight_artifact(static_cast<int_t<> >(1));
	FunctionBodyTextEmissionPreflightRow row = __latency_fn_llvm_text_from_plan_preflight_row_from_plan(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), plan);
	__latency_fn_llvm_text_from_plan_append_preflight(artifact, row);
	return artifact;
}

}
