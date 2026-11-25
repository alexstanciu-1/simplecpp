#include <stdio.h>
#include <stdlib.h>
using namespace scpp;
#ifdef CONCRETE_PROBE
using Number = I;
using Numbers = V_I;
using Map = H_I_string_t;
using IntMap = H_bool_t_I;
using Lines = V_P_JournalLine;
static int64_t native(Number n) {return n.value;}
static auto new_line() {return create_JournalLine();}
static string_t text(Number n) {return convert_string_t(n);}
#else
using Number = int_t<>;
using Numbers = vector_t<Number>;
using Map = hash_t<Number>;
using IntMap = hash_t<bool_t,Number>;
using Lines = vector_t<shared_p<JournalLine>>;
static int64_t native(Number n) {return n.native_value();}
static auto new_line() {return create<JournalLine>();}
static string_t text(Number n) {return cast<string_t>(n);}
#endif
#define CHECK(x) do {if(!static_cast<bool>(x)) {fprintf(stderr,"probe failed at %d\n",__LINE__);abort();}} while(0)
int concrete_probes() {
    CHECK(native(str::length(string_t("é🙂")))==2);
    CHECK(native(str::length(string_t("\xff\x80")))==2);
    CHECK(native(str::length(string_t("\xc0\xaf")))==2);
    CHECK(native(str::length(string_t("\xed\xa0\x80")))==3);
    Numbers values;
    for(int i=0;i<300;++i) values.push_back(Number(i));
    values.push_back(values.at(Number(0)));
    Numbers copied=values;
    copied.at(Number(0))=Number(999);
    CHECK(values.at(Number(0))==Number(0));
    CHECK(copied.at(Number(0))==Number(999));
    Numbers assigned;assigned=copied;
    assigned=assigned;
    CHECK(assigned.at(Number(0))==Number(999));
    bool failed=false;try {values.at(Number(-1));} catch(...) {failed=true;} CHECK(failed);
    failed=false;try {values.at(Number(301));} catch(...) {failed=true;} CHECK(failed);
    Map map;
    for(int i=0;i<300;++i) map[text(Number(i))]=Number(i);
    Map snapshot=map;
    for(int i=0;i<300;i+=2) CHECK(map.remove(text(Number(i))));
    CHECK(php::count(map)==Number(150));
    CHECK(php::count(snapshot)==Number(300));
    for(int i=0;i<300;++i) {
        CHECK(static_cast<bool>(php::isset(map,text(Number(i))))==(i%2!=0));
        CHECK(snapshot.at(text(Number(i)))==Number(i));
    }
    for(int i=0;i<300;i+=2) map[text(Number(i))]=Number(i+1000);
    int order=1;
    for(auto entry:foreach_range(map)) {
        if(order==301) order=0;
        CHECK(entry.key()==text(Number(order)));
        CHECK(entry.value_copy()==Number(order+(order%2 ? 0 : 1000)));
        order+=2;
    }
    CHECK(order==300);
    Map assigned_map;assigned_map=map;assigned_map=assigned_map;
    CHECK(assigned_map.at(text(Number(0)))==Number(1000));
    IntMap intmap;
    for(int i=-100;i<100;++i) intmap[Number(i)]=bool_t(i%2!=0);
    CHECK(php::count(intmap)==Number(200));
    for(int i=-100;i<100;++i) CHECK(static_cast<bool>(intmap.at(Number(i)))==(i%2!=0));
    auto first=new_line();first->debit=Number(42);
    Lines lines;lines.push_back(first);Lines other=lines;
    other.at(Number(0))->debit=Number(7);
    CHECK(first->debit==Number(7));
    other.at(Number(0))=new_line();
    CHECK(lines.at(Number(0))->debit==Number(7));
    CHECK(other.at(Number(0))->debit==Number(0));
    auto survivor=[&]() {Lines temp=lines;return temp.at(Number(0));}();
    CHECK(survivor.operator->()==first.operator->());
    try {Lines temp=lines;temp.push_back(new_line());throw 1;} catch(int) {}
    CHECK(first->debit==Number(7));
    failed=false;try {auto unused=Number(1)/Number(0);(void)unused;} catch(...) {failed=true;} CHECK(failed);
    return 0;
}
#ifdef PROBE_MAIN
int main() {concrete_probes();puts("CONTAINER_PROBES_PASS");}
#endif
