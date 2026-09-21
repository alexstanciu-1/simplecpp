#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendNodeList;
struct FrontendNodeListRef;
FrontendNodeListRef __latency_fn_frontend_node_lists_list_ref_for_generation(shared_p<FrontendNodeList> list, int_t<std::uint32_t> ownerSourceUnitId, int_t<std::uint32_t> listId, int_t<std::uint32_t> generationId);
}
