#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionBlockRow;
struct BackendEmissionDecisionRow;
BackendEmissionBlockRow __latency_fn_backend_emission_decisions_block_from_decision(int_t<std::uint32_t> blockId, BackendEmissionDecisionRow decision);
}
