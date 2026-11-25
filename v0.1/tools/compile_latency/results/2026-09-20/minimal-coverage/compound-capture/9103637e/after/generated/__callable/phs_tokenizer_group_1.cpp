#include <scpp/lang/php.hpp>
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_phs_tokenizer_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_token_tables_extended_length_sentinel.hpp"
#include "__callable/__latency_fn_token_tables_extended_length_sentinel_int.hpp"
#include "__callable/__latency_fn_token_tables_flag_extended_length.hpp"
#include "__callable/__latency_fn_token_tables_with_flag.hpp"
#include "__callable/__latency_fn_phs_tokenizer_apply_token_segment_policy.hpp"
#include "__callable/__latency_fn_phs_tokenizer_from_runtime_token_buffer_with_segment_policy.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_token_tables_append_token_with_full_length.hpp"
#include "__callable/__latency_fn_token_tables_reserve_extended_lengths.hpp"
#include "__callable/__latency_fn_token_tables_reserve_stream.hpp"
#include "__callable/__latency_fn_token_tables_substrate_runtime_token_buffer_id.hpp"
#include "__callable/__latency_fn_phs_tokenizer_from_runtime_token_buffer.hpp"
#include "__callable/__latency_fn_phs_tokenizer_from_runtime_token_buffer_with_segment_policy.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_phs_tokenizer_byte_at.hpp"
#include "__callable/__latency_fn_phs_tokenizer_estimated_token_capacity.hpp"
#include "__callable/__latency_fn_phs_tokenizer_from_source_text_local.hpp"
#include "__callable/__latency_fn_phs_tokenizer_is_alpha_byte.hpp"
#include "__callable/__latency_fn_phs_tokenizer_is_digit_byte.hpp"
#include "__callable/__latency_fn_phs_tokenizer_is_identifier_byte.hpp"
#include "__callable/__latency_fn_phs_tokenizer_is_whitespace_byte.hpp"
#include "__callable/__latency_fn_phs_tokenizer_keyword_kind_id.hpp"
#include "__callable/__latency_fn_phs_tokenizer_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_token_kinds_eof.hpp"
#include "__callable/__latency_fn_token_kinds_number.hpp"
#include "__callable/__latency_fn_token_kinds_symbol.hpp"
#include "__callable/__latency_fn_token_tables_append_token.hpp"
#include "__callable/__latency_fn_token_tables_make_trivia_flags.hpp"
#include "__callable/__latency_fn_token_tables_reserve_stream.hpp"
#include "__callable/__latency_fn_token_tables_substrate_local_scanner_id.hpp"
#include "__callable/__latency_fn_phs_tokenizer_from_runtime_token_buffer.hpp"
#include "__callable/__latency_fn_phs_tokenizer_from_source_text.hpp"
#include "__callable/__latency_fn_phs_tokenizer_from_runtime_token_buffer_with_segment_policy.hpp"
#include "__callable/__latency_fn_phs_tokenizer_from_source_text_with_segment_policy.hpp"
namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
TokenRow __latency_fn_phs_tokenizer_row(int_t<std::uint16_t> kindId, int_t<> startOffset, int_t<> length, int_t<std::uint16_t> flags) {
	SCPP_CALL_DEPTH_GUARD("phs_tokenizer::row", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/phs_tokenizer.phs", __latency_lines_phs_tokenizer[9]);
	TokenRow row = TokenRow{};
	row->kind_id = kindId;
	row->start_offset = __latency_fn_structure_row_ids_uint32_from_int(startOffset);
	if (static_cast<bool>(php::condition_truthy((length >= __latency_fn_token_tables_extended_length_sentinel_int())))) {
		row->length = __latency_fn_token_tables_extended_length_sentinel();
		row->flags = __latency_fn_token_tables_with_flag(flags, __latency_fn_token_tables_flag_extended_length());
		return row;
	}
	row->length = __latency_fn_structure_row_ids_uint16_from_int(length);
	row->flags = flags;
	return row;
}

}

namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
shared_p<TokenStream> __latency_fn_phs_tokenizer_from_runtime_token_buffer_with_segment_policy(int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> sourceBufferId, const string_t& source, int_t<std::uint32_t> segmentThreshold, int_t<std::uint32_t> segmentCapacity) {
	SCPP_CALL_DEPTH_GUARD("phs_tokenizer::from_runtime_token_buffer_with_segment_policy", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/phs_tokenizer.phs", __latency_lines_phs_tokenizer[10]);
	tokenizer::token_buffer_t buffer = required_cast<tokenizer::token_buffer_t>(tokenizer::phs_tokenize_buffer(source));
	shared_p<TokenStream> stream = create<TokenStream>();
	stream->source_unit_id = sourceUnitId;
	stream->source_buffer_id = sourceBufferId;
	stream->source_length = __latency_fn_structure_row_ids_uint32_from_int(str::byte_length(source));
	stream->substrate_id = __latency_fn_token_tables_substrate_runtime_token_buffer_id();
	__latency_fn_phs_tokenizer_apply_token_segment_policy(stream, cast<int_t<std::uint32_t>>(segmentThreshold), cast<int_t<std::uint32_t>>(segmentCapacity));
	int_t<> count = required_cast<int_t<>>(tokenizer::token_buffer_count(buffer));
	__latency_fn_token_tables_reserve_stream(stream, count);
	__latency_fn_token_tables_reserve_extended_lengths(stream, static_cast<int_t<> >(1));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < count))) {
		int_t<std::uint16_t> kindId = required_cast<int_t<std::uint16_t>>(__latency_fn_structure_row_ids_uint16_from_int(tokenizer::token_buffer_kind_id(buffer, index)));
		int_t<> startOffset = required_cast<int_t<>>(tokenizer::token_buffer_start_offset(buffer, index));
		int_t<> fullLength = required_cast<int_t<>>(tokenizer::token_buffer_length(buffer, index));
		int_t<std::uint16_t> flags = required_cast<int_t<std::uint16_t>>(__latency_fn_structure_row_ids_uint16_from_int(tokenizer::token_buffer_flags(buffer, index)));
		__latency_fn_token_tables_append_token_with_full_length(stream, kindId, startOffset, fullLength, flags);
		index = (index + static_cast<int_t<> >(1));
	}
	return stream;
}

}

namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
shared_p<TokenStream> __latency_fn_phs_tokenizer_from_runtime_token_buffer(int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> sourceBufferId, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("phs_tokenizer::from_runtime_token_buffer", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/phs_tokenizer.phs", __latency_lines_phs_tokenizer[11]);
	return __latency_fn_phs_tokenizer_from_runtime_token_buffer_with_segment_policy(cast<int_t<std::uint32_t>>(sourceUnitId), cast<int_t<std::uint32_t>>(sourceBufferId), source, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id());
}

}

namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
shared_p<TokenStream> __latency_fn_phs_tokenizer_from_source_text_local(int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> sourceBufferId, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("phs_tokenizer::from_source_text_local", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/phs_tokenizer.phs", __latency_lines_phs_tokenizer[12]);
	shared_p<TokenStream> stream = create<TokenStream>();
	stream->source_unit_id = sourceUnitId;
	stream->source_buffer_id = sourceBufferId;
	stream->source_length = __latency_fn_structure_row_ids_uint32_from_int(str::byte_length(source));
	stream->substrate_id = __latency_fn_token_tables_substrate_local_scanner_id();
	__latency_fn_token_tables_reserve_stream(stream, __latency_fn_phs_tokenizer_estimated_token_capacity(cast<int_t<>>(stream->source_length)));
	int_t<> offset = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> length = required_cast<int_t<>>(str::byte_length(source));
	bool_t pendingWhitespace = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
	bool_t pendingNewline = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
	while (static_cast<bool>((offset < length))) {
		int_t<> byte = required_cast<int_t<>>(__latency_fn_phs_tokenizer_byte_at(source, offset));
		if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_is_whitespace_byte(byte)))) {
			pendingWhitespace = bool_t(static_cast<bool_t>(true));
			if (static_cast<bool>(php::identical(byte, static_cast<int_t<> >(10)))) {
				pendingNewline = bool_t(static_cast<bool_t>(true));
			}
			offset = (offset + static_cast<int_t<> >(1));
			continue;
		}
		int_t<std::uint16_t> flags = required_cast<int_t<std::uint16_t>>(__latency_fn_token_tables_make_trivia_flags(pendingWhitespace, pendingNewline));
		pendingWhitespace = bool_t(static_cast<bool_t>(false));
		pendingNewline = bool_t(static_cast<bool_t>(false));
		if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_is_alpha_byte(byte)))) {
			int_t<> start = required_cast<int_t<>>(offset);
			while (static_cast<bool>(((offset < length) && __latency_fn_phs_tokenizer_is_identifier_byte(__latency_fn_phs_tokenizer_byte_at(source, offset))))) {
				offset = (offset + static_cast<int_t<> >(1));
			}
			int_t<> tokenLength = required_cast<int_t<>>((offset - start));
			__latency_fn_token_tables_append_token(stream, __latency_fn_phs_tokenizer_row(__latency_fn_phs_tokenizer_keyword_kind_id(source, start, tokenLength), start, tokenLength, cast<int_t<std::uint16_t>>(flags)));
			continue;
		}
		if (static_cast<bool>(php::condition_truthy(__latency_fn_phs_tokenizer_is_digit_byte(byte)))) {
			int_t<> start = required_cast<int_t<>>(offset);
			while (static_cast<bool>(((offset < length) && __latency_fn_phs_tokenizer_is_digit_byte(__latency_fn_phs_tokenizer_byte_at(source, offset))))) {
				offset = (offset + static_cast<int_t<> >(1));
			}
			if (static_cast<bool>(((((offset + static_cast<int_t<> >(1)) < length) && php::identical(__latency_fn_phs_tokenizer_byte_at(source, offset), static_cast<int_t<> >(46))) && __latency_fn_phs_tokenizer_is_digit_byte(__latency_fn_phs_tokenizer_byte_at(source, (offset + static_cast<int_t<> >(1))))))) {
				offset = (offset + static_cast<int_t<> >(1));
				while (static_cast<bool>(((offset < length) && __latency_fn_phs_tokenizer_is_digit_byte(__latency_fn_phs_tokenizer_byte_at(source, offset))))) {
					offset = (offset + static_cast<int_t<> >(1));
				}
			}
			__latency_fn_token_tables_append_token(stream, __latency_fn_phs_tokenizer_row(__latency_fn_token_kinds_number(), start, (offset - start), cast<int_t<std::uint16_t>>(flags)));
			continue;
		}
		if (static_cast<bool>(((offset + static_cast<int_t<> >(2)) < length))) {
			int_t<> second = required_cast<int_t<>>(__latency_fn_phs_tokenizer_byte_at(source, (offset + static_cast<int_t<> >(1))));
			int_t<> third = required_cast<int_t<>>(__latency_fn_phs_tokenizer_byte_at(source, (offset + static_cast<int_t<> >(2))));
			if (static_cast<bool>(((((((php::identical(byte, static_cast<int_t<> >(61)) && php::identical(second, static_cast<int_t<> >(61))) && php::identical(third, static_cast<int_t<> >(61))) || ((php::identical(byte, static_cast<int_t<> >(60)) && php::identical(second, static_cast<int_t<> >(61))) && php::identical(third, static_cast<int_t<> >(62)))) || ((php::identical(byte, static_cast<int_t<> >(60)) && php::identical(second, static_cast<int_t<> >(60))) && php::identical(third, static_cast<int_t<> >(61)))) || ((php::identical(byte, static_cast<int_t<> >(62)) && php::identical(second, static_cast<int_t<> >(62))) && php::identical(third, static_cast<int_t<> >(61)))) || ((php::identical(byte, static_cast<int_t<> >(33)) && php::identical(second, static_cast<int_t<> >(61))) && php::identical(third, static_cast<int_t<> >(61)))))) {
				__latency_fn_token_tables_append_token(stream, __latency_fn_phs_tokenizer_row(__latency_fn_token_kinds_symbol(), offset, static_cast<int_t<> >(3), cast<int_t<std::uint16_t>>(flags)));
				offset = (offset + static_cast<int_t<> >(3));
				continue;
			}
		}
		if (static_cast<bool>(((offset + static_cast<int_t<> >(1)) < length))) {
			int_t<> next = required_cast<int_t<>>(__latency_fn_phs_tokenizer_byte_at(source, (offset + static_cast<int_t<> >(1))));
			if (static_cast<bool>((((((((((((((php::identical(byte, static_cast<int_t<> >(58)) && php::identical(next, static_cast<int_t<> >(58))) || (php::identical(byte, static_cast<int_t<> >(61)) && php::identical(next, static_cast<int_t<> >(62)))) || (php::identical(byte, static_cast<int_t<> >(61)) && php::identical(next, static_cast<int_t<> >(61)))) || (php::identical(byte, static_cast<int_t<> >(33)) && php::identical(next, static_cast<int_t<> >(61)))) || (php::identical(byte, static_cast<int_t<> >(60)) && php::identical(next, static_cast<int_t<> >(61)))) || (php::identical(byte, static_cast<int_t<> >(62)) && php::identical(next, static_cast<int_t<> >(61)))) || (php::identical(byte, static_cast<int_t<> >(60)) && php::identical(next, static_cast<int_t<> >(60)))) || (php::identical(byte, static_cast<int_t<> >(62)) && php::identical(next, static_cast<int_t<> >(62)))) || (php::identical(byte, static_cast<int_t<> >(38)) && php::identical(next, static_cast<int_t<> >(38)))) || (php::identical(byte, static_cast<int_t<> >(124)) && php::identical(next, static_cast<int_t<> >(124)))) || (php::identical(byte, static_cast<int_t<> >(63)) && php::identical(next, static_cast<int_t<> >(63)))) || (php::identical(byte, static_cast<int_t<> >(42)) && php::identical(next, static_cast<int_t<> >(42)))) || (php::identical(byte, static_cast<int_t<> >(45)) && php::identical(next, static_cast<int_t<> >(62)))))) {
				__latency_fn_token_tables_append_token(stream, __latency_fn_phs_tokenizer_row(__latency_fn_token_kinds_symbol(), offset, static_cast<int_t<> >(2), cast<int_t<std::uint16_t>>(flags)));
				offset = (offset + static_cast<int_t<> >(2));
				continue;
			}
		}
		__latency_fn_token_tables_append_token(stream, __latency_fn_phs_tokenizer_row(__latency_fn_token_kinds_symbol(), offset, static_cast<int_t<> >(1), cast<int_t<std::uint16_t>>(flags)));
		offset = (offset + static_cast<int_t<> >(1));
	}
	int_t<std::uint16_t> eofFlags = required_cast<int_t<std::uint16_t>>(__latency_fn_token_tables_make_trivia_flags(pendingWhitespace, pendingNewline));
	__latency_fn_token_tables_append_token(stream, __latency_fn_phs_tokenizer_row(__latency_fn_token_kinds_eof(), offset, static_cast<int_t<> >(0), cast<int_t<std::uint16_t>>(eofFlags)));
	return stream;
}

}

namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
shared_p<TokenStream> __latency_fn_phs_tokenizer_from_source_text(int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> sourceBufferId, const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("phs_tokenizer::from_source_text", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/phs_tokenizer.phs", __latency_lines_phs_tokenizer[13]);
	return __latency_fn_phs_tokenizer_from_runtime_token_buffer(cast<int_t<std::uint32_t>>(sourceUnitId), cast<int_t<std::uint32_t>>(sourceBufferId), source);
}

}

namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
shared_p<TokenStream> __latency_fn_phs_tokenizer_from_source_text_with_segment_policy(int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> sourceBufferId, const string_t& source, int_t<std::uint32_t> segmentThreshold, int_t<std::uint32_t> segmentCapacity) {
	SCPP_CALL_DEPTH_GUARD("phs_tokenizer::from_source_text_with_segment_policy", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/phs_tokenizer.phs", __latency_lines_phs_tokenizer[14]);
	return __latency_fn_phs_tokenizer_from_runtime_token_buffer_with_segment_policy(cast<int_t<std::uint32_t>>(sourceUnitId), cast<int_t<std::uint32_t>>(sourceBufferId), source, cast<int_t<std::uint32_t>>(segmentThreshold), cast<int_t<std::uint32_t>>(segmentCapacity));
}

}
