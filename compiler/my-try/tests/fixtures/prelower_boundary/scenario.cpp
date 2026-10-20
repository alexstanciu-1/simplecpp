#include "interface.hpp"
#include "instrument.hpp"
#include <utility>

// The same application is compiled against direct and opaque representations.
// No pointer survives a structural mutation which may invalidate its element.
int main() {
    begin(); { Integer x; set_integer(x, 42); require(read_integer(x)==42); }
    report("integer");
    begin(); { Value x; set_value(x, 9); require(read_value(x)==9); }
    report("overaligned_value");
    {
        Values values;
        begin(); reserve_values(values, 1); append_value(values, 7); report("seed");
        begin(); append_alias(values); report("alias_growth");
        require(size_values(values)==2 && read_leaf(leaf(values, 1))==7);
        begin();
        auto ref = leaf(values, 0);
        auto ro = const_leaf(values, 0);
        require(ref.address==ro.address);
        write_leaf(ref, 11);
        require(read_leaf(leaf(values, 0))==11 && read_const_leaf(ro)==11);
        report("borrow");
        begin(); { Values copy(values); write_leaf(leaf(copy, 0), 99);
            require(read_leaf(leaf(values, 0))==11);
            Values moved(std::move(copy)); require(size_values(moved)==2);
            copy = moved; copy = copy; moved = std::move(copy);
        } report("copy_move_cleanup");
        begin();
        copies_before_throw=1;
        bool caught=false;
        try { Values copy(values); } catch(const CopyFailure&) { caught=true; }
        copies_before_throw=-1;
        require(caught && live_values==2 && read_leaf(leaf(values, 0))==11);
        report("copy_failure_cleanup");
        Nested nested;
        append_row(nested, values);
        begin();
        auto r = row(nested, 0);
        auto element = row_leaf(r, 1);
        auto constant = const_nested_leaf(nested, 0, 1);
        require(element.address==constant.address);
        write_leaf(element, 23);
        require(read_const_leaf(constant)==23);
        require(read_leaf(leaf(values, 1))==7);
        require(nested_address(nested, 0, 1)==element.address);
        report("nested_borrow");
        begin(); { Nested copy(nested); write_leaf(row_leaf(row(copy, 0), 1), 55);
            require(read_const_leaf(constant)==23);
        } report("nested_copy");
        require(bounds_error(values));
        begin(); clear_values(values); report("clear");
    }
    require(live_values==0);
    begin(); {
        Handle h; make_handle(h, 31);
        require(handle_count(h)==1);
        { Handle copy(h); require(handle_count(h)==2); require(handle_address(h)==handle_address(copy));
          Handle moved(std::move(copy)); require(handle_count(h)==2);
          write_leaf(handle_leaf(moved), 32); require(read_leaf(handle_leaf(h))==32);
        }
        require(handle_count(h)==1);
    } report("shared_lifecycle");
    require(live_values==0);
    begin(); { Values result = make_values(); require(size_values(result)==2);
        require(read_leaf(leaf(result, 1))==17);
    } report("owned_result");
    require(live_values==0);
}
