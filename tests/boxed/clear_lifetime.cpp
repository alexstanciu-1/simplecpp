#include "boxed_value.hpp"
#include <cassert>
#include <stdexcept>
using namespace scpp::detail;
using Mixed = boxed_value<boxed_family::mixed>;
using Dynamic = boxed_value<boxed_family::dynamic>;

// Exercise the storage owner directly, including cycles which clear must release.
int main() {
    for (int run = 0; run != 1000; ++run) {
        auto m = Mixed::container();
        m.set(0, Mixed::string("heap backed bytes retained by a copied value"));
        auto copy = m;
        auto saved = m.read(0);
        copy.clear();
        assert(copy.container_size() == 0 && m.container_size() == 1);
        m.clear();
        assert(saved.get_string() == "heap backed bytes retained by a copied value");
        auto d = Dynamic::container();
        auto alias = d;
        d.set("self", d);
        auto child = Dynamic::container();
        child.set("parent", d);
        d.set("child", child);
        auto limit = d.iteration_limit();
        std::int64_t position = 0;
        Dynamic value;
        assert(d.iteration_next(position, limit, value));
        alias.clear();
        assert(d.container_size() == 0);
        assert(!d.iteration_next(position, limit, value));
        alias.clear();
        d.append(Dynamic::integer(7));
        assert(alias.read(0).get_integer() == 7);
        d.clear();
        try { (void)d.require(0); assert(false); }
        catch (const std::out_of_range&) {}
        assert(d.container_size() == 0);
        d.set(0, Dynamic{});
        assert(d.require(0).type() == Dynamic::kind::null);
        d.clear();
        try { Mixed{}.clear(); assert(false); }
        catch (const std::runtime_error&) {}
    }
}
