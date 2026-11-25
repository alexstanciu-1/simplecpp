#include <scpp/vectors.hpp>
#include <cassert>
#include <stdexcept>
#include <string_view>

using Int = scpp::int64;
namespace App {
scpp::vector_int64 each_batch_(scpp::vector_int64&, scpp::vector_int64&, Int, Int, Int);
}

#include "mt_each_probe.hpp"

int main() { check_each(); }
