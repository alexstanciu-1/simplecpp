#include <scpp/lang/php.hpp>
#include "__types/TokenExtendedLengthRow.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_token_tables_extended_length_sentinel_int.hpp"
#include "__callable/__latency_fn_token_tables_extended_length_sentinel.hpp"
#include "__callable/__latency_fn_token_tables_extended_length_sentinel_int.hpp"
#include "__callable/__latency_fn_token_tables_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_tables_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_tables_with_flag.hpp"
#include "__callable/__latency_fn_token_tables_has_flag.hpp"
#include "__callable/__latency_fn_token_tables_flag_has_newline_before.hpp"
#include "__callable/__latency_fn_token_tables_flag_has_whitespace_before.hpp"
#include "__callable/__latency_fn_token_tables_make_trivia_flags.hpp"
#include "__callable/__latency_fn_token_tables_no_flags.hpp"
#include "__callable/__latency_fn_token_tables_with_flag.hpp"
#include "__callable/__latency_fn_token_tables_flag_has_whitespace_before.hpp"
#include "__callable/__latency_fn_token_tables_has_flag.hpp"
#include "__callable/__latency_fn_token_tables_has_whitespace_before.hpp"
#include "__callable/__latency_fn_token_tables_flag_has_newline_before.hpp"
#include "__callable/__latency_fn_token_tables_has_flag.hpp"
#include "__callable/__latency_fn_token_tables_has_newline_before.hpp"
#include "__callable/__latency_fn_token_tables_flag_extended_length.hpp"
#include "__callable/__latency_fn_token_tables_has_extended_length.hpp"
#include "__callable/__latency_fn_token_tables_has_flag.hpp"
#include "__callable/__latency_fn_token_tables_extended_length_by_token_id.hpp"
#include "__callable/__latency_fn_token_tables_has_extended_length.hpp"
#include "__callable/__latency_fn_token_tables_resolved_length.hpp"
#include "__callable/__latency_fn_token_tables_token_by_id.hpp"
#include "__callable/__latency_fn_token_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_token_tables_end_offset.hpp"
#include "__callable/__latency_fn_token_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_token_tables_end_offset.hpp"
#include "__callable/__latency_fn_token_tables_leading_trivia_start_offset.hpp"
#include "__callable/__latency_fn_token_tables_leading_trivia_length.hpp"
#include "__callable/__latency_fn_token_tables_leading_trivia_start_offset.hpp"
#include "__callable/__latency_fn_token_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_token_tables_equals.hpp"
#include "__callable/__latency_fn_token_tables_stable_hash.hpp"
#include "__callable/__latency_fn_token_tables_debug_string.hpp"
namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<> __latency_fn_token_tables_extended_length_sentinel_int() {
	SCPP_CALL_DEPTH_GUARD("token_tables::extended_length_sentinel_int", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[29]);
	return static_cast<int_t<> >(65535);
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_tables_extended_length_sentinel() {
	SCPP_CALL_DEPTH_GUARD("token_tables::extended_length_sentinel", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[30]);
	return __latency_fn_token_tables_uint16_from_int(__latency_fn_token_tables_extended_length_sentinel_int());
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_tables_with_flag(int_t<std::uint16_t> flags, int_t<std::uint16_t> flag) {
	SCPP_CALL_DEPTH_GUARD("token_tables::with_flag", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[31]);
	return __latency_fn_token_tables_uint16_from_int((cast<int_t<>>(flags) | cast<int_t<>>(flag)));
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
bool_t __latency_fn_token_tables_has_flag(int_t<std::uint16_t> flags, int_t<std::uint16_t> flag) {
	SCPP_CALL_DEPTH_GUARD("token_tables::has_flag", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[32]);
	return bool_t(php::not_identical((cast<int_t<>>(flags) & cast<int_t<>>(flag)), static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_tables_make_trivia_flags(bool_t hasWhitespaceBefore, bool_t hasNewlineBefore) {
	SCPP_CALL_DEPTH_GUARD("token_tables::make_trivia_flags", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[33]);
	int_t<std::uint16_t> flags = required_cast<int_t<std::uint16_t>>(__latency_fn_token_tables_no_flags());
	if (static_cast<bool>(php::condition_truthy(hasWhitespaceBefore))) {
		flags = __latency_fn_token_tables_with_flag(cast<int_t<std::uint16_t>>(flags), __latency_fn_token_tables_flag_has_whitespace_before());
	}
	if (static_cast<bool>(php::condition_truthy(hasNewlineBefore))) {
		flags = __latency_fn_token_tables_with_flag(cast<int_t<std::uint16_t>>(flags), __latency_fn_token_tables_flag_has_newline_before());
	}
	return cast<int_t<std::uint16_t>>(flags);
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
bool_t __latency_fn_token_tables_has_whitespace_before(TokenRow row) {
	SCPP_CALL_DEPTH_GUARD("token_tables::has_whitespace_before", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[34]);
	return __latency_fn_token_tables_has_flag(row->flags, __latency_fn_token_tables_flag_has_whitespace_before());
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
bool_t __latency_fn_token_tables_has_newline_before(TokenRow row) {
	SCPP_CALL_DEPTH_GUARD("token_tables::has_newline_before", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[35]);
	return __latency_fn_token_tables_has_flag(row->flags, __latency_fn_token_tables_flag_has_newline_before());
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
bool_t __latency_fn_token_tables_has_extended_length(TokenRow row) {
	SCPP_CALL_DEPTH_GUARD("token_tables::has_extended_length", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[36]);
	return __latency_fn_token_tables_has_flag(row->flags, __latency_fn_token_tables_flag_extended_length());
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_token_tables_resolved_length(shared_p<TokenStream> stream, int_t<std::uint32_t> tokenId) {
	SCPP_CALL_DEPTH_GUARD("token_tables::resolved_length", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[37]);
	TokenRow row = __latency_fn_token_tables_token_by_id(stream, cast<int_t<std::uint32_t>>(tokenId));
	if (static_cast<bool>((!__latency_fn_token_tables_has_extended_length(row)))) {
		return __latency_fn_token_tables_uint32_from_int(cast<int_t<>>(row->length));
	}
	TokenExtendedLengthRow ext = __latency_fn_token_tables_extended_length_by_token_id(stream, cast<int_t<std::uint32_t>>(tokenId));
	return ext->length;
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_token_tables_end_offset(TokenRow row) {
	SCPP_CALL_DEPTH_GUARD("token_tables::end_offset", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[38]);
	return __latency_fn_token_tables_uint32_from_int((cast<int_t<>>(row->start_offset) + cast<int_t<>>(row->length)));
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_token_tables_leading_trivia_start_offset(TokenRow previous, TokenRow current) {
	SCPP_CALL_DEPTH_GUARD("token_tables::leading_trivia_start_offset", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[39]);
	return __latency_fn_token_tables_end_offset(previous);
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_token_tables_leading_trivia_length(TokenRow previous, TokenRow current) {
	SCPP_CALL_DEPTH_GUARD("token_tables::leading_trivia_length", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[40]);
	int_t<> start = required_cast<int_t<>>(cast<int_t<>>(__latency_fn_token_tables_leading_trivia_start_offset(previous, current)));
	int_t<> currentStart = required_cast<int_t<>>(cast<int_t<>>(current->start_offset));
	if (static_cast<bool>((currentStart > start))) {
		return __latency_fn_token_tables_uint32_from_int((currentStart - start));
	}
	return __latency_fn_token_tables_uint32_from_int(static_cast<int_t<> >(0));
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
bool_t __latency_fn_token_tables_equals(TokenRow left, TokenRow right) {
	SCPP_CALL_DEPTH_GUARD("token_tables::equals", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[41]);
	return (((php::identical(cast<int_t<>>(left->kind_id), cast<int_t<>>(right->kind_id)) && php::identical(cast<int_t<>>(left->start_offset), cast<int_t<>>(right->start_offset))) && php::identical(cast<int_t<>>(left->length), cast<int_t<>>(right->length))) && php::identical(cast<int_t<>>(left->flags), cast<int_t<>>(right->flags)));
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_token_tables_stable_hash(TokenRow row) {
	SCPP_CALL_DEPTH_GUARD("token_tables::stable_hash", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[42]);
	string_t identity = required_cast<string_t>((string_t("token_row:v1:") + cast<string_t>(cast<int_t<>>(row->kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->start_offset)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->length)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->flags))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
string_t __latency_fn_token_tables_debug_string(TokenRow row) {
	SCPP_CALL_DEPTH_GUARD("token_tables::debug_string", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[43]);
	return (string_t("token:") + cast<string_t>(cast<int_t<>>(row->kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->start_offset)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->length)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->flags)));
}

}
