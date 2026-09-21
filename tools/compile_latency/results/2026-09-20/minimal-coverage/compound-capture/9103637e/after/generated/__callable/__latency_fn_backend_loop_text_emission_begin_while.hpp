#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendControlFlowOperandRow;
class BackendLoopTextEmissionState;
void __latency_fn_backend_loop_text_emission_begin_while(shared_p<BackendLoopTextEmissionState>& state, str::text_builder& lines, BackendControlFlowOperandRow controlFlowOperand, int_t<> valueId, vector_t<int_t<std::uint32_t>>& localSourceRows);
}
