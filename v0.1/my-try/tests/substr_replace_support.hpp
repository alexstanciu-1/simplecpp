#pragma once
#include "lang/php/support/php_string.hpp"
#include <limits>
#include <stdexcept>
#include <vector>

namespace replace_probe {
using I = scpp::int_t<>;
using S = scpp::string_t;

// Independent oracle uses wide arithmetic and copies bytes individually. It does
// not call the runtime's normalization helpers or its append/substr construction.
inline std::string expected(const std::string& subject, const std::string& replacement, std::int64_t offset, std::int64_t length) {
 const __int128 size = subject.size();
 __int128 start = offset < 0 ? size + offset : offset;
 if (start < 0) start = 0;
 if (start > size) start = size;
 __int128 end = length < 0 ? size + length : start + length;
 if (length < 0 && end <= start) return subject;
 if (end > size) end = size;
 std::string result;
 for (__int128 i = 0; i < start; ++i) result.push_back(subject[static_cast<std::size_t>(i)]);
 for (char byte : replacement) result.push_back(byte);
 for (__int128 i = end; i < size; ++i) result.push_back(subject[static_cast<std::size_t>(i)]);
 return result;
}

template<class Three, class Four> long verify(Three replace3, Four replace4) {
 std::vector<std::string> subjects{"", "abcdef", "aaaa", "é中😀", std::string{"a\0b\0a",5}, std::string{"\xff\x80" "a",3}};
 std::vector<std::string> replacements{"", "X", "é", std::string{"\0X",2}, std::string{"\xff",1}};
 long count = 0;
 for (const auto& subject : subjects) {
  for (const auto& replacement : replacements) {
   S s{subject}, r{replacement};
   auto size = static_cast<std::int64_t>(subject.size());
   std::vector<std::int64_t> bounds{std::numeric_limits<std::int64_t>::min(),-size-1,-size,-1,0,1,size-1,size,size+1,std::numeric_limits<std::int64_t>::max()};
   for (auto offset : bounds) {
    if (replace3(s,r,I{offset}).native_value()!=expected(subject,replacement,offset,0)) throw std::runtime_error("omitted length mismatch");
    ++count;
    for (auto length : bounds) {
     if (replace4(s,r,I{offset},I{length}).native_value()!=expected(subject,replacement,offset,length)) throw std::runtime_error("window mismatch");
     ++count;
    }
   }
   if (s.native_value()!=subject || r.native_value()!=replacement) throw std::runtime_error("input changed");
  }
  S same{subject};
  if (replace3(same,same,I{1}).native_value()!=expected(subject,subject,1,0)
   || replace4(same,same,I{1},I{2}).native_value()!=expected(subject,subject,1,2)
   || same.native_value()!=subject) throw std::runtime_error("aliased input mismatch");
  count+=2;
 }
 return count;
}
}
