#pragma once
@includes@
#include "scpp/decoder.hpp"

namespace scpp::json {
exception read_record(const string&, void*, void (*)(void*, Decoder&));
// For generated synchronous leaf readers. The consumer must keep text alive and
// unmodified until it finishes the document, including through other aliases.
exception read_leaf_record(const string&, void*, void (*)(void*, Decoder&));
}
