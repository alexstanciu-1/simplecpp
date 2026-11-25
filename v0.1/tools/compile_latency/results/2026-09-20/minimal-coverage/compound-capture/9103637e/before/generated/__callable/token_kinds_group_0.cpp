#include <scpp/lang/php.hpp>
#include "__types/token_kinds.hpp"
#include "__callable/__latency_fn_token_kinds_error.hpp"
#include "__callable/__latency_fn_token_kinds_unknown.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_token_kinds_eof.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_kinds_number.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_kinds_string_literal.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_kinds_comment.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_kinds_error.hpp"
#include "__callable/__latency_fn_token_kinds_comment.hpp"
#include "__callable/__latency_fn_token_kinds_eof.hpp"
#include "__callable/__latency_fn_token_kinds_error.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_token_kinds_name.hpp"
#include "__callable/__latency_fn_token_kinds_number.hpp"
#include "__callable/__latency_fn_token_kinds_string_literal.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
namespace scpp { extern const int __latency_lines_token_kinds[]; }
namespace scpp {
bool_t token_kinds::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == token_kinds::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_token_kinds[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_kinds_unknown() {
	SCPP_CALL_DEPTH_GUARD("token_kinds::unknown", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/token_kinds.phs", __latency_lines_token_kinds[0]);
	return __latency_fn_token_kinds_error();
}

}

namespace scpp { extern const int __latency_lines_token_kinds[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_kinds_eof() {
	SCPP_CALL_DEPTH_GUARD("token_kinds::eof", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/token_kinds.phs", __latency_lines_token_kinds[1]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_token_kinds[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_kinds_identifier() {
	SCPP_CALL_DEPTH_GUARD("token_kinds::identifier", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/token_kinds.phs", __latency_lines_token_kinds[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_token_kinds[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_kinds_keyword() {
	SCPP_CALL_DEPTH_GUARD("token_kinds::keyword", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/token_kinds.phs", __latency_lines_token_kinds[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_token_kinds[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_kinds_number() {
	SCPP_CALL_DEPTH_GUARD("token_kinds::number", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/token_kinds.phs", __latency_lines_token_kinds[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_token_kinds[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_kinds_string_literal() {
	SCPP_CALL_DEPTH_GUARD("token_kinds::string_literal", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/token_kinds.phs", __latency_lines_token_kinds[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_token_kinds[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_kinds_symbol() {
	SCPP_CALL_DEPTH_GUARD("token_kinds::symbol", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/token_kinds.phs", __latency_lines_token_kinds[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_token_kinds[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_kinds_comment() {
	SCPP_CALL_DEPTH_GUARD("token_kinds::comment", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/token_kinds.phs", __latency_lines_token_kinds[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(6));
}

}

namespace scpp { extern const int __latency_lines_token_kinds[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_kinds_error() {
	SCPP_CALL_DEPTH_GUARD("token_kinds::error", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/token_kinds.phs", __latency_lines_token_kinds[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(7));
}

}

namespace scpp { extern const int __latency_lines_token_kinds[]; }
namespace scpp {
string_t __latency_fn_token_kinds_name(int_t<std::uint16_t> kindId) {
	SCPP_CALL_DEPTH_GUARD("token_kinds::name", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/token_kinds.phs", __latency_lines_token_kinds[9]);
	if (static_cast<bool>(php::identical(kindId, __latency_fn_token_kinds_eof()))) {
		return string_t("eof");
	}
	if (static_cast<bool>(php::identical(kindId, __latency_fn_token_kinds_identifier()))) {
		return string_t("identifier");
	}
	if (static_cast<bool>(php::identical(kindId, __latency_fn_token_kinds_keyword()))) {
		return string_t("keyword");
	}
	if (static_cast<bool>(php::identical(kindId, __latency_fn_token_kinds_number()))) {
		return string_t("number");
	}
	if (static_cast<bool>(php::identical(kindId, __latency_fn_token_kinds_string_literal()))) {
		return string_t("string");
	}
	if (static_cast<bool>(php::identical(kindId, __latency_fn_token_kinds_symbol()))) {
		return string_t("symbol");
	}
	if (static_cast<bool>(php::identical(kindId, __latency_fn_token_kinds_comment()))) {
		return string_t("comment");
	}
	if (static_cast<bool>(php::identical(kindId, __latency_fn_token_kinds_error()))) {
		return string_t("error");
	}
	return string_t("unknown");
}

}
