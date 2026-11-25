#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
void __latency_fn_proof_metrics_add_mt_single_pipeline_live_publication_summary(shared_p<CompilerProjectRunReport>& report, int_t<> workerCount, bool_t selected, bool_t promotionBlocked, bool_t sideEffectBlocked, int_t<std::uint32_t> publishedRows, int_t<std::uint32_t> payloadCopyBytes);
}
