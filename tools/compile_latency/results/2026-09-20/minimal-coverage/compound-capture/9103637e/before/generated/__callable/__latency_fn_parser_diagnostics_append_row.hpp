#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ParserDiagnosticRow;
class ParserDiagnosticTable;
int_t<std::uint32_t> __latency_fn_parser_diagnostics_append_row(shared_p<ParserDiagnosticTable> table, ParserDiagnosticRow row);
}
