#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendNodeListOwner;
struct FrontendNodeListRef;
FrontendNodeListRef __latency_fn_frontend_node_lists_current_list_ref(shared_p<FrontendNodeListOwner> owner);
}
