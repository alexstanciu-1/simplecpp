#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SourceUnitTable;
struct SourceUnitTableRow;
class TokenStream;
shared_p<TokenStream> __latency_fn_phs_tokenizer_from_source_unit(shared_p<SourceUnitTable> table, SourceUnitTableRow sourceUnit, int_t<std::uint32_t> sourceBufferId);
}
