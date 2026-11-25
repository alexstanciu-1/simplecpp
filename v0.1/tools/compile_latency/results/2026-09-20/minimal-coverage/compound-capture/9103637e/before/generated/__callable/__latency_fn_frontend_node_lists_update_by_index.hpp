#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendNodeList;
struct FrontendNodeRow;
bool_t __latency_fn_frontend_node_lists_update_by_index(shared_p<FrontendNodeList> list, int_t<> index, FrontendNodeRow row);
}
