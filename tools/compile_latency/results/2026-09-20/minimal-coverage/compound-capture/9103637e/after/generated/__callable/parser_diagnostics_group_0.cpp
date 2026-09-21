#include <scpp/lang/php.hpp>
#include "__types/ParserDiagnosticRow.hpp"
#include "__types/ParserDiagnosticTable.hpp"
#include "__types/PhsParserCursor.hpp"
#include "__types/TokenRow.hpp"
#include "__types/parser_diagnostics.hpp"
#include "__callable/__latency_fn_parser_diagnostics_artifact_kind_parser_diagnostics_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_parser_diagnostics_phase_parser_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_parser_diagnostics_severity_error_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_parser_diagnostics_reason_expected_token_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_parser_diagnostics_status_active_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_parser_diagnostics_artifact_kind_parser_diagnostics_id.hpp"
#include "__callable/__latency_fn_parser_diagnostics_new_table.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_parser_diagnostics_token_id_from_cursor.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_parser_diagnostics_phase_parser_id.hpp"
#include "__callable/__latency_fn_parser_diagnostics_row_from_token.hpp"
#include "__callable/__latency_fn_parser_diagnostics_severity_error_id.hpp"
#include "__callable/__latency_fn_parser_diagnostics_status_active_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_parser_diagnostics_append_row.hpp"
#include "__callable/__latency_fn_parser_diagnostics_severity_error_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_parser_diagnostics_append_expected_token.hpp"
#include "__callable/__latency_fn_parser_diagnostics_append_row.hpp"
#include "__callable/__latency_fn_parser_diagnostics_reason_expected_token_id.hpp"
#include "__callable/__latency_fn_parser_diagnostics_row_from_token.hpp"
#include "__callable/__latency_fn_parser_diagnostics_token_id_from_cursor.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_parser_diagnostics_row_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_parser_diagnostics[]; }
namespace scpp {
bool_t parser_diagnostics::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == parser_diagnostics::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_parser_diagnostics[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_parser_diagnostics_artifact_kind_parser_diagnostics_id() {
	SCPP_CALL_DEPTH_GUARD("parser_diagnostics::artifact_kind_parser_diagnostics_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_diagnostics.phs", __latency_lines_parser_diagnostics[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_parser_diagnostics[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_parser_diagnostics_phase_parser_id() {
	SCPP_CALL_DEPTH_GUARD("parser_diagnostics::phase_parser_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_diagnostics.phs", __latency_lines_parser_diagnostics[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_parser_diagnostics[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_parser_diagnostics_severity_error_id() {
	SCPP_CALL_DEPTH_GUARD("parser_diagnostics::severity_error_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_diagnostics.phs", __latency_lines_parser_diagnostics[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_parser_diagnostics[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_parser_diagnostics_reason_expected_token_id() {
	SCPP_CALL_DEPTH_GUARD("parser_diagnostics::reason_expected_token_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_diagnostics.phs", __latency_lines_parser_diagnostics[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_parser_diagnostics[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_parser_diagnostics_status_active_id() {
	SCPP_CALL_DEPTH_GUARD("parser_diagnostics::status_active_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_diagnostics.phs", __latency_lines_parser_diagnostics[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_parser_diagnostics[]; }
namespace scpp {
shared_p<ParserDiagnosticTable> __latency_fn_parser_diagnostics_new_table(int_t<> capacity) {
	SCPP_CALL_DEPTH_GUARD("parser_diagnostics::new_table", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_diagnostics.phs", __latency_lines_parser_diagnostics[5]);
	shared_p<ParserDiagnosticTable> table = create<ParserDiagnosticTable>();
	table->artifact_kind_id = __latency_fn_parser_diagnostics_artifact_kind_parser_diagnostics_id();
	table->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	php::vector_reserve(table->rows, capacity);
	return table;
}

}

namespace scpp { extern const int __latency_lines_parser_diagnostics[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_parser_diagnostics_token_id_from_cursor(PhsParserCursor cursor) {
	SCPP_CALL_DEPTH_GUARD("parser_diagnostics::token_id_from_cursor", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_diagnostics.phs", __latency_lines_parser_diagnostics[6]);
	return __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(cursor->index) + static_cast<int_t<> >(1)));
}

}

namespace scpp { extern const int __latency_lines_parser_diagnostics[]; }
namespace scpp {
ParserDiagnosticRow __latency_fn_parser_diagnostics_row_from_token(int_t<std::uint32_t> diagnosticId, int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> sourceBufferId, int_t<std::uint32_t> tokenId, TokenRow token, int_t<std::uint16_t> reasonId, int_t<std::uint32_t> relatedRowId) {
	SCPP_CALL_DEPTH_GUARD("parser_diagnostics::row_from_token", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_diagnostics.phs", __latency_lines_parser_diagnostics[7]);
	ParserDiagnosticRow row = ParserDiagnosticRow{};
	row->diagnostic_id = diagnosticId;
	row->source_unit_id = sourceUnitId;
	row->source_buffer_id = sourceBufferId;
	row->token_id = tokenId;
	row->source_range_id = __latency_fn_structure_row_ids_none_id();
	row->start_offset = token->start_offset;
	row->length = token->length;
	row->phase_id = __latency_fn_parser_diagnostics_phase_parser_id();
	row->severity_id = __latency_fn_parser_diagnostics_severity_error_id();
	row->reason_id = reasonId;
	row->status_id = __latency_fn_parser_diagnostics_status_active_id();
	row->related_row_id = relatedRowId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_parser_diagnostics[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_parser_diagnostics_append_row(shared_p<ParserDiagnosticTable> table, ParserDiagnosticRow row) {
	SCPP_CALL_DEPTH_GUARD("parser_diagnostics::append_row", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_diagnostics.phs", __latency_lines_parser_diagnostics[8]);
	row->diagnostic_id = __latency_fn_structure_row_ids_next_dense_id(php::count(table->rows));
	(void) table->rows.append(row);
	table->row_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(table->rows));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->severity_id), cast<int_t<>>(__latency_fn_parser_diagnostics_severity_error_id())))) {
		table->error_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(table->error_count) + static_cast<int_t<> >(1)));
	}
	return row->diagnostic_id;
}

}

namespace scpp { extern const int __latency_lines_parser_diagnostics[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_parser_diagnostics_append_expected_token(shared_p<ParserDiagnosticTable> table, int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> sourceBufferId, PhsParserCursor cursor, TokenRow token) {
	SCPP_CALL_DEPTH_GUARD("parser_diagnostics::append_expected_token", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_diagnostics.phs", __latency_lines_parser_diagnostics[9]);
	ParserDiagnosticRow row = __latency_fn_parser_diagnostics_row_from_token(__latency_fn_structure_row_ids_none_id(), cast<int_t<std::uint32_t>>(sourceUnitId), cast<int_t<std::uint32_t>>(sourceBufferId), __latency_fn_parser_diagnostics_token_id_from_cursor(cursor), token, __latency_fn_parser_diagnostics_reason_expected_token_id(), __latency_fn_structure_row_ids_none_id());
	return __latency_fn_parser_diagnostics_append_row(table, row);
}

}

namespace scpp { extern const int __latency_lines_parser_diagnostics[]; }
namespace scpp {
ParserDiagnosticRow __latency_fn_parser_diagnostics_row_by_id(shared_p<ParserDiagnosticTable> table, int_t<std::uint32_t> diagnosticId) {
	SCPP_CALL_DEPTH_GUARD("parser_diagnostics::row_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_diagnostics.phs", __latency_lines_parser_diagnostics[10]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(diagnosticId, cast<int_t<>>(table->row_count))))) {
		ParserDiagnosticRow row = table->rows[__latency_fn_structure_row_ids_dense_index(diagnosticId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->diagnostic_id), cast<int_t<>>(diagnosticId)))) {
			return row;
		}
	}
	auto __latency_local_0 = table->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->diagnostic_id), cast<int_t<>>(diagnosticId)))) {
			return row;
		}
	}
	ParserDiagnosticRow empty = ParserDiagnosticRow{};
	return empty;
}

}
