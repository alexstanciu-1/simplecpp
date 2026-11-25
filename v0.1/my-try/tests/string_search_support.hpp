#pragma once
#include "lang/php/support/php_string.hpp"
#include <limits>
#include <stdexcept>
#include <vector>

namespace search_probe {
using I = scpp::int_t<>;
using S = scpp::string_t;

// Independent byte-by-byte oracle. Wide arithmetic avoids sharing normalization
// implementation with the provider, including its signed-minimum boundary.
inline std::int64_t expected(const std::string& h, const std::string& n, std::int64_t offset, bool reverse) {
 const __int128 start = offset < 0 ? static_cast<__int128>(h.size()) + offset : offset;
 if (start < 0 || start > h.size()) return -2;
 std::int64_t answer = -1;
 for (std::size_t p = 0; p <= h.size(); ++p) {
  if ((!reverse || offset >= 0) ? p < start : p > start) continue;
  if (n.size() > h.size() - p) continue;
  bool equal = true;
  for (std::size_t j = 0; j < n.size(); ++j) if (h[p+j] != n[j]) equal = false;
  if (equal) { answer = static_cast<std::int64_t>(p); if (!reverse) break; }
 }
 return answer;
}

template<class Call> void check(Call call, std::int64_t wanted) {
 try {
  const auto actual = call();
  if (wanted == -2) throw std::runtime_error("missing ValueError");
  if (wanted == -1) {
   if (!actual.is_false().native_value() || actual.has_value().native_value()) throw std::runtime_error("missing false sentinel");
  } else if (!actual.has_value().native_value() || actual.value().native_value() != wanted) {
   throw std::runtime_error("wrong byte position");
  }
 } catch (const scpp::ValueError&) {
  if (wanted != -2) throw;
 }
}

template<class F2, class F3, class R2, class R3>
long verify(F2 forward2, F3 forward3, R2 reverse2, R3 reverse3) {
 std::vector<std::string> texts{"", "abc", "banana", "aaaa", "éaéa", "é中😀", std::string{"a\0b\0a",5}, std::string{"\xff\x80" "a",3}};
 std::vector<std::string> needles{"", "a", "na", "aa", "abcdef", "é", "中", std::string{"\xa9",1}, std::string{"\0",1}, std::string{"\xff",1}};
 long count = 0;
 for (const auto& h : texts) for (const auto& n : needles) {
  S haystack{h}, needle{n};
  check([&]{return forward2(haystack,needle);}, expected(h,n,0,false));
  check([&]{return reverse2(haystack,needle);}, expected(h,n,0,true));
  count += 2;
  auto size = static_cast<std::int64_t>(h.size());
  for (auto offset : {std::numeric_limits<std::int64_t>::min(), -size-1, -size, std::int64_t{-1}, std::int64_t{0}, std::int64_t{1}, size-1, size, size+1, std::numeric_limits<std::int64_t>::max()}) {
   check([&]{return forward3(haystack,needle,I{offset});}, expected(h,n,offset,false));
   check([&]{return reverse3(haystack,needle,I{offset});}, expected(h,n,offset,true));
   count += 2;
  }
  if (haystack.native_value()!=h || needle.native_value()!=n) throw std::runtime_error("input changed");
 }
 return count;
}

inline void text_controls() {
 S h{"éaéa"}, n{"a"};
 if (scpp::php::strpos(h,n).value().native_value()!=1 || scpp::php::strrpos(h,n).value().native_value()!=3
  || scpp::php::strpos(h,n,I{-1}).value().native_value()!=3 || scpp::php::strrpos(h,n,I{-2}).value().native_value()!=1)
  throw std::runtime_error("historical code-point adapters changed");
 try { scpp::str::byte_find(h,n,I{-1}); throw std::runtime_error("explicit byte_find policy changed"); }
 catch (const scpp::ValueError&) {}
}
}
