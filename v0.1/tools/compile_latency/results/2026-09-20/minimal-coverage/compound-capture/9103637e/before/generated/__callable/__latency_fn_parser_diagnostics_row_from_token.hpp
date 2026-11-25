#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ParserDiagnosticRow;
struct TokenRow;
ParserDiagnosticRow __latency_fn_parser_diagnostics_row_from_token(int_t<std::uint32_t> diagnosticId, int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> sourceBufferId, int_t<std::uint32_t> tokenId, TokenRow token, int_t<std::uint16_t> reasonId, int_t<std::uint32_t> relatedRowId);
}
