#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionBlockRow;
struct BackendEmissionDecisionArtifact;
BackendEmissionBlockRow __latency_fn_backend_emission_decisions_block_by_id(BackendEmissionDecisionArtifact artifact, int_t<std::uint32_t> blockId);
}
