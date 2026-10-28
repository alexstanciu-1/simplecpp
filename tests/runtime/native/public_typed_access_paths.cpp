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
    // Prefix calls contain only saved keys, and retain no interior storage.
    int reached=0;
    if (matrix_ready(matrix,uint64(9))) ++reached;
    CHECK(reached==0);
    CHECK(matrix_ready(matrix,uint64(0)));
    matrix.clear(); // A later key expression changes the root.
    CHECK(!matrix_ready(matrix,uint64(0)));
    rows.set(key,make_row(int64(2)));
    CHECK(rows_ready(rows,key));
    rows_erase(rows,key);
    CHECK(!rows_ready(rows,key));
    const auto no_op_allocations=allocations;
    rows_erase(rows,key);CHECK(allocations==no_op_allocations);
    path_table table;table.set(key,int64(4));
    path_tables tables;tables.append(table);
    const auto removal_allocations=allocations;
    tables_erase(tables,uint64(0),key);
    tables_erase(tables,uint64(9),key); // Missing intermediate is a no-op.
    tables_erase(tables,uint64(0),key); // Missing leaf is a no-op.
    CHECK(allocations==removal_allocations);
    CHECK(tables.at(uint64(0)).size()==uint64(0));
    CHECK(tables.size()==uint64(1)); // Do not prune an empty parent.
    matrix.append(make_row(int64(100)));
    path_optionals optionals;optionals.append(path_optional());optionals.append(path_optional(int64(0)));
    vector_mixed boxes;boxes.append(mixed());boxes.append(mixed(int64(0)));boxes.append(mixed(string("")));
    path_results results;results.append(path_result(mixed()));
    results.append(path_result(error_value::make(string("failed"))));
    const auto observation_allocations=allocations;
    for(int i=0;i<100;++i) {
        CHECK(matrix_isset(matrix,uint64(0),uint64(0)));
        CHECK(!matrix_isset(matrix,uint64(0),uint64(9)));
        CHECK(!row_empty(matrix,uint64(0)));
        CHECK(!optional_isset(optionals,uint64(0)) && optional_isset(optionals,uint64(1)));
        CHECK(!box_isset(boxes,uint64(0)) && box_isset(boxes,uint64(1)));
        CHECK(box_empty(boxes,uint64(2)));
        CHECK(result_isset(results,uint64(0)) && !result_isset(results,uint64(1)));
    }
    CHECK(allocations==observation_allocations);
    failed=false;try { row_empty(matrix,uint64(9)); } catch(...) { failed=true; } CHECK(failed);
    failed=false;try { box_empty(boxes,uint64(0)); } catch(...) { failed=true; } CHECK(failed);
    failed=false;try { box_empty(boxes,uint64(1)); } catch(...) { failed=true; } CHECK(failed);
    auto detached=row_read(matrix,uint64(0));detached.set(uint64(0),int64(200));
    CHECK(matrix_read(matrix,uint64(0),uint64(0))==int64(100));
    auto retained=retained_row();CHECK(retained.at(uint64(0))==int64(17));
    std::puts("PASS: typed vector/hash paths mutate original storage, recheck RHS effects, preserve failures and owned reads; intermediate allocations=0");
}
