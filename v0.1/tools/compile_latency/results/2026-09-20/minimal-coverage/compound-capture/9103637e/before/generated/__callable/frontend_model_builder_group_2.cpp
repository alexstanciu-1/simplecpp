#include <scpp/lang/php.hpp>
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/PhsParserCursor.hpp"
#include "__types/PhsParserState.hpp"
#include "__types/ProviderTraitDescriptorRow.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_frontend_model_builder_integer_positive_limit_for_width.hpp"
#include "__callable/__latency_fn_frontend_model_builder_integer_literal_fits_type_ref.hpp"
#include "__callable/__latency_fn_frontend_model_builder_integer_positive_limit_for_width.hpp"
#include "__callable/__latency_fn_type_traits_descriptor_from_type_ref_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_signed_integer_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_unsigned_integer_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_token_end_offset.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_token.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_source_range.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_range_from_offsets.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_source_range.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_builder_primitive_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_type_refs_primitive_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_type_refs_source_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expect_type_name_token.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_parser_cursor_current.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text.hpp"
#include "__callable/__latency_fn_token_kinds_identifier.hpp"
#include "__callable/__latency_fn_token_kinds_keyword.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expect_type_name_token.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_type_ref_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_source_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_parser_state_at.hpp"
#include "__callable/__latency_fn_parser_state_diagnostic.hpp"
#include "__callable/__latency_fn_parser_state_expect.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text.hpp"
#include "__callable/__latency_fn_token_kinds_eof.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_type_refs_family_instance_type_ref_id_for_type_args.hpp"
#include "__callable/__latency_fn_type_refs_source_family_id_for_name.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<> __latency_fn_frontend_model_builder_integer_positive_limit_for_width(int_t<std::uint16_t> widthBits) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::integer_positive_limit_for_width", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[32]);
	if (static_cast<bool>(php::condition_truthy((cast<int_t<>>(widthBits) >= static_cast<int_t<> >(31))))) {
		return static_cast<int_t<> >(2147483647);
	}
	int_t<> limit = required_cast<int_t<>>(static_cast<int_t<> >(1));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < cast<int_t<>>(widthBits)))) {
		limit = (limit * static_cast<int_t<> >(2));
		index = (index + static_cast<int_t<> >(1));
	}
	return limit;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_integer_literal_fits_type_ref(int_t<std::uint32_t> typeRefId, int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::integer_literal_fits_type_ref", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[33]);
	ProviderTraitDescriptorRow descriptor = __latency_fn_type_traits_descriptor_from_type_ref_id(typeRefId);
	if (static_cast<bool>(php::identical(cast<int_t<>>(descriptor->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_signed_integer_id())))) {
		if (static_cast<bool>(php::condition_truthy((cast<int_t<>>(descriptor->integer_width_bits) >= static_cast<int_t<> >(31))))) {
			return bool_t(static_cast<bool_t>(true));
		}
		int_t<> limit = required_cast<int_t<>>(__latency_fn_frontend_model_builder_integer_positive_limit_for_width(descriptor->integer_width_bits));
		int_t<> min = required_cast<int_t<>>((-(limit / static_cast<int_t<> >(2))));
		int_t<> max = required_cast<int_t<>>(((limit / static_cast<int_t<> >(2)) - static_cast<int_t<> >(1)));
		return ((value >= min) && (value <= max));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(descriptor->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_unsigned_integer_id())))) {
		if (static_cast<bool>((value < static_cast<int_t<> >(0)))) {
			return bool_t(static_cast<bool_t>(false));
		}
		int_t<> limit = required_cast<int_t<>>(__latency_fn_frontend_model_builder_integer_positive_limit_for_width(descriptor->integer_width_bits));
		return bool_t((value < limit));
	}
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<> __latency_fn_frontend_model_builder_token_end_offset(TokenRow token) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::token_end_offset", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[34]);
	return (cast<int_t<>>(token->start_offset) + cast<int_t<>>(token->length));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_source_range_from_token(shared_p<FrontendModel> model, TokenRow token, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::source_range_from_token", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[35]);
	SourceRangeRow row = SourceRangeRow{.source_range_id = __latency_fn_structure_row_ids_none_id(), .start_offset = token->start_offset, .length = __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(token->length)), .flags = __latency_fn_structure_row_ids_none_kind_id()};
	return __latency_fn_frontend_model_tables_append_source_range(model, row, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_source_range_from_offsets(shared_p<FrontendModel> model, int_t<> startOffset, int_t<> endOffset, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::source_range_from_offsets", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[36]);
	int_t<> length = required_cast<int_t<>>(static_cast<int_t<> >(0));
	if (static_cast<bool>((endOffset > startOffset))) {
		length = (endOffset - startOffset);
	}
	SourceRangeRow row = SourceRangeRow{.source_range_id = __latency_fn_structure_row_ids_none_id(), .start_offset = __latency_fn_structure_row_ids_uint32_from_int(startOffset), .length = __latency_fn_structure_row_ids_uint32_from_int(length), .flags = __latency_fn_structure_row_ids_none_kind_id()};
	return __latency_fn_frontend_model_tables_append_source_range(model, row, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_primitive_type_ref_id_for_name(const string_t& typeName) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::primitive_type_ref_id_for_name", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[37]);
	return __latency_fn_type_refs_primitive_type_ref_id_for_name(typeName);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_source_type_ref_id_for_name(const string_t& typeName) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::source_type_ref_id_for_name", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[38]);
	return __latency_fn_type_refs_source_type_ref_id_for_name(typeName);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
TokenRow __latency_fn_frontend_model_builder_expect_type_name_token(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::expect_type_name_token", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[39]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_identifier(), string_t(""))))) {
		return __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t(""));
	}
	TokenRow current = __latency_fn_parser_cursor_current(state->tokens, state->cursor);
	string_t typeName = required_cast<string_t>(__latency_fn_phs_tokenizer_token_text(source, current));
	if (static_cast<bool>((php::identical(cast<int_t<>>(current->kind_id), cast<int_t<>>(__latency_fn_token_kinds_keyword())) && (cast<int_t<>>(__latency_fn_frontend_model_builder_source_type_ref_id_for_name(typeName)) > static_cast<int_t<> >(0))))) {
		return __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_keyword(), string_t(""));
	}
	return __latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_identifier(), string_t(""));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_type_ref_id(shared_p<PhsParserState> state, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::parse_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[40]);
	TokenRow typeToken = __latency_fn_frontend_model_builder_expect_type_name_token(state, source);
	string_t typeName = required_cast<string_t>(__latency_fn_phs_tokenizer_token_text(source, typeToken));
	if (static_cast<bool>((!__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t("<"))))) {
		return __latency_fn_frontend_model_builder_source_type_ref_id_for_name(typeName);
	}
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t("<"));
	vector_t<int_t<std::uint32_t>> typeArgRefIds = {};
	while (static_cast<bool>(((!__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t(">"))) && (!__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_eof(), string_t("")))))) {
		{
		auto __latency_local_0 = __latency_fn_frontend_model_builder_parse_type_ref_id(state, source);
		(void) typeArgRefIds.push_back(__latency_local_0);
		}
		if (static_cast<bool>(php::condition_truthy(__latency_fn_parser_state_at(state, source, __latency_fn_token_kinds_symbol(), string_t(","))))) {
			__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(","));
		}
		else {
			break;
		}
	}
	__latency_fn_parser_state_expect(state, source, __latency_fn_token_kinds_symbol(), string_t(">"));
	int_t<std::uint32_t> familyId = required_cast<int_t<std::uint32_t>>(__latency_fn_type_refs_source_family_id_for_name(typeName));
	int_t<std::uint32_t> typeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_type_refs_family_instance_type_ref_id_for_type_args(familyId, typeArgRefIds));
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), static_cast<int_t<> >(0)))) {
		__latency_fn_parser_state_diagnostic(state);
	}
	return cast<int_t<std::uint32_t>>(typeRefId);
}

}
