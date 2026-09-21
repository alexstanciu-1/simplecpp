#include <scpp/lang/php.hpp>
#include "__types/TokenListRef.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenRowList.hpp"
#include "__types/TokenRowSpan.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_token_tables_segment_count.hpp"
#include "__callable/__latency_fn_token_row_lists_reserved_segment_bytes.hpp"
#include "__callable/__latency_fn_token_tables_reserved_segment_bytes.hpp"
#include "__callable/__latency_fn_token_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_token_row_lists_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_token_tables_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_token_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_token_row_lists_retained_old_generation_bytes.hpp"
#include "__callable/__latency_fn_token_tables_retained_old_generation_bytes.hpp"
#include "__callable/__latency_fn_token_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_token_row_lists_list_ref.hpp"
#include "__callable/__latency_fn_token_tables_list_ref.hpp"
#include "__callable/__latency_fn_token_row_lists_first_span.hpp"
#include "__callable/__latency_fn_token_tables_first_span.hpp"
#include "__callable/__latency_fn_token_row_lists_next_span.hpp"
#include "__callable/__latency_fn_token_tables_next_span.hpp"
#include "__callable/__latency_fn_token_row_lists_span_is_empty.hpp"
#include "__callable/__latency_fn_token_tables_span_is_empty.hpp"
#include "__callable/__latency_fn_token_row_lists_span_token_at.hpp"
#include "__callable/__latency_fn_token_tables_span_token_at.hpp"
#include "__callable/__latency_fn_token_tables_no_flags.hpp"
#include "__callable/__latency_fn_token_tables_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_tables_substrate_local_scanner_id.hpp"
#include "__callable/__latency_fn_token_tables_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_tables_substrate_runtime_token_buffer_id.hpp"
#include "__callable/__latency_fn_token_tables_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_tables_substrate_local_scanner_id.hpp"
#include "__callable/__latency_fn_token_tables_substrate_name.hpp"
#include "__callable/__latency_fn_token_tables_substrate_runtime_token_buffer_id.hpp"
#include "__callable/__latency_fn_token_tables_flag_has_whitespace_before.hpp"
#include "__callable/__latency_fn_token_tables_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_tables_flag_has_newline_before.hpp"
#include "__callable/__latency_fn_token_tables_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_tables_flag_extended_length.hpp"
#include "__callable/__latency_fn_token_tables_uint16_from_int.hpp"
namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_token_tables_segment_count(shared_p<TokenStream> stream) {
	SCPP_CALL_DEPTH_GUARD("token_tables::segment_count", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[13]);
	return stream->token_rows->segment_count;
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_token_tables_reserved_segment_bytes(shared_p<TokenStream> stream) {
	SCPP_CALL_DEPTH_GUARD("token_tables::reserved_segment_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[14]);
	return __latency_fn_token_tables_uint32_from_int(__latency_fn_token_row_lists_reserved_segment_bytes(stream->token_rows));
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_token_tables_segment_slack_bytes(shared_p<TokenStream> stream) {
	SCPP_CALL_DEPTH_GUARD("token_tables::segment_slack_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[15]);
	return __latency_fn_token_tables_uint32_from_int(__latency_fn_token_row_lists_segment_slack_bytes(stream->token_rows));
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_token_tables_retained_old_generation_bytes(shared_p<TokenStream> stream) {
	SCPP_CALL_DEPTH_GUARD("token_tables::retained_old_generation_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[16]);
	return __latency_fn_token_tables_uint32_from_int(__latency_fn_token_row_lists_retained_old_generation_bytes(stream->token_rows));
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
TokenListRef __latency_fn_token_tables_list_ref(shared_p<TokenStream> stream) {
	SCPP_CALL_DEPTH_GUARD("token_tables::list_ref", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[17]);
	return __latency_fn_token_row_lists_list_ref(stream->token_rows, stream->source_unit_id);
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
TokenRowSpan __latency_fn_token_tables_first_span(shared_p<TokenStream> stream) {
	SCPP_CALL_DEPTH_GUARD("token_tables::first_span", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[18]);
	return __latency_fn_token_row_lists_first_span(stream->token_rows);
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
TokenRowSpan __latency_fn_token_tables_next_span(shared_p<TokenStream> stream, TokenRowSpan span) {
	SCPP_CALL_DEPTH_GUARD("token_tables::next_span", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[19]);
	return __latency_fn_token_row_lists_next_span(stream->token_rows, span);
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
bool_t __latency_fn_token_tables_span_is_empty(TokenRowSpan span) {
	SCPP_CALL_DEPTH_GUARD("token_tables::span_is_empty", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[20]);
	return __latency_fn_token_row_lists_span_is_empty(span);
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
TokenRow __latency_fn_token_tables_span_token_at(shared_p<TokenStream> stream, TokenRowSpan span, int_t<> offset) {
	SCPP_CALL_DEPTH_GUARD("token_tables::span_token_at", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[21]);
	return __latency_fn_token_row_lists_span_token_at(stream->token_rows, span, offset);
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_tables_no_flags() {
	SCPP_CALL_DEPTH_GUARD("token_tables::no_flags", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[22]);
	return __latency_fn_token_tables_uint16_from_int(static_cast<int_t<> >(0));
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_tables_substrate_local_scanner_id() {
	SCPP_CALL_DEPTH_GUARD("token_tables::substrate_local_scanner_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[23]);
	return __latency_fn_token_tables_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_tables_substrate_runtime_token_buffer_id() {
	SCPP_CALL_DEPTH_GUARD("token_tables::substrate_runtime_token_buffer_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[24]);
	return __latency_fn_token_tables_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
string_t __latency_fn_token_tables_substrate_name(int_t<std::uint16_t> substrateId) {
	SCPP_CALL_DEPTH_GUARD("token_tables::substrate_name", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[25]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(substrateId), cast<int_t<>>(__latency_fn_token_tables_substrate_runtime_token_buffer_id())))) {
		return string_t("runtime_token_buffer");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(substrateId), cast<int_t<>>(__latency_fn_token_tables_substrate_local_scanner_id())))) {
		return string_t("local_scanner");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_tables_flag_has_whitespace_before() {
	SCPP_CALL_DEPTH_GUARD("token_tables::flag_has_whitespace_before", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[26]);
	return __latency_fn_token_tables_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_tables_flag_has_newline_before() {
	SCPP_CALL_DEPTH_GUARD("token_tables::flag_has_newline_before", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[27]);
	return __latency_fn_token_tables_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_tables_flag_extended_length() {
	SCPP_CALL_DEPTH_GUARD("token_tables::flag_extended_length", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[28]);
	return __latency_fn_token_tables_uint16_from_int(static_cast<int_t<> >(4));
}

}
