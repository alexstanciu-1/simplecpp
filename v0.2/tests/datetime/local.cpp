#include "scpp/datetime.hpp"
#include "../hash/allocation_probe.hpp"
#include <cstdio>
#include <cstring>
#include <ctime>
#include <limits>
#include <locale>
#include <stdexcept>
#include <string>
using namespace scpp;
void require(bool ok) { if(!ok) throw std::runtime_error("local datetime proof failed"); }
std::string bytes(const string& value) { return {value.data(),static_cast<std::size_t>(value.size().native_value())}; }
long long parse(const char* text) { auto p=dt::parse_common_local(string{text}); require(p.has_value().native_value()); return p.value().native_value(); }
std::string format(const char* pattern,long long time) { return bytes(dt::format_local(string{pattern},int64{time})); }
struct alien_numbers : std::num_put<char> {
    iter_type do_put(iter_type out,std::ios_base&,char_type,long) const override { *out++='X'; return out; }
};
int main(int argc,char** argv) {
    require(argc==2);
    const bool utc=std::strcmp(argv[1],"utc")==0;
    const bool dst=std::strcmp(argv[1],"dst")==0;
    require(parse("1970-01-01T00:00:00Z")==0); // Z remains UTC under any TZ.
    require(format("U",-1)=="-1");
    if(utc) {
        require(parse("1969-12-31 23:59:59")==-1);
        require(parse("1969-12-31T23:59:59")==-1);
        require(parse("1970-01-01")==0);
        require(format("Y",253402300800LL)=="10000");
        const auto stamp=parse("2024-02-29 03:04:05");
        require(format("Y y m n d j H G i s U",stamp)=="2024 24 02 2 29 29 03 3 04 05 1709175845");
        require(format("\\Y \\m \\U Q",stamp)=="Y m U Q");
    }
    if(dst) {
        const auto gap=parse("2024-03-10 02:30:00");
        require(gap==1710052200LL || gap==1710055800LL);
        const auto rendered=format("Y-m-d H:i:s",gap);
        require(rendered=="2024-03-10 01:30:00" || rendered=="2024-03-10 03:30:00");
        const auto overlap=parse("2024-11-03 01:30:00");
        require(overlap==1730611800LL || overlap==1730615400LL);
        require(format("Y-m-d H:i:s",overlap)=="2024-11-03 01:30:00");
        // Real -1 in this host's local zone, not only UTC.
        require(parse("1969-12-31 18:59:59")==-1);
    }
    if(!utc && !dst) {
        require(parse("1970-01-01 00:00:00")==39600);
        require(format("Y-m-d H:i:s",0)=="1969-12-31 13:00:00");
        require(parse("1969-12-31 12:59:59")==-1);
    }
    for(const char* bad : {"", "2024-02-30", "1900-02-29", "2024-01-01 24:00:00", "2024-01-01T00:00:60",
        "2024-01-01 ", "2024-01-01T00:00:00z", "2024-01-01T00:00:00+00:00", "next Tuesday"})
        require(dt::parse_common_local(string{bad}).has_error().native_value());
    std::string valid="2024-02-29 12:34:56";
    for(std::size_t i=0;i<valid.size();++i) {
        auto text=valid; text[i]='\0';
        require(dt::parse_common_local(string{text.data(),int64{static_cast<long long>(text.size())}}).has_error().native_value());
    }
    const char pattern[]={'Y','\0','U','\\'};
    auto binary=dt::format_local(string{pattern,int64{4}},int64{0});
    auto expected=format("Y",0)+std::string(1,'\0')+"0\\";
    require(bytes(binary)==expected);
    require(format("",0).empty());
    require(bytes(dt::format_local_now(string{"\\x"}))=="x");
    const auto previous=std::locale();
    auto ordinary=format("Y-m-d H:i:s U",0);
    std::locale::global(std::locale{previous,new alien_numbers});
    auto alien=format("Y-m-d H:i:s U",0);
    std::locale::global(previous); require(ordinary==alien);
    bool threw=false;
    try { (void)dt::format_local(string{"U"},int64{std::numeric_limits<long long>::max()}); }
    catch(const std::runtime_error&) { threw=true; }
    require(threw);
    string input{"2024-02-29 12:34:56"};
    string output_pattern{"Y-m-d H:i:s Y-m-d H:i:s Y-m-d H:i:s"};
    string invalid_input{"2024-02-30"};
    for(int operation=0;operation<4;++operation) {
        for(long fail=0;;++fail) {
            allocation_probe::fail_after=fail; bool failed=false;
            try {
                if(operation==0) (void)dt::parse_common_local(input);
                else if(operation==1) (void)dt::format_local(output_pattern,int64{0});
                else if(operation==2) (void)dt::format_local_now(output_pattern);
                else (void)dt::parse_common_local(invalid_input);
            } catch(const std::bad_alloc&) { failed=true; }
            allocation_probe::fail_after=-1;
            if(!failed) break;
        }
    }
    require(bytes(input)==valid && bytes(output_pattern)=="Y-m-d H:i:s Y-m-d H:i:s Y-m-d H:i:s");
    auto cost=allocation_probe::calls;
    auto parsed=dt::parse_common_local(input);
    cost=allocation_probe::calls-cost;
    require(parsed.has_value().native_value() && cost==1);
    auto copy=dt::format_local(output_pattern,int64{0});
    const auto expected_copy=bytes(copy); output_pattern=string{"changed"};
    require(bytes(copy)==expected_copy);
    std::printf("local datetime %s: tokens/bytes, host policy, -1, failure proofs pass; parse allocations=%zu\n",argv[1],cost);
}
