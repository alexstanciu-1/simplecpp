#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
struct FrontendNodeRow;
struct FrontendNodeSpan;
FrontendNodeRow __latency_fn_frontend_model_tables_span_node_at(shared_p<FrontendModel> model, FrontendNodeSpan span, int_t<> offset);
}
