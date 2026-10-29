#include "scpp/datetime.hpp"
#include "../hash/allocation_probe.hpp"
#include <cstdio>
#include <cstring>
#include <locale>
#include <stdexcept>
#include <type_traits>
#include <limits>
using namespace scpp;
static void require(bool ok) { if(!ok) throw std::runtime_error("datetime consumer proof failed"); }
template<class E,class F> void rejects(F f) { try { f(); } catch(const E&) { return; } throw std::runtime_error("expected exception"); }
struct alien_dates : std::time_put<char> {
    iter_type do_put(iter_type out,std::ios_base&,char_type,const std::tm*,char,char) const override { *out++='X'; return out; }
};
int main() {
    static_assert(std::is_same_v<decltype(dt::monotonic_micros()),uint64>);
    static_assert(std::is_same_v<decltype(dt::monotonic_nanos()),uint64>);
    static_assert(std::is_same_v<decltype(dt::parse_iso_utc(string{})),result_int64>);
    auto count=allocation_probe::calls;
    const auto ms=dt::monotonic_millis().native_value();
    const auto us=dt::monotonic_micros().native_value();
    const auto ns=dt::monotonic_nanos().native_value();
    (void)dt::now_unix_seconds(); (void)dt::now_unix_millis();
    dt::sleep_millis(int64{0}); dt::sleep_millis(int64{-1}); dt::sleep_millis(int64{1});
    require(dt::monotonic_millis().native_value()>=ms);
    require(dt::monotonic_micros().native_value()>=us && dt::monotonic_nanos().native_value()>=ns);
    require(allocation_probe::calls==count);
    auto epoch=dt::parse_iso_utc(string{"1970-01-01T00:00:00Z"});
    require(epoch.has_value().native_value() && epoch.value().native_value()==0);
    auto rendered=dt::format_iso_utc(epoch.value());
    require(rendered.size().native_value()==20 && std::strcmp(rendered.data(),"1970-01-01T00:00:00Z")==0);
    string text{"2024-02-29T12:34:56Z"};
    count=allocation_probe::calls;
    auto parsed=dt::parse_iso_utc(text);
    const auto result_cost=allocation_probe::calls-count;
    // Current result_int64 owns one pimpl allocation; borrowed input adds none.
    require(result_cost==1 && parsed.value().native_value()==1709210096LL);
    auto error=dt::parse_iso_utc(string{"2023-02-29T00:00:00Z"});
    require(error.has_error().native_value() && !error.has_value().native_value());
    require(error.error().get_message().size().native_value()>0);
    auto original=std::locale();
    std::locale::global(std::locale{original,new alien_dates});
    auto unaffected=dt::format_iso_utc(int64{0});
    std::locale::global(original);
    require(std::strcmp(unaffected.data(),"1970-01-01T00:00:00Z")==0);
    for(const char* input : {"0000-01-01T00:00:00Z","9999-12-31T23:59:59Z","1969-12-31T23:59:59Z"}) {
        auto value=dt::parse_iso_utc(string{input}); require(value.has_value().native_value());
#ifndef _WIN32
        require(std::strcmp(dt::format_iso_utc(value.value()).data(),input)==0);
#endif
    }
    rejects<std::runtime_error>([]{ (void)dt::format_iso_utc(int64{253402300800LL}); });
    string bad{"not a date"};
    for(const auto* input : {&text,&bad}) {
        for(long fail=0;;++fail) {
            allocation_probe::fail_after=fail;
            bool failed=false;
            try { (void)dt::parse_iso_utc(*input); } catch(const std::bad_alloc&) { failed=true; }
            allocation_probe::fail_after=-1;
            if(!failed) break;
        }
    }
    for(long fail=0;;++fail) {
        allocation_probe::fail_after=fail;
        bool failed=false;
        try { (void)dt::format_iso_utc(int64{0}); } catch(const std::bad_alloc&) { failed=true; }
        allocation_probe::fail_after=-1;
        if(!failed) break;
    }
    require(std::strcmp(text.data(),"2024-02-29T12:34:56Z")==0);
    std::printf("datetime: eight-function consumer, independent results, allocation failures; successful parse allocations: %zu\n",result_cost);
}
