#include <scpp/lang/php.hpp>
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_phs_tokenizer_from_source_text.hpp"
#include "__callable/__latency_fn_phs_tokenizer_from_source_unit.hpp"
#include "__callable/__latency_fn_source_units_source_text_by_id.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text.hpp"
#include "__callable/__latency_fn_phs_tokenizer_slice_equals.hpp"
#include "__callable/__latency_fn_phs_tokenizer_token_text_equals.hpp"
namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
shared_p<TokenStream> __latency_fn_phs_tokenizer_from_source_unit(shared_p<SourceUnitTable> table, SourceUnitTableRow sourceUnit, int_t<std::uint32_t> sourceBufferId) {
	SCPP_CALL_DEPTH_GUARD("phs_tokenizer::from_source_unit", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/phs_tokenizer.phs", __latency_lines_phs_tokenizer[15]);
	string_t source = required_cast<string_t>(__latency_fn_source_units_source_text_by_id(table, sourceUnit->source_unit_id));
	return __latency_fn_phs_tokenizer_from_source_text(sourceUnit->source_unit_id, cast<int_t<std::uint32_t>>(sourceBufferId), source);
}

}

namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
string_t __latency_fn_phs_tokenizer_token_text(const string_t& source, TokenRow row) {
	SCPP_CALL_DEPTH_GUARD("phs_tokenizer::token_text", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/phs_tokenizer.phs", __latency_lines_phs_tokenizer[16]);
	return str::byte_slice(source, cast<int_t<>>(row->start_offset), cast<int_t<>>(row->length));
}

}

namespace scpp { extern const int __latency_lines_phs_tokenizer[]; }
namespace scpp {
bool_t __latency_fn_phs_tokenizer_token_text_equals(const string_t& source, TokenRow row, const string_t& literal) {
	SCPP_CALL_DEPTH_GUARD("phs_tokenizer::token_text_equals", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/phs_tokenizer.phs", __latency_lines_phs_tokenizer[17]);
	return __latency_fn_phs_tokenizer_slice_equals(source, cast<int_t<>>(row->start_offset), cast<int_t<>>(row->length), literal);
}

}
