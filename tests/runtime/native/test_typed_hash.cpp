#include "public_api_guard.hpp"
#include "scpp/hash_strings.hpp"
#include "scpp/hash_integers.hpp"
#include "scpp/hash_vectors.hpp"
#include "scpp/hash_records.hpp"
#include "scpp/exception_info.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
#include <utility>

using namespace scpp;
static bool counting = false;
static unsigned allocations = 0;
void begin_hash_allocations() { counting = true; allocations = 0; }
unsigned end_hash_allocations() { counting = false; return allocations; }
void *operator new(std::size_t size)
{
    if (counting) ++allocations;
    if (void *memory = std::malloc(size ? size : 1)) return memory;
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }
unsigned native_hash_allocations();
void native_integer_append_proof();
static void check(bool value, int line)
{
    if (!value) { std::fprintf(stderr,"typed hash check failed: %d\n",line); std::abort(); }
}
#define CHECK(x) check(static_cast<bool>(x), __LINE__)

static auto surviving_cursor()
{
    hash_strings values;
    values.set(string("key"),string("owned after the original hash is destroyed"));
    return values.iterate();
}

int main()
{
    native_integer_append_proof();
    const auto native_count = native_hash_allocations();
    begin_hash_allocations();
    {
        hash_integers values;
        for (int i=0; i<12; ++i) (void)values.append(string("small"));
        auto found = values.find(int64(4));
        auto copied = values;
        auto cursor = values.iterate();
        (void)cursor.value();
        values.clear();
        cursor.next();
        (void)cursor.value();
    }
    const auto public_count = end_hash_allocations();
    CHECK(native_count == public_count);
    begin_hash_allocations();
    { hash_strings empty_hash; nullable_string absent; }
    CHECK(end_hash_allocations() == 0);
    hash_strings values;
    const string a("a"), b("b");
    values.set(a,string("one")).set(b,string("two"));
    begin_hash_allocations();
    auto missing = values.find(string("missing"));
    CHECK(!missing.has_value());
    CHECK(end_hash_allocations() == 0);
    CHECK(values.size() == uint64(2));
    bool rejected = false;
    try { (void)values[string("missing")]; } catch (...) { rejected = true; }
    CHECK(rejected && values.size() == uint64(2));
    string destination("unchanged");
    CHECK(!take(destination,missing) && destination.equals("unchanged"));
    rejected=false;
    try { (void)missing.value(); } catch (...) {
        rejected=true;
        CHECK(capture_runtime_exception().code().equals("invalid_nullable_unwrap_empty"));
    }
    CHECK(rejected);
    auto found = values.find(a);
    values.set(a,string("updated"));
    CHECK(found.value().equals("one"));
    CHECK(was_found(found) && !is_nullopt(found));
    CHECK(take(destination,found) && destination.equals("one"));
    found.reset();
    CHECK(found.value_or(string("fallback")).equals("fallback"));
    auto copy=values;
    copy.clear();
    CHECK(values.size()==uint64(2));
    auto cursor=values.iterate();
    CHECK(cursor.key().equals("a"));
    CHECK(cursor.value().equals("updated"));
    values.set(b,string("visible before preservation"));
    values.set(string("c"),string("not visited"));
    values.set(b,string("hidden after preservation"));
    cursor.next();
    CHECK(cursor.key().equals("b"));
    CHECK(cursor.value().equals("visible before preservation"));
    cursor.next();
    CHECK(!cursor.valid());
    CHECK(values.remove(a) && !values.remove(a));
    auto escaped=surviving_cursor();
    CHECK(escaped.key().equals("key"));
    CHECK(escaped.value().equals("owned after the original hash is destroyed"));
    hash_integers integers;
    integers.set(int64(-9),string("negative"));
    CHECK(integers.append(string("zero"))==int64(0));
    integers.set(int64(8),string("eight"));
    CHECK(integers.append(string("nine"))==int64(9));
    integers.set(int64(__INT64_MAX__),string("limit"));
    rejected=false;
    try { (void)integers.append(string("overflow")); } catch (...) { rejected=true; }
    CHECK(rejected && integers.size()==uint64(5));
    test_vector_string inner;
    inner.append(string("original"));
    hash_vectors nested;
    nested.set(a,inner);
    inner.clear();
    auto extracted=nested.at(a);
    extracted.clear();
    auto optional=nested.find(a);
    nested.clear();
    CHECK(optional.value().at(uint64(0)).equals("original"));
    hash_records records;
    records.set(a,record{int64(42)});
    auto record_copy=records.find(a);
    records.clear();
    CHECK(record_copy.value().value==int64(42));
    std::printf("PASS: hash allocations native=%u public=%u; storage hash=%zu nullable=%zu cursor=%zu bytes\n",
        native_count,public_count,sizeof(hash_strings),sizeof(nullable_string),sizeof(cursor));
}
