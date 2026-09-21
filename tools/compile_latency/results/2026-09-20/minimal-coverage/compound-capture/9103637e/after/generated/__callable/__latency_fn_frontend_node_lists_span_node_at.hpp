#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendNodeList;
struct FrontendNodeRow;
struct FrontendNodeSpan;
FrontendNodeRow __latency_fn_frontend_node_lists_span_node_at(shared_p<FrontendNodeList> list, FrontendNodeSpan span, int_t<> offset);
}
