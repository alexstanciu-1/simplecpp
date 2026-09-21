#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendEmissionBlockRow.hpp"
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionDecisionRow.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/backend_emission_decisions.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_artifact_kind_backend_emission_decision_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_source_model_lowering_plan_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_emission_model_decision_value_block_rows_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_decision_kind_function_body_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_block_kind_function_body_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_return_value_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_echo_scalar_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_echo_string_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_storage_kind_immediate_value_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_storage_kind_runtime_opaque_value_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_blocked_reason_lowering_plan_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_blocked_reason_no_lowering_steps_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_artifact_kind_backend_emission_decision_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_emission_model_decision_value_block_rows_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_new_artifact_with_sidecar_capacity.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_source_model_lowering_plan_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
bool_t backend_emission_decisions::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == backend_emission_decisions::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_artifact_kind_backend_emission_decision_id() {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::artifact_kind_backend_emission_decision_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_source_model_lowering_plan_id() {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::source_model_lowering_plan_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_emission_model_decision_value_block_rows_id() {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::emission_model_decision_value_block_rows_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_decision_kind_function_body_id() {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::decision_kind_function_body_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_block_kind_function_body_id() {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::block_kind_function_body_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_value_role_return_value_id() {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::value_role_return_value_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_value_role_echo_scalar_id() {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::value_role_echo_scalar_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_value_role_echo_string_id() {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::value_role_echo_string_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_storage_kind_immediate_value_id() {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::storage_kind_immediate_value_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_storage_kind_runtime_opaque_value_id() {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::storage_kind_runtime_opaque_value_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[10]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[11]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[12]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_blocked_reason_lowering_plan_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::blocked_reason_lowering_plan_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[13]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_decisions_blocked_reason_no_lowering_steps_id() {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::blocked_reason_no_lowering_steps_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[14]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
BackendEmissionDecisionArtifact __latency_fn_backend_emission_decisions_new_artifact_with_sidecar_capacity(shared_p<LoweringPlan> plan, int_t<> decisionCapacity, int_t<> valueCapacity, int_t<> blockCapacity, int_t<> sidecarCapacity) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::new_artifact_with_sidecar_capacity", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[15]);
	BackendEmissionDecisionArtifact artifact = BackendEmissionDecisionArtifact{};
	artifact->artifact_kind_id = __latency_fn_backend_emission_decisions_artifact_kind_backend_emission_decision_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	artifact->source_model_id = __latency_fn_backend_emission_decisions_source_model_lowering_plan_id();
	artifact->emission_model_id = __latency_fn_backend_emission_decisions_emission_model_decision_value_block_rows_id();
	artifact->owner_source_unit_id = plan->owner_source_unit_id;
	artifact->owner_symbol_id = plan->owner_symbol_id;
	php::vector_reserve(artifact->decisions, decisionCapacity);
	php::vector_reserve(artifact->values, valueCapacity);
	php::vector_reserve(artifact->blocks, blockCapacity);
	php::vector_reserve(artifact->binary_operands, sidecarCapacity);
	php::vector_reserve(artifact->local_operands, sidecarCapacity);
	php::vector_reserve(artifact->call_arguments, sidecarCapacity);
	php::vector_reserve(artifact->control_flow_operands, sidecarCapacity);
	return artifact;
}

}
