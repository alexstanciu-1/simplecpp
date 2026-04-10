#pragma once

#include "scpp/dynamic_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/string_t.hpp"

namespace scpp::php {

// Decodes one JSON document directly into the current Prism++ runtime value model.
// How: scalars stay inline in mixed_t, arrays become shared hash_t<mixed_t>, and objects become
// dynamic_t so decode matches the same visible model as hand-written object casts in Simple C++.
[[nodiscard]] mixed_t json_decode(const string_t &json);

// Encodes one runtime value into JSON text.
// How: packed hash_t values become JSON arrays and non-packed hash_t / dynamic_t values become
// JSON objects.
[[nodiscard]] string_t json_encode(const mixed_t &value);
[[nodiscard]] string_t json_encode(const hash_t<mixed_t> &value);
[[nodiscard]] string_t json_encode(const shared_p<hash_t<mixed_t>> &value);

} // namespace scpp::php
