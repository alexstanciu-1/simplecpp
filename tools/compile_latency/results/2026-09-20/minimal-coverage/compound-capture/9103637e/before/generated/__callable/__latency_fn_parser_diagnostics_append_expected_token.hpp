#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ParserDiagnosticTable;
struct PhsParserCursor;
struct TokenRow;
int_t<std::uint32_t> __latency_fn_parser_diagnostics_append_expected_token(shared_p<ParserDiagnosticTable> table, int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> sourceBufferId, PhsParserCursor cursor, TokenRow token);
}
