#include <scpp/lang/php.hpp>
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendLoopTextEmissionState.hpp"
#include "__types/backend_loop_text_emission.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_empty_state.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_reset.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_begin_while.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_reset.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_is_break.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_is_continue.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_while_condition_branch_text.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_begin_for.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_reset.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_is_break.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_is_continue.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_for_condition_branch_text.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_append_end_after_close_source.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_reset.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_is_break.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_is_continue.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_none_id.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_append_exit_without_back_edge.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_reset.hpp"
namespace scpp { extern const int __latency_lines_backend_loop_text_emission[]; }
namespace scpp {
bool_t backend_loop_text_emission::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == backend_loop_text_emission::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_backend_loop_text_emission[]; }
namespace scpp {
shared_p<BackendLoopTextEmissionState> __latency_fn_backend_loop_text_emission_empty_state() {
	SCPP_CALL_DEPTH_GUARD("backend_loop_text_emission::empty_state", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_loop_text_emission.phs", __latency_lines_backend_loop_text_emission[0]);
	return create<BackendLoopTextEmissionState>();
}

}

namespace scpp { extern const int __latency_lines_backend_loop_text_emission[]; }
namespace scpp {
void __latency_fn_backend_loop_text_emission_reset(shared_p<BackendLoopTextEmissionState>& state) {
	SCPP_CALL_DEPTH_GUARD("backend_loop_text_emission::reset", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_loop_text_emission.phs", __latency_lines_backend_loop_text_emission[1]);
	state->condition_label = string_t("");
	state->continue_label = string_t("");
	state->exit_label = string_t("");
	state->close_after_source_row_id = __latency_fn_structure_row_ids_none_id();
	state->continue_close_after_source_row_id = __latency_fn_structure_row_ids_none_id();
	state->terminator_kind_id = __latency_fn_backend_preflight_requests_control_flow_terminator_none_id();
}

}

namespace scpp { extern const int __latency_lines_backend_loop_text_emission[]; }
namespace scpp {
void __latency_fn_backend_loop_text_emission_begin_while(shared_p<BackendLoopTextEmissionState>& state, str::text_builder& lines, BackendControlFlowOperandRow controlFlowOperand, int_t<> valueId, vector_t<int_t<std::uint32_t>>& localSourceRows) {
	SCPP_CALL_DEPTH_GUARD("backend_loop_text_emission::begin_while", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_loop_text_emission.phs", __latency_lines_backend_loop_text_emission[2]);
	__latency_fn_llvm_text_from_plan_append_while_condition_branch_text(lines, controlFlowOperand, valueId, localSourceRows);
	if (static_cast<bool>((((cast<int_t<>>(controlFlowOperand->body_terminator_source_row_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(controlFlowOperand->body_terminator_source_row_id), cast<int_t<>>(controlFlowOperand->body_last_source_row_id))) && __latency_fn_backend_preflight_requests_control_flow_terminator_is_break(controlFlowOperand->body_terminator_kind_id)))) {
		str::text_builder_append_string(lines, (string_t("  br label %while_exit_") + cast<string_t>(valueId) + string_t("\n")));
		str::text_builder_append_string(lines, (string_t("while_exit_") + cast<string_t>(valueId) + string_t(":\n")));
		__latency_fn_backend_loop_text_emission_reset(state);
		return;
	}
	if (static_cast<bool>((((cast<int_t<>>(controlFlowOperand->body_terminator_source_row_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(controlFlowOperand->body_terminator_source_row_id), cast<int_t<>>(controlFlowOperand->body_last_source_row_id))) && __latency_fn_backend_preflight_requests_control_flow_terminator_is_continue(controlFlowOperand->body_terminator_kind_id)))) {
		str::text_builder_append_string(lines, (string_t("  br label %while_condition_") + cast<string_t>(valueId) + string_t("\n")));
		str::text_builder_append_string(lines, (string_t("while_exit_") + cast<string_t>(valueId) + string_t(":\n")));
		__latency_fn_backend_loop_text_emission_reset(state);
		return;
	}
	state->condition_label = (string_t("while_condition_") + cast<string_t>(valueId));
	state->continue_label = (string_t("while_condition_") + cast<string_t>(valueId));
	state->exit_label = (string_t("while_exit_") + cast<string_t>(valueId));
	state->close_after_source_row_id = controlFlowOperand->body_last_source_row_id;
	state->terminator_kind_id = controlFlowOperand->body_terminator_kind_id;
}

}

namespace scpp { extern const int __latency_lines_backend_loop_text_emission[]; }
namespace scpp {
void __latency_fn_backend_loop_text_emission_begin_for(shared_p<BackendLoopTextEmissionState>& state, str::text_builder& lines, BackendControlFlowOperandRow controlFlowOperand, int_t<> valueId, vector_t<int_t<std::uint32_t>>& localSourceRows) {
	SCPP_CALL_DEPTH_GUARD("backend_loop_text_emission::begin_for", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_loop_text_emission.phs", __latency_lines_backend_loop_text_emission[3]);
	__latency_fn_llvm_text_from_plan_append_for_condition_branch_text(lines, controlFlowOperand, valueId, localSourceRows);
	if (static_cast<bool>((((cast<int_t<>>(controlFlowOperand->body_terminator_source_row_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(controlFlowOperand->body_terminator_source_row_id), cast<int_t<>>(controlFlowOperand->body_last_source_row_id))) && __latency_fn_backend_preflight_requests_control_flow_terminator_is_break(controlFlowOperand->body_terminator_kind_id)))) {
		str::text_builder_append_string(lines, (string_t("  br label %for_exit_") + cast<string_t>(valueId) + string_t("\n")));
		str::text_builder_append_string(lines, (string_t("for_exit_") + cast<string_t>(valueId) + string_t(":\n")));
		__latency_fn_backend_loop_text_emission_reset(state);
		return;
	}
	if (static_cast<bool>((((cast<int_t<>>(controlFlowOperand->body_terminator_source_row_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(controlFlowOperand->body_terminator_source_row_id), cast<int_t<>>(controlFlowOperand->body_last_source_row_id))) && __latency_fn_backend_preflight_requests_control_flow_terminator_is_continue(controlFlowOperand->body_terminator_kind_id)))) {
		str::text_builder_append_string(lines, (string_t("  br label %for_update_") + cast<string_t>(valueId) + string_t("\n")));
		str::text_builder_append_string(lines, (string_t("for_update_") + cast<string_t>(valueId) + string_t(":\n")));
	}
	state->condition_label = (string_t("for_condition_") + cast<string_t>(valueId));
	state->continue_label = (string_t("for_update_") + cast<string_t>(valueId));
	state->exit_label = (string_t("for_exit_") + cast<string_t>(valueId));
	state->close_after_source_row_id = controlFlowOperand->else_body_last_source_row_id;
	if (static_cast<bool>(((cast<int_t<>>(controlFlowOperand->body_terminator_source_row_id) > static_cast<int_t<> >(0)) && php::not_identical(cast<int_t<>>(controlFlowOperand->body_terminator_source_row_id), cast<int_t<>>(controlFlowOperand->body_last_source_row_id))))) {
		state->close_after_source_row_id = controlFlowOperand->body_last_source_row_id;
		state->continue_close_after_source_row_id = controlFlowOperand->else_body_last_source_row_id;
		state->terminator_kind_id = controlFlowOperand->body_terminator_kind_id;
	}
}

}

namespace scpp { extern const int __latency_lines_backend_loop_text_emission[]; }
namespace scpp {
void __latency_fn_backend_loop_text_emission_append_end_after_close_source(str::text_builder& lines, shared_p<BackendLoopTextEmissionState>& state, int_t<std::uint32_t> currentSourceRowId) {
	SCPP_CALL_DEPTH_GUARD("backend_loop_text_emission::append_end_after_close_source", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_loop_text_emission.phs", __latency_lines_backend_loop_text_emission[4]);
	if (static_cast<bool>(((((php::identical(state->condition_label, string_t("")) || php::identical(state->exit_label, string_t(""))) || php::identical(cast<int_t<>>(currentSourceRowId), static_cast<int_t<> >(0))) || php::identical(cast<int_t<>>(state->close_after_source_row_id), static_cast<int_t<> >(0))) || php::not_identical(cast<int_t<>>(currentSourceRowId), cast<int_t<>>(state->close_after_source_row_id))))) {
		return;
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_backend_preflight_requests_control_flow_terminator_is_break(state->terminator_kind_id)))) {
		str::text_builder_append_string(lines, (string_t("  br label %") + cast<string_t>(state->exit_label) + string_t("\n")));
		str::text_builder_append_string(lines, (cast<string_t>(state->exit_label) + string_t(":\n")));
		__latency_fn_backend_loop_text_emission_reset(state);
		return;
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_backend_preflight_requests_control_flow_terminator_is_continue(state->terminator_kind_id)))) {
		str::text_builder_append_string(lines, (string_t("  br label %") + cast<string_t>(state->continue_label) + string_t("\n")));
		if (static_cast<bool>(php::condition_truthy(php::not_identical(state->continue_label, state->condition_label)))) {
			str::text_builder_append_string(lines, (cast<string_t>(state->continue_label) + string_t(":\n")));
			state->close_after_source_row_id = state->continue_close_after_source_row_id;
			state->terminator_kind_id = __latency_fn_backend_preflight_requests_control_flow_terminator_none_id();
		}
		else {
			str::text_builder_append_string(lines, (cast<string_t>(state->exit_label) + string_t(":\n")));
		}
		if (static_cast<bool>(php::identical(state->continue_label, state->condition_label))) {
			__latency_fn_backend_loop_text_emission_reset(state);
		}
		return;
	}
	str::text_builder_append_string(lines, (string_t("  br label %") + cast<string_t>(state->condition_label) + string_t("\n")));
	str::text_builder_append_string(lines, (cast<string_t>(state->exit_label) + string_t(":\n")));
	__latency_fn_backend_loop_text_emission_reset(state);
}

}

namespace scpp { extern const int __latency_lines_backend_loop_text_emission[]; }
namespace scpp {
void __latency_fn_backend_loop_text_emission_append_exit_without_back_edge(str::text_builder& lines, shared_p<BackendLoopTextEmissionState>& state) {
	SCPP_CALL_DEPTH_GUARD("backend_loop_text_emission::append_exit_without_back_edge", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_loop_text_emission.phs", __latency_lines_backend_loop_text_emission[5]);
	if (static_cast<bool>(php::identical(state->exit_label, string_t("")))) {
		return;
	}
	str::text_builder_append_string(lines, (cast<string_t>(state->exit_label) + string_t(":\n")));
	__latency_fn_backend_loop_text_emission_reset(state);
}

}
