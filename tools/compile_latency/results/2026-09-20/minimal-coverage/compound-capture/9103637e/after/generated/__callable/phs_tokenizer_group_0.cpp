#include <scpp/lang/php.hpp>
#include "__types/TokenRowList.hpp"
#include "__types/TokenStream.hpp"
#include "__types/phs_tokenizer.hpp"
#include "__callable/__latency_fn_phs_tokenizer_byte_at.hpp"
#include "__callable/__latency_fn_phs_tokenizer_is_alpha_byte.hpp"
#include "__callable/__latency_fn_phs_tokenizer_is_digit_byte.hpp"
#include "__callable/__latency_fn_phs_tokenizer_is_alpha_byte.hpp"
#include "__callable/__latency_fn_phs_tokenizer_is_digit_byte.hpp"
#include "__callable/__latency_fn_phs_tokenizer_is_identifier_byte.hpp"
#include "__callable/__latency_fn_phs_tokenizer_is_whitespace_byte.hpp"
#include "__callable/__latency_fn_phs_tokenizer_slice_equals.hpp"
#include "__callable/__latency_fn_phs_tokenizer_keyword_kind_id.hpp"
#include "__callable/__latency_fn_phs_tokenizer_slice_equals.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_phs_tokenizer_estimated_token_capacity.hpp"
#include "__callable/__latency_fn_phs_tokenizer_apply_token_segment_policy.hpp"
namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
bool_t phs_tokenizer::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == phs_tokenizer::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
int_t<> __latency_fn_phs_tokenizer_byte_at(const string_t& text, int_t<> offset) {
	SCPP_CALL_DEPTH_GUARD("phs_tokenizer::byte_at", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/phs_tokenizer.phs", __latency_lines_phs_tokenizer[0]);
	return php::string_byte_at(text, offset);
}

}

namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
bool_t __latency_fn_phs_tokenizer_is_alpha_byte(int_t<> byte) {
	SCPP_CALL_DEPTH_GUARD("phs_tokenizer::is_alpha_byte", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/phs_tokenizer.phs", __latency_lines_phs_tokenizer[1]);
	return ((((byte >= static_cast<int_t<> >(65)) && (byte <= static_cast<int_t<> >(90))) || ((byte >= static_cast<int_t<> >(97)) && (byte <= static_cast<int_t<> >(122)))) || php::identical(byte, static_cast<int_t<> >(95)));
}

}

namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
bool_t __latency_fn_phs_tokenizer_is_digit_byte(int_t<> byte) {
	SCPP_CALL_DEPTH_GUARD("phs_tokenizer::is_digit_byte", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/phs_tokenizer.phs", __latency_lines_phs_tokenizer[2]);
	return ((byte >= static_cast<int_t<> >(48)) && (byte <= static_cast<int_t<> >(57)));
}

}

namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
bool_t __latency_fn_phs_tokenizer_is_identifier_byte(int_t<> byte) {
	SCPP_CALL_DEPTH_GUARD("phs_tokenizer::is_identifier_byte", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/phs_tokenizer.phs", __latency_lines_phs_tokenizer[3]);
	return (__latency_fn_phs_tokenizer_is_alpha_byte(byte) || __latency_fn_phs_tokenizer_is_digit_byte(byte));
}

}

namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
bool_t __latency_fn_phs_tokenizer_is_whitespace_byte(int_t<> byte) {
	SCPP_CALL_DEPTH_GUARD("phs_tokenizer::is_whitespace_byte", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/phs_tokenizer.phs", __latency_lines_phs_tokenizer[4]);
	return (((php::identical(byte, static_cast<int_t<> >(32)) || php::identical(byte, static_cast<int_t<> >(9))) || php::identical(byte, static_cast<int_t<> >(13))) || php::identical(byte, static_cast<int_t<> >(10)));
}

}

namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
bool_t __latency_fn_phs_tokenizer_slice_equals(const string_t& source, int_t<> start, int_t<> length, const string_t& literal) {
	SCPP_CALL_DEPTH_GUARD("phs_tokenizer::slice_equals", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/phs_tokenizer.phs", __latency_lines_phs_tokenizer[5]);
	return php::string_byte_slice_equals(source, start, length, literal);
}

}

namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_phs_tokenizer_keyword_kind_id(const string_t& source, int_t<> start, int_t<> length) {
	SCPP_CALL_DEPTH_GUARD("phs_tokenizer::keyword_kind_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/phs_tokenizer.phs", __latency_lines_phs_tokenizer[6]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("function"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("class"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("namespace"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("use"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("public"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("private"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("protected"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("static"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("return"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("if"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("else"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("for"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("while"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("switch"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("case"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("default"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("foreach"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("try"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("catch"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("throw"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("break"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("continue"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_slice_equals(source, start, length, string_t("echo"))))) {
		return __latency_fn_token_kinds_keyword();
	}
	return __latency_fn_token_kinds_identifier();
}

}

namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
int_t<> __latency_fn_phs_tokenizer_estimated_token_capacity(int_t<> sourceLength) {
	SCPP_CALL_DEPTH_GUARD("phs_tokenizer::estimated_token_capacity", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/phs_tokenizer.phs", __latency_lines_phs_tokenizer[7]);
	return (cast<int_t<>>((sourceLength / static_cast<int_t<> >(2))) + static_cast<int_t<> >(8));
}

}

namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
void __latency_fn_phs_tokenizer_apply_token_segment_policy(shared_p<TokenStream> stream, int_t<std::uint32_t> segmentThreshold, int_t<std::uint32_t> segmentCapacity) {
	SCPP_CALL_DEPTH_GUARD("phs_tokenizer::apply_token_segment_policy", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/phs_tokenizer.phs", __latency_lines_phs_tokenizer[8]);
	if (static_cast<bool>((cast<int_t<>>(segmentThreshold) > static_cast<int_t<> >(0)))) {
		stream->token_rows->segment_threshold = segmentThreshold;
	}
	if (static_cast<bool>((cast<int_t<>>(segmentCapacity) > static_cast<int_t<> >(0)))) {
		stream->token_rows->segment_capacity = segmentCapacity;
	}
}

}
