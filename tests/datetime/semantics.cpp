#include "datetime_native.hpp"
#include "datetime_semantics.hpp"
#include "../hash/allocation_probe.hpp"
#include <chrono>
#include <cstdio>
#include <limits>
#include <stdexcept>
#include <string>
using namespace scpp::detail::datetime;
static void require(bool ok) { if(!ok) throw std::runtime_error("datetime private proof failed"); }
template<class F> void rejects(F f) { try { f(); } catch(const std::runtime_error&) { return; } throw std::logic_error("expected range failure"); }
int main() {
    using i64=std::int64_t; using u64=std::uint64_t;
    using Sec=std::chrono::duration<i64>; using Ms=std::chrono::duration<i64,std::milli>;
    using UMs=std::chrono::duration<u64,std::milli>;
    const auto maximum=std::numeric_limits<i64>::max();
    require(checked_duration_count<Ms>(Sec{maximum/1000})==(maximum/1000)*1000);
    rejects([&]{ (void)checked_duration_count<Ms>(Sec{maximum/1000+1}); });
    rejects([&]{ (void)checked_duration_count<Ms>(Sec{std::numeric_limits<i64>::min()}); });
    require(checked_duration_count<Sec>(Ms{-1999})==-1);
    rejects([&]{ (void)checked_duration_count<UMs>(Sec{-1}); });
    require(checked_duration_count<UMs>(UMs{std::numeric_limits<u64>::max()})==std::numeric_limits<u64>::max());
    rejects([&]{ (void)checked_duration_count<Ms>(UMs{std::numeric_limits<u64>::max()}); });
    using TwoThirds=std::chrono::duration<u64,std::ratio<2,3>>;
    using USec=std::chrono::duration<u64>;
    const auto umax=std::numeric_limits<u64>::max();
    require(checked_duration_count<USec>(TwoThirds{umax})==(umax/3)*2+(umax%3)*2/3);
    require(sleep_chunk_millis(maximum)==86400000 && sleep_chunk_millis(-1)==0 && sleep_chunk_millis(7)==7);
    require(parse_iso_utc("0000-01-01T00:00:00Z").value==-62167219200LL);
    require(parse_iso_utc("9999-12-31T23:59:59Z").value==253402300799LL);
    require(parse_iso_utc("1969-12-31T23:59:59Z").value==-1);
    for (auto text : {"1900-02-29T00:00:00Z","2100-02-29T00:00:00Z","2024-00-01T00:00:00Z",
        "2024-13-01T00:00:00Z","2024-04-31T00:00:00Z","2024-01-00T00:00:00Z",
        "2024-01-01T24:00:00Z","2024-01-01T00:60:00Z","2024-01-01T00:00:60Z",
        "2024-01-01T00:00:00z","2024-01-01 00:00:00Z","10000-01-01T00:00:00Z",""}) require(parse_iso_utc(text).error);
    require(!parse_iso_utc("0000-02-29T00:00:00Z").error);
    require(!parse_iso_utc("2000-02-29T00:00:00Z").error);
    // Every field/delimiter: NUL is never an implicit terminator or accepted byte.
    std::string valid="2024-02-29T12:34:56Z";
    for(std::size_t i=0;i<valid.size();++i) { auto bad=valid; bad[i]='\0'; require(parse_iso_utc(bad).error); }
    require(parse_iso_utc(valid+"\n").error);
    rejects([&]{ (void)format_iso_utc(-62167219201LL); });
    rejects([&]{ (void)format_iso_utc(253402300800LL); });
    rejects([&]{ (void)format_iso_utc(maximum); });
    // Independent known epoch/calendar facts plus broad calendar round trips.
    require(parse_iso_utc("2024-02-29T12:34:56Z").value==1709210096LL);
    for(i64 t=-2208988800LL;t<4102444800LL;t+=86400*37) {
        const auto text=format_iso_utc(t);
        const auto parsed=parse_iso_utc({text.data(),text.size()});
        require(!parsed.error && parsed.value==t);
    }
    auto count=allocation_probe::calls;
    auto start=std::chrono::steady_clock::now();
    i64 sum=0;
    for(int i=0;i<100000;++i) sum+=parse_iso_utc(valid).value;
    auto elapsed=std::chrono::duration_cast<std::chrono::microseconds>(std::chrono::steady_clock::now()-start).count();
    require(sum==170921009600000LL && allocation_probe::calls==count);
    std::printf("datetime: 100000 borrowed native parses: %lld us, zero allocations\n",static_cast<long long>(elapsed));
    const std::string_view local_text="2024-02-29 12:34:56";
    const auto local_reference=parse_common_local(local_text);
    require(!local_reference.error);
    count=allocation_probe::calls; start=std::chrono::steady_clock::now();
    for(int i=0;i<10000;++i) {
        const auto local=parse_common_local(local_text);
        require(!local.error && local.value==local_reference.value);
    }
    elapsed=std::chrono::duration_cast<std::chrono::microseconds>(std::chrono::steady_clock::now()-start).count();
    require(allocation_probe::calls==count);
    std::printf("datetime: 10000 borrowed local parses: %lld us, zero C++ allocations\n",static_cast<long long>(elapsed));

}
