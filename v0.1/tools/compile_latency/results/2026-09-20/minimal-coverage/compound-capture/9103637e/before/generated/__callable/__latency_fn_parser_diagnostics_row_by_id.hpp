#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ParserDiagnosticRow;
class ParserDiagnosticTable;
ParserDiagnosticRow __latency_fn_parser_diagnostics_row_by_id(shared_p<ParserDiagnosticTable> table, int_t<std::uint32_t> diagnosticId);
}
