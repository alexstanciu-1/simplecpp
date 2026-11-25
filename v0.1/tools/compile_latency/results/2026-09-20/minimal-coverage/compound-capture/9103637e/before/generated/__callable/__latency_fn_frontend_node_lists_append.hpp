#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendNodeList;
struct FrontendNodeRow;
int_t<std::uint32_t> __latency_fn_frontend_node_lists_append(shared_p<FrontendNodeList> list, FrontendNodeRow row);
}
