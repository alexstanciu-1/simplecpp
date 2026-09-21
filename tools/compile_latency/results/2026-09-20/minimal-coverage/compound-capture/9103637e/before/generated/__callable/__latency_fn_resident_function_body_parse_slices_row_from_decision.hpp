#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentFunctionBodyParseSliceRow;
struct ResidentFunctionBodyWorkDecisionRow;
class SourceUnitTable;
ResidentFunctionBodyParseSliceRow __latency_fn_resident_function_body_parse_slices_row_from_decision(shared_p<CompilerProjectRunReport> report, shared_p<SourceUnitTable> sourceUnits, ResidentFunctionBodyWorkDecisionRow decision);
}
