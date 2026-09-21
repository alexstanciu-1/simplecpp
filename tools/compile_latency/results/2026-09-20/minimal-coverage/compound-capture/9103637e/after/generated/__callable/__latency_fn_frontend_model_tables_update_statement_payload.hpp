#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendStatementPayloadRow;
bool_t __latency_fn_frontend_model_tables_update_statement_payload(shared_p<FrontendModel> model, FrontendStatementPayloadRow row, shared_p<FrontendModelKernelCounters> counters);
}
