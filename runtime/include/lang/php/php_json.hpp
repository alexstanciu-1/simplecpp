#pragma once

#include "scpp/mixed_t.hpp"
#include "scpp/string_t.hpp"

namespace scpp::php {

[[nodiscard]] string_t json_encode(const mixed_t &value);
[[nodiscard]] mixed_t json_decode(const string_t &json);

} // namespace scpp::php
