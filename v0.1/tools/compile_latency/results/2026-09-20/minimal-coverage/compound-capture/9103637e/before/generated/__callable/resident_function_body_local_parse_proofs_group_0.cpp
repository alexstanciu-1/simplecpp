#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/PhsParserCursor.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/ResidentFunctionBodyLocalParseProofRow.hpp"
#include "__types/ResidentFunctionBodyParseSliceRow.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/TokenStream.hpp"
#include "__types/resident_function_body_local_parse_proofs.hpp"
#include "__types/resident_function_body_parse_slices.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_blocked_reason_missing_source_unit_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_blocked_reason_parser_diagnostic_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_blocked_reason_token_cursor_mismatch_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_has_parse_slices.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_expected_token_after_body.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_tables_reserve_model.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_reserve_local_model.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_blocked_row_from_slice.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_expected_token_after_body.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_status_blocked_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_statement_list_until_close.hpp"
#include "__callable/__latency_fn_frontend_model_builder_primitive_int_type_ref_id.hpp"
#include "__callable/__latency_fn_parser_state_make.hpp"
#include "__callable/__latency_fn_phs_tokenizer_from_source_text.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_build_local_model_from_slice.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_reserve_local_model.hpp"
#include "__callable/__latency_fn_source_units_row_by_id.hpp"
#include "__callable/__latency_fn_source_units_source_text_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_local_parse_proofs[]; }
namespace scpp {
bool_t resident_function_body_local_parse_proofs::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_function_body_local_parse_proofs::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_local_parse_proofs[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_local_parse_proofs_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_local_parse_proofs::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_local_parse_proofs.phs", __latency_lines_resident_function_body_local_parse_proofs[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_local_parse_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_local_parse_proofs_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_local_parse_proofs::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_local_parse_proofs.phs", __latency_lines_resident_function_body_local_parse_proofs[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_local_parse_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_local_parse_proofs_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_local_parse_proofs::status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_local_parse_proofs.phs", __latency_lines_resident_function_body_local_parse_proofs[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_local_parse_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_local_parse_proofs_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_local_parse_proofs::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_local_parse_proofs.phs", __latency_lines_resident_function_body_local_parse_proofs[3]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_local_parse_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_local_parse_proofs_blocked_reason_missing_source_unit_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_local_parse_proofs::blocked_reason_missing_source_unit_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_local_parse_proofs.phs", __latency_lines_resident_function_body_local_parse_proofs[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_local_parse_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_local_parse_proofs_blocked_reason_parser_diagnostic_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_local_parse_proofs::blocked_reason_parser_diagnostic_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_local_parse_proofs.phs", __latency_lines_resident_function_body_local_parse_proofs[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_local_parse_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_local_parse_proofs_blocked_reason_token_cursor_mismatch_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_local_parse_proofs::blocked_reason_token_cursor_mismatch_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_local_parse_proofs.phs", __latency_lines_resident_function_body_local_parse_proofs[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_local_parse_proofs[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_local_parse_proofs_has_parse_slices(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_local_parse_proofs::has_parse_slices", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_local_parse_proofs.phs", __latency_lines_resident_function_body_local_parse_proofs[7]);
	auto __latency_local_0 = report->resident_function_body_parse_slices;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto slice = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(slice->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_local_parse_proofs[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_local_parse_proofs_expected_token_after_body(ResidentFunctionBodyParseSliceRow slice) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_local_parse_proofs::expected_token_after_body", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_local_parse_proofs.phs", __latency_lines_resident_function_body_local_parse_proofs[8]);
	return __latency_fn_resident_function_body_local_parse_proofs_uint32_from_int((cast<int_t<>>(slice->token_start_index) + cast<int_t<>>(slice->token_count)));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_local_parse_proofs[]; }
namespace scpp {
void __latency_fn_resident_function_body_local_parse_proofs_reserve_local_model(shared_p<FrontendModel> model, ResidentFunctionBodyParseSliceRow slice, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_local_parse_proofs::reserve_local_model", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_local_parse_proofs.phs", __latency_lines_resident_function_body_local_parse_proofs[9]);
	int_t<> tokenCount = required_cast<int_t<>>(cast<int_t<>>(slice->token_count));
	int_t<> nodeCapacity = required_cast<int_t<>>((tokenCount + static_cast<int_t<> >(8)));
	if (static_cast<bool>((nodeCapacity < static_cast<int_t<> >(8)))) {
		nodeCapacity = static_cast<int_t<> >(8);
	}
	__latency_fn_frontend_model_tables_reserve_model(model, static_cast<int_t<> >(0), (tokenCount + static_cast<int_t<> >(8)), static_cast<int_t<> >(0), static_cast<int_t<> >(0), nodeCapacity, static_cast<int_t<> >(0), (tokenCount + static_cast<int_t<> >(4)), (tokenCount + static_cast<int_t<> >(4)), static_cast<int_t<> >(0), (tokenCount + static_cast<int_t<> >(4)), (tokenCount + static_cast<int_t<> >(4)), counters);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_local_parse_proofs[]; }
namespace scpp {
ResidentFunctionBodyLocalParseProofRow __latency_fn_resident_function_body_local_parse_proofs_blocked_row_from_slice(ResidentFunctionBodyParseSliceRow slice, int_t<std::uint16_t> blockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_local_parse_proofs::blocked_row_from_slice", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_local_parse_proofs.phs", __latency_lines_resident_function_body_local_parse_proofs[10]);
	ResidentFunctionBodyLocalParseProofRow row = ResidentFunctionBodyLocalParseProofRow{};
	row->owner_run_id = slice->owner_run_id;
	row->parse_slice_id = slice->slice_id;
	row->function_body_work_decision_id = slice->function_body_work_decision_id;
	row->current_function_body_snapshot_id = slice->current_function_body_snapshot_id;
	row->source_unit_id = slice->source_unit_id;
	row->symbol_id = slice->symbol_id;
	row->body_start_offset = slice->body_start_offset;
	row->body_end_offset = slice->body_end_offset;
	row->token_start_index = slice->token_start_index;
	row->expected_token_after_body_index = __latency_fn_resident_function_body_local_parse_proofs_expected_token_after_body(slice);
	row->status_id = __latency_fn_resident_function_body_local_parse_proofs_status_blocked_id();
	row->blocked_reason_id = blockedReasonId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_local_parse_proofs[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_local_parse_proofs_build_local_model_from_slice(shared_p<SourceUnitTable> sourceUnits, ResidentFunctionBodyParseSliceRow slice, shared_p<FrontendModel> model, int_t<std::uint32_t>& actualTokenAfterBodyIndex, int_t<std::uint32_t>& parserDiagnosticCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_local_parse_proofs::build_local_model_from_slice", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_local_parse_proofs.phs", __latency_lines_resident_function_body_local_parse_proofs[11]);
	SourceUnitTableRow sourceUnit = __latency_fn_source_units_row_by_id(sourceUnits, slice->source_unit_id);
	if (static_cast<bool>(php::identical(cast<int_t<>>(sourceUnit->source_unit_id), static_cast<int_t<> >(0)))) {
		actualTokenAfterBodyIndex = __latency_fn_structure_row_ids_none_id();
		parserDiagnosticCount = __latency_fn_structure_row_ids_none_id();
		return bool_t(static_cast<bool_t>(false));
	}
	string_t sourceText = required_cast<string_t>(__latency_fn_source_units_source_text_by_id(sourceUnits, slice->source_unit_id));
	shared_p<TokenStream> tokens = __latency_fn_phs_tokenizer_from_source_text(slice->source_unit_id, __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), sourceText);
	shared_p<PhsParserState> state = __latency_fn_parser_state_make(sourceUnit, __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), tokens);
	state->cursor->index = slice->token_start_index;
	model->source_unit_id = slice->source_unit_id;
	model->source_buffer_id = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	__latency_fn_resident_function_body_local_parse_proofs_reserve_local_model(model, slice, counters);
	__latency_fn_frontend_model_builder_parse_statement_list_until_close(state, sourceText, model, __latency_fn_structure_row_ids_none_id(), static_cast<bool_t>(true), __latency_fn_frontend_model_builder_primitive_int_type_ref_id(), counters);
	actualTokenAfterBodyIndex = state->cursor->index;
	parserDiagnosticCount = state->diagnostic_count;
	return bool_t(static_cast<bool_t>(true));
}

}
