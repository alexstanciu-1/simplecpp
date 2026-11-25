#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SemanticOperatorLookupRow;
shared_p<SemanticOperatorLookupRow> __latency_fn_semantic_operator_lookup_row_by_symbol_and_type_refs(const string_t& operatorSymbol, int_t<std::uint32_t> lhsTypeRefId, int_t<std::uint32_t> rhsTypeRefId);
}
