// The fixture driver prepends the concrete path API headers.
#include "public_api_guard.hpp"
#include <cstdlib>
#include <cstdio>
#include <new>
static unsigned allocations=0;
void *operator new(std::size_t n) { ++allocations;if(auto p=std::malloc(n?n:1)) return p;throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"typed path line %d: %s\n",__LINE__,#x);std::abort(); } } while(false)
using namespace scpp;
static path_row make_row(int64 value) { path_row row;row.append(value);return row; }
static path_row retained_row() { path_matrix matrix;matrix.append(make_row(int64(17)));return row_read(matrix,uint64(0)); }
int main() {
    path_matrix matrix;matrix.append(make_row(int64(3)));
    path_rows rows;const string key("a long nonnumeric key proving that lookup does not construct a temporary string for each step");
    rows.set(key,make_row(int64(4)));
    const auto before=allocations;
    for(int i=0;i<100;++i) {
        matrix_store(matrix,uint64(0),uint64(0),int64(i));rows_store(rows,key,uint64(0),int64(i+1));
        CHECK(matrix_read(matrix,uint64(0),uint64(0))==int64(i));CHECK(rows_read(rows,key,uint64(0))==int64(i+1));
    }
    CHECK(allocations==before); // Any intermediate vector copy would allocate.
    bool failed=false;
    try { matrix_store(matrix,uint64(0),uint64(9),int64(111)); } catch(...) { failed=true; }
    CHECK(failed && matrix_read(matrix,uint64(0),uint64(0))==int64(99));
    failed=false;try { rows_store(rows,key,uint64(9),int64(111)); } catch(...) { failed=true; }
    CHECK(failed && rows_read(rows,key,uint64(0))==int64(100));
    // Saved old value and keys survive RHS replacement; writeback resolves current storage.
    auto old=matrix_read(matrix,uint64(0),uint64(0));matrix.set(uint64(0),make_row(int64(700)));
    matrix_store(matrix,uint64(0),uint64(0),old+int64(1));CHECK(matrix_read(matrix,uint64(0),uint64(0))==int64(100));
    auto saved=rows_read(rows,key,uint64(0));CHECK(rows.remove(key));
    failed=false;try { rows_store(rows,key,uint64(0),saved+int64(1)); } catch(...) { failed=true; }
    CHECK(failed && rows.size()==uint64(0)); // Never recreates a deleted intermediate.
    auto detached=row_read(matrix,uint64(0));detached.set(uint64(0),int64(200));
    CHECK(matrix_read(matrix,uint64(0),uint64(0))==int64(100));
    auto retained=retained_row();CHECK(retained.at(uint64(0))==int64(17));
    std::puts("PASS: typed vector/hash paths mutate original storage, recheck RHS effects, preserve failures and owned reads; intermediate allocations=0");
}
