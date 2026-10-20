#include "scpp/lang/php.hpp"
#include <chrono>
#include <iostream>
using namespace scpp;
// Exact old emitted body, with diagnostic call-depth guard disabled for both paths.
__attribute__((noinline)) string_t old_join(const vector_t<string_t>& parts) {
    auto result = string_t("");
    auto separator = string_t("");
    auto& source = parts;
    for (auto entry : foreach_range(source)) {
        auto part = entry.value_copy();
        result = (result + cast<string_t>((cast<string_t>(separator) + cast<string_t>(part))));
        separator = string_t(", ");
    }
    return result;
}
__attribute__((noinline)) string_t new_join(const vector_t<string_t>& parts) {
    return php::implode(string_t(", "), parts);
}
int main() {
    for (auto count : {3, 128, 2048}) {
        vector_t<string_t> parts;
        for (int i=0; i<count; ++i) parts.push_back(string_t(std::string(count==3 ? 12 : 80, char('a'+i%20))));
        if (old_join(parts).native_value()!=new_join(parts).native_value()) return 1;
        const int repeats=count==3 ? 100000 : count==128 ? 3000 : 50;
        for (int trial=0;trial<7;++trial) {
            for(int turn=0;turn<2;++turn) {
                bool old=(turn+trial)%2==0;
                auto start=std::chrono::steady_clock::now();
                std::size_t checksum=0;
                for(int k=0;k<repeats;++k) {
                    auto result=old ? old_join(parts) : new_join(parts);
                    asm volatile("" : : "g"(&result) : "memory");
                    checksum+=result.byte_size();
                }
                auto ns=std::chrono::duration_cast<std::chrono::nanoseconds>(std::chrono::steady_clock::now()-start).count();
                std::cout<<count<<","<<(old?"old":"join")<<","<<trial<<","<<repeats<<","<<ns<<","<<checksum<<"\n";
            }
        }
    }
}
