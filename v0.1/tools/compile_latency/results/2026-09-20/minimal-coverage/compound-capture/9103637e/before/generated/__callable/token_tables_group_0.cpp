#include <scpp/lang/php.hpp>
#include "__types/TokenExtendedLengthRow.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenRowList.hpp"
#include "__types/TokenStream.hpp"
#include "__types/token_tables.hpp"
#include "__callable/__latency_fn_token_tables_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_token_row_lists_reserve.hpp"
#include "__callable/__latency_fn_token_tables_reserve_stream.hpp"
#include "__callable/__latency_fn_token_tables_reserve_extended_lengths.hpp"
#include "__callable/__latency_fn_token_row_lists_append.hpp"
#include "__callable/__latency_fn_token_tables_append_token.hpp"
#include "__callable/__latency_fn_token_tables_append_extended_length.hpp"
#include "__callable/__latency_fn_token_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_token_tables_extended_length_by_token_id.hpp"
#include "__callable/__latency_fn_token_tables_append_extended_length.hpp"
#include "__callable/__latency_fn_token_tables_append_token.hpp"
#include "__callable/__latency_fn_token_tables_append_token_with_full_length.hpp"
#include "__callable/__latency_fn_token_tables_extended_length_sentinel.hpp"
#include "__callable/__latency_fn_token_tables_extended_length_sentinel_int.hpp"
#include "__callable/__latency_fn_token_tables_flag_extended_length.hpp"
#include "__callable/__latency_fn_token_tables_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_token_tables_with_flag.hpp"
#include "__callable/__latency_fn_token_row_lists_row_by_id.hpp"
#include "__callable/__latency_fn_token_tables_token_by_id.hpp"
#include "__callable/__latency_fn_token_row_lists_row_by_index.hpp"
#include "__callable/__latency_fn_token_tables_token_by_index.hpp"
#include "__callable/__latency_fn_token_row_lists_row_count.hpp"
#include "__callable/__latency_fn_token_tables_row_count.hpp"
#include "__callable/__latency_fn_token_tables_storage_kind_id.hpp"
#include "__callable/__latency_fn_token_row_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_token_tables_uses_segmented.hpp"
namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
bool_t token_tables::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == token_tables::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_tables_uint16_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("token_tables::uint16_from_int", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[0]);
	return cast<int_t<std::uint16_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_token_tables_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("token_tables::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[1]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
void __latency_fn_token_tables_reserve_stream(shared_p<TokenStream> stream, int_t<> tokenCapacity) {
	SCPP_CALL_DEPTH_GUARD("token_tables::reserve_stream", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[2]);
	__latency_fn_token_row_lists_reserve(stream->token_rows, tokenCapacity);
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
void __latency_fn_token_tables_reserve_extended_lengths(shared_p<TokenStream> stream, int_t<> capacity) {
	SCPP_CALL_DEPTH_GUARD("token_tables::reserve_extended_lengths", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[3]);
	php::vector_reserve(stream->extended_lengths, capacity);
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_token_tables_append_token(shared_p<TokenStream> stream, TokenRow row) {
	SCPP_CALL_DEPTH_GUARD("token_tables::append_token", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[4]);
	stream->token_count = __latency_fn_token_row_lists_append(stream->token_rows, row);
	return stream->token_count;
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
void __latency_fn_token_tables_append_extended_length(shared_p<TokenStream> stream, int_t<std::uint32_t> tokenId, int_t<std::uint32_t> length) {
	SCPP_CALL_DEPTH_GUARD("token_tables::append_extended_length", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[5]);
	TokenExtendedLengthRow row = TokenExtendedLengthRow{};
	row->token_id = tokenId;
	row->length = length;
	(void) stream->extended_lengths.append(row);
	stream->extended_length_count = __latency_fn_token_tables_uint32_from_int(php::count(stream->extended_lengths));
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
TokenExtendedLengthRow __latency_fn_token_tables_extended_length_by_token_id(shared_p<TokenStream> stream, int_t<std::uint32_t> tokenId) {
	SCPP_CALL_DEPTH_GUARD("token_tables::extended_length_by_token_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[6]);
	auto __latency_local_0 = stream->extended_lengths;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->token_id), cast<int_t<>>(tokenId)))) {
			return row;
		}
	}
	TokenExtendedLengthRow empty = TokenExtendedLengthRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_token_tables_append_token_with_full_length(shared_p<TokenStream> stream, int_t<std::uint16_t> kindId, int_t<> startOffset, int_t<> fullLength, int_t<std::uint16_t> flags) {
	SCPP_CALL_DEPTH_GUARD("token_tables::append_token_with_full_length", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[7]);
	TokenRow row = TokenRow{};
	row->kind_id = kindId;
	row->start_offset = __latency_fn_token_tables_uint32_from_int(startOffset);
	if (static_cast<bool>(php::condition_truthy((fullLength >= __latency_fn_token_tables_extended_length_sentinel_int())))) {
		row->length = __latency_fn_token_tables_extended_length_sentinel();
		row->flags = __latency_fn_token_tables_with_flag(cast<int_t<std::uint16_t>>(flags), __latency_fn_token_tables_flag_extended_length());
		int_t<std::uint32_t> tokenId = required_cast<int_t<std::uint32_t>>(__latency_fn_token_tables_append_token(stream, row));
		__latency_fn_token_tables_append_extended_length(stream, cast<int_t<std::uint32_t>>(tokenId), __latency_fn_token_tables_uint32_from_int(fullLength));
		return cast<int_t<std::uint32_t>>(tokenId);
	}
	row->length = __latency_fn_token_tables_uint16_from_int(fullLength);
	row->flags = flags;
	return __latency_fn_token_tables_append_token(stream, row);
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
TokenRow __latency_fn_token_tables_token_by_id(shared_p<TokenStream> stream, int_t<std::uint32_t> tokenId) {
	SCPP_CALL_DEPTH_GUARD("token_tables::token_by_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[8]);
	return __latency_fn_token_row_lists_row_by_id(stream->token_rows, tokenId);
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
TokenRow __latency_fn_token_tables_token_by_index(shared_p<TokenStream> stream, int_t<> index) {
	SCPP_CALL_DEPTH_GUARD("token_tables::token_by_index", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[9]);
	return __latency_fn_token_row_lists_row_by_index(stream->token_rows, index);
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_token_tables_row_count(shared_p<TokenStream> stream) {
	SCPP_CALL_DEPTH_GUARD("token_tables::row_count", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[10]);
	return __latency_fn_token_row_lists_row_count(stream->token_rows);
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_token_tables_storage_kind_id(shared_p<TokenStream> stream) {
	SCPP_CALL_DEPTH_GUARD("token_tables::storage_kind_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[11]);
	return stream->token_rows->storage_kind_id;
}

}

namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
bool_t __latency_fn_token_tables_uses_segmented(shared_p<TokenStream> stream) {
	SCPP_CALL_DEPTH_GUARD("token_tables::uses_segmented", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[12]);
	return __latency_fn_token_row_lists_uses_segmented(stream->token_rows);
}

}
