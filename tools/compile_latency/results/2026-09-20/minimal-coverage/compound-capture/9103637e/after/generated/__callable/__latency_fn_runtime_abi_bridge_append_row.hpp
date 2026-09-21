#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class RuntimeAbiBridgeArtifact;
class SemanticRuntimeAbiBridgeDescriptorRow;
void __latency_fn_runtime_abi_bridge_append_row(shared_p<RuntimeAbiBridgeArtifact>& artifact, shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row);
}
