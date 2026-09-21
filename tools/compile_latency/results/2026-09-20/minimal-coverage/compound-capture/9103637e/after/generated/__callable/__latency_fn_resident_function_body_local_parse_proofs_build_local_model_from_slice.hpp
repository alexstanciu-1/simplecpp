#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
struct ResidentFunctionBodyParseSliceRow;
class SourceUnitTable;
bool_t __latency_fn_resident_function_body_local_parse_proofs_build_local_model_from_slice(shared_p<SourceUnitTable> sourceUnits, ResidentFunctionBodyParseSliceRow slice, shared_p<FrontendModel> model, int_t<std::uint32_t>& actualTokenAfterBodyIndex, int_t<std::uint32_t>& parserDiagnosticCount);
}
