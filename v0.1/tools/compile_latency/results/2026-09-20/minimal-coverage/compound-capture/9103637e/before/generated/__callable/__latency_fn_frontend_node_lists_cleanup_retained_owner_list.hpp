#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendNodeListOwner;
struct RowListPublishResult;
RowListPublishResult __latency_fn_frontend_node_lists_cleanup_retained_owner_list(shared_p<FrontendNodeListOwner> owner);
}
