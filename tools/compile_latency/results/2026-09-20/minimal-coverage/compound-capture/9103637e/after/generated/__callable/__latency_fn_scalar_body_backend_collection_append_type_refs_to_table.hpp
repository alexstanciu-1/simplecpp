#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ScalarBodyBackendCollection;
struct TypeRefTable;
void __latency_fn_scalar_body_backend_collection_append_type_refs_to_table(TypeRefTable& typeRefs, shared_p<ScalarBodyBackendCollection> body);
}
