#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
void __latency_fn_frontend_model_tables_reserve_model(shared_p<FrontendModel> model, int_t<> sourceBufferCapacity, int_t<> sourceRangeCapacity, int_t<> sourceRangeExtCapacity, int_t<> lineStartCapacity, int_t<> nodeCapacity, int_t<> declarationCapacity, int_t<> statementCapacity, int_t<> expressionCapacity, int_t<> typeSyntaxCapacity, int_t<> literalCapacity, int_t<> nameCapacity, shared_p<FrontendModelKernelCounters> counters);
}
