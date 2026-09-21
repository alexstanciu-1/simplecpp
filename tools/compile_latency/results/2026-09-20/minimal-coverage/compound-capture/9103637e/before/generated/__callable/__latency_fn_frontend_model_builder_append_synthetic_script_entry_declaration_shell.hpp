#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
class PhsParserState;
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_synthetic_script_entry_declaration_shell(shared_p<PhsParserState> state, shared_p<FrontendModel> model, int_t<std::uint32_t> entryTypeRefId, shared_p<FrontendModelKernelCounters> counters, int_t<std::uint32_t>& declarationPayloadId);
}
