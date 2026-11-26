#pragma once
@includes@
#include "scpp/decoder.hpp"

namespace scpp::json {
error_t read_record(const string&, void*, void (*)(void*, Decoder&));
}
