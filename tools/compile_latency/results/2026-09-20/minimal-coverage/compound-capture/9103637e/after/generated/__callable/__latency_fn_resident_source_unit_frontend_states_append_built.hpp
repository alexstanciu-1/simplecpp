#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class FrontendModel;
struct ResidentSourceUnitFrontendStateRow;
struct SourceUnitTableRow;
class TokenStream;
ResidentSourceUnitFrontendStateRow __latency_fn_resident_source_unit_frontend_states_append_built(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, SourceUnitTableRow sourceUnit, shared_p<TokenStream>& tokens, shared_p<FrontendModel>& model, int_t<std::uint16_t> stateKindId);
}
