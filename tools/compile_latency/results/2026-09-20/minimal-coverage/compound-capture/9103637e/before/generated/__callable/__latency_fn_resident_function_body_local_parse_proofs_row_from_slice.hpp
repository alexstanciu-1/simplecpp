#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentFunctionBodyLocalParseProofRow;
struct ResidentFunctionBodyParseSliceRow;
class SourceUnitTable;
ResidentFunctionBodyLocalParseProofRow __latency_fn_resident_function_body_local_parse_proofs_row_from_slice(shared_p<CompilerProjectRunReport> report, shared_p<SourceUnitTable> sourceUnits, ResidentFunctionBodyParseSliceRow slice);
}
