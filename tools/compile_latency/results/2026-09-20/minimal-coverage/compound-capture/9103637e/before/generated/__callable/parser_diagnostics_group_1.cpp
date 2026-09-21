#include <scpp/lang/php.hpp>
#include "__types/ParserDiagnosticRow.hpp"
#include "__callable/__latency_fn_parser_diagnostics_reason_expected_token_id.hpp"
#include "__callable/__latency_fn_parser_diagnostics_reason_name.hpp"
#include "__callable/__latency_fn_parser_diagnostics_severity_error_id.hpp"
#include "__callable/__latency_fn_parser_diagnostics_severity_name.hpp"
#include "__callable/__latency_fn_parser_diagnostics_equals.hpp"
#include "__callable/__latency_fn_parser_diagnostics_stable_hash.hpp"
#include "__callable/__latency_fn_parser_diagnostics_debug_string.hpp"
#include "__callable/__latency_fn_parser_diagnostics_reason_name.hpp"
#include "__callable/__latency_fn_parser_diagnostics_severity_name.hpp"
namespace scpp { extern const int __latency_lines_parser_diagnostics[]; }
namespace scpp {
string_t __latency_fn_parser_diagnostics_reason_name(int_t<std::uint16_t> reasonId) {
	SCPP_CALL_DEPTH_GUARD("parser_diagnostics::reason_name", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_diagnostics.phs", __latency_lines_parser_diagnostics[11]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_parser_diagnostics_reason_expected_token_id())))) {
		return string_t("expected_token");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_parser_diagnostics[]; }
namespace scpp {
string_t __latency_fn_parser_diagnostics_severity_name(int_t<std::uint16_t> severityId) {
	SCPP_CALL_DEPTH_GUARD("parser_diagnostics::severity_name", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_diagnostics.phs", __latency_lines_parser_diagnostics[12]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(severityId), cast<int_t<>>(__latency_fn_parser_diagnostics_severity_error_id())))) {
		return string_t("error");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_parser_diagnostics[]; }
namespace scpp {
bool_t __latency_fn_parser_diagnostics_equals(ParserDiagnosticRow left, ParserDiagnosticRow right) {
	SCPP_CALL_DEPTH_GUARD("parser_diagnostics::equals", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_diagnostics.phs", __latency_lines_parser_diagnostics[13]);
	return (((((((php::identical(cast<int_t<>>(left->diagnostic_id), cast<int_t<>>(right->diagnostic_id)) && php::identical(cast<int_t<>>(left->source_unit_id), cast<int_t<>>(right->source_unit_id))) && php::identical(cast<int_t<>>(left->source_buffer_id), cast<int_t<>>(right->source_buffer_id))) && php::identical(cast<int_t<>>(left->token_id), cast<int_t<>>(right->token_id))) && php::identical(cast<int_t<>>(left->start_offset), cast<int_t<>>(right->start_offset))) && php::identical(cast<int_t<>>(left->length), cast<int_t<>>(right->length))) && php::identical(cast<int_t<>>(left->reason_id), cast<int_t<>>(right->reason_id))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id)));
}

}

namespace scpp { extern const int __latency_lines_parser_diagnostics[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_parser_diagnostics_stable_hash(ParserDiagnosticRow row) {
	SCPP_CALL_DEPTH_GUARD("parser_diagnostics::stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_diagnostics.phs", __latency_lines_parser_diagnostics[14]);
	string_t identity = required_cast<string_t>((string_t("parser_diagnostic:v1:") + cast<string_t>(cast<int_t<>>(row->diagnostic_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_buffer_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->token_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->start_offset)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->length)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->reason_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_parser_diagnostics[]; }
namespace scpp {
string_t __latency_fn_parser_diagnostics_debug_string(ParserDiagnosticRow row) {
	SCPP_CALL_DEPTH_GUARD("parser_diagnostics::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/parser_diagnostics.phs", __latency_lines_parser_diagnostics[15]);
	return (string_t("parser_diagnostic:") + cast<string_t>(cast<int_t<>>(row->diagnostic_id)) + string_t(":") + cast<string_t>(__latency_fn_parser_diagnostics_severity_name(row->severity_id)) + string_t(":") + cast<string_t>(__latency_fn_parser_diagnostics_reason_name(row->reason_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->token_id)));
}

}
