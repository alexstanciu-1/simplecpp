#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendNodeList;
class FrontendNodeListOwner;
struct RowListPublishResult;
RowListPublishResult __latency_fn_frontend_node_lists_publish_owner_list(shared_p<FrontendNodeListOwner> owner, shared_p<FrontendNodeList> replacementRows);
}
