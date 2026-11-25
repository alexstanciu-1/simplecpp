#include "modules/strings/strings.hpp"
#include <chrono>
#include <fstream>
#include <iostream>
#include <sstream>
#ifdef ALLOCATION_PROBE
#include <cstdlib>
static bool count_allocations=false;
static std::size_t allocations=0, allocated_bytes=0;
void* operator new(std::size_t n) {
    if(count_allocations) { ++allocations; allocated_bytes+=n; }
    if(void* p=std::malloc(n)) return p;
    throw std::bad_alloc();
}
void operator delete(void* p) noexcept { std::free(p); }
void operator delete(void* p,std::size_t) noexcept { std::free(p); }
#endif

using Clock = std::chrono::steady_clock;
using scpp::string_t;

// Same append/search algorithm as the runtime, with controlled allocation variants.
std::string replace_native(const std::string& s, const std::string& n,
                           const std::string& r, int mode) {
    if (n.empty()) return s;
    std::size_t pos;
    std::string out;
    if (mode != 0) out.reserve(s.size());
    std::size_t cursor = 0;
    while (cursor < s.size()) {
        pos = s.find(n, cursor);
        if (pos == std::string::npos) { out.append(s, cursor, s.size()-cursor); break; }
        out.append(s, cursor, pos-cursor);
        out += r;
        cursor = pos+n.size();
    }
    return out;
}

int main(int argc, char** argv) {
    if (argc != 3) return 2;
    const std::string backend = argv[2];
    std::ifstream file(argv[1]);
    std::string line;
    while (std::getline(file, line)) {
        std::vector<std::string> f;
        std::stringstream row(line);
        std::string field;
        while (std::getline(row, field, '\t')) f.push_back(field);
        if (f.size() != 7) return 3;
        string_t s(f[2]), t(f[3]), n(f[4]), r(f[5]);
        const bool replace = f[1] == "replace";
        const bool baseline = f[1] == "baseline";
        auto replacement = [&]() {
            if (backend == "runtime_snapshot") {
                auto nn=n, rr=r, ss=s;
                asm volatile("" : : "g"(&nn), "g"(&rr), "g"(&ss) : "memory");
                return scpp::str::replace(nn,rr,ss).release_native();
            }
            if (backend == "runtime") return scpp::str::replace(n, r, s).release_native();
            return replace_native(s.native_value(), n.native_value(), r.native_value(),
                                  backend == "cpp_reserved" ? 1 : 0);
        };
        auto compare = [&]() {
            if (backend == "runtime_snapshot") {
                auto ss=s, tt=t;
                asm volatile("" : : "g"(&ss), "g"(&tt) : "memory");
                return int(scpp::str::substr_compare(ss,tt,scpp::int_t<>(0)).native_value());
            }
            if (backend == "runtime") return int(scpp::str::substr_compare(s,t,scpp::int_t<>(0)).native_value());
            return s.native_value().compare(t.native_value());
        };
        if (replace ? replacement() != f[6] : !baseline && ((compare()>0)-(compare()<0) != std::stoi(f[6]))) return 4;
#ifdef ALLOCATION_PROBE
        allocations=allocated_bytes=0;
        count_allocations=true;
        if(replace) { auto out=replacement(); asm volatile("" : : "g"(&out) : "memory"); }
        else if(!baseline) { auto result=compare(); asm volatile("" : : "g"(result) : "memory"); }
        else if(backend=="runtime_snapshot") { auto ss=s; asm volatile("" : : "g"(&ss) : "memory"); }
        count_allocations=false;
        std::cout << f[0] << ',' << backend << ',' << allocations << ',' << allocated_bytes << '\n';
        continue;
#endif
        auto batch = [&](std::uint64_t count) {
            std::uint64_t sum = 0;
            auto start = Clock::now();
            for (std::uint64_t i=0; i<count; ++i) {
                asm volatile("" : : "g"(&s), "g"(&t), "g"(&n), "g"(&r) : "memory");
                if (baseline) {
                    if (backend == "runtime_snapshot") {
                        auto ss=s;
                        asm volatile("" : : "g"(&ss) : "memory");
                        sum += ss.size();
                    } else sum += s.size();
                }
                else if (replace) {
                    auto out = replacement();
                    asm volatile("" : : "g"(&out) : "memory");
                    sum += out.size();
                } else { const auto result = compare(); sum += 1+(result>0)-(result<0); }
            }
            auto ns = std::chrono::duration_cast<std::chrono::nanoseconds>(Clock::now()-start).count();
            return std::pair(ns,sum);
        };
        std::uint64_t count=1;
        while (batch(count).first < 20000000 && count < (1u<<26)) count *= 2;
        for (int trial=0; trial<7; ++trial) {
            auto [ns,sum] = batch(count);
            std::cout << f[0] << ',' << backend << ',' << trial << ',' << count << ',' << ns << ',' << sum << '\n';
        }
    }
}
