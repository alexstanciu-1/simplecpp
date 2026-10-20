#include <cassert>
#include <cstdio>
#include "RowVector.hpp"
static int test_Row() {
    assert(Row::live()==0);
    int made=Row::made(), dropped=Row::dropped();
    {
        Row first(11);
        RowVector a; a.append(first);
        for(int i=0;i<32;++i) a.append(Row(i));
        a.append(a.get(0)); // aliased append across possible reallocation
        RowVector b(a);
        assert(a.size()==34 && b.size()==34);
        b.get(0).value(99);
        assert(first.value()==99 && a.get(0).value()==99);
        b.set(0,Row(77));
        assert(a.get(0).same(first) && !b.get(0).same(first));
        b.append(Row(55));
        assert(a.size()==34 && b.size()==35);
        RowVector assigned; assigned=b;
        RowVector moved(static_cast<RowVector&&>(assigned));
        assert(moved.size()==35 && moved.get(0).value()==77);
        moved=moved; // self-assignment retains contents
        assert(moved.size()==35);
        bool failed=false;
        try { a.get(1000); } catch(const BoundsError&) { failed=true; }
        assert(failed);
        auto early=[&]() { RowVector temporary(a); temporary.append(Row(8)); return temporary.get(0); };
        assert(early().same(first));
        int live=Row::live();
        try { RowVector temporary(a); temporary.append(Row(8)); throw 7; } catch(int) {}
        assert(Row::live()==live);
        assert(first.value()==99);
    }
    assert(Row::live()==0 && Row::made()-made==Row::dropped()-dropped);
    return 11;
}
#include "OtherVector.hpp"
static int test_Other() {
    assert(Other::live()==0);
    int made=Other::made(), dropped=Other::dropped();
    {
        Other first(11);
        OtherVector a; a.append(first);
        for(int i=0;i<32;++i) a.append(Other(i));
        a.append(a.get(0)); // aliased append across possible reallocation
        OtherVector b(a);
        assert(a.size()==34 && b.size()==34);
        b.get(0).value(99);
        assert(first.value()==99 && a.get(0).value()==99);
        b.set(0,Other(77));
        assert(a.get(0).same(first) && !b.get(0).same(first));
        b.append(Other(55));
        assert(a.size()==34 && b.size()==35);
        OtherVector assigned; assigned=b;
        OtherVector moved(static_cast<OtherVector&&>(assigned));
        assert(moved.size()==35 && moved.get(0).value()==77);
        moved=moved; // self-assignment retains contents
        assert(moved.size()==35);
        bool failed=false;
        try { a.get(1000); } catch(const BoundsError&) { failed=true; }
        assert(failed);
        auto early=[&]() { OtherVector temporary(a); temporary.append(Other(8)); return temporary.get(0); };
        assert(early().same(first));
        int live=Other::live();
        try { OtherVector temporary(a); temporary.append(Other(8)); throw 7; } catch(int) {}
        assert(Other::live()==live);
        assert(first.value()==99);
    }
    assert(Other::live()==0 && Other::made()-made==Other::dropped()-dropped);
    return 11;
}
int main() { int result=0;
result+=test_Row();
result+=test_Other();
std::printf("%d\n",result); }
