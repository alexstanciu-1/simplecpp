#include "scpp/support/table_t.hpp"

#include <cassert>
#include <cstdio>
#include <stdexcept>
#include <string>

using namespace scpp;

// ── helpers ──────────────────────────────────────────────────────────────────

static int g_pass = 0, g_fail = 0;

#define CHECK(expr) do { \
    if (expr) { ++g_pass; printf("  PASS  %s\n", #expr); } \
    else       { ++g_fail; printf("  FAIL  %s  [line %d]\n", #expr, __LINE__); } \
} while(0)

#define CHECK_THROWS(expr) do { \
    bool threw = false; \
    try { (expr); } catch (...) { threw = true; } \
    if (threw) { ++g_pass; printf("  PASS  throws: %s\n", #expr); } \
    else        { ++g_fail; printf("  FAIL  no throw: %s  [line %d]\n", #expr, __LINE__); } \
} while(0)

// ── section 1: value_t basics ────────────────────────────────────────────────

void test_value_t_basics() {
    printf("\n=== value_t basics ===\n");

    value_t v_null;
    CHECK(v_null.kind() == value_t::kind_t::null_v);
    CHECK(v_null.is_null().native_value());

    value_t v_null2(null_t{});
    CHECK(v_null2.is_null().native_value());

    value_t v_nullopt(nullopt_t{});       // sentinel equivalence → null_v
    CHECK(v_nullopt.kind() == value_t::kind_t::null_v);

    value_t v_nullptr(scpp::nullptr_t{});       // sentinel equivalence → null_v
    CHECK(v_nullptr.kind() == value_t::kind_t::null_v);

    value_t v_bool(bool_t{true});
    CHECK(v_bool.kind() == value_t::kind_t::bool_v);
    CHECK(v_bool.bool_value().native_value() == true);

    value_t v_int(int_t{42LL});
    CHECK(v_int.kind() == value_t::kind_t::int_v);
    CHECK(v_int.int_value().native_value() == 42LL);

    value_t v_float(float_t{3.14});
    CHECK(v_float.kind() == value_t::kind_t::float_v);

    value_t v_str(string_t{"hello"});
    CHECK(v_str.kind() == value_t::kind_t::string_v);
    CHECK(v_str.string_if() != nullptr);
    CHECK(v_str.string_if()->native_value() == "hello");

    // wrong-kind access throws
    CHECK_THROWS(v_int.bool_value());
    CHECK_THROWS(v_bool.int_value());
    CHECK_THROWS(v_str.float_value());

    // sizeof
    CHECK(sizeof(value_t) == 24);
}

// ── section 2: value_t ───────────────────────────────────────────────────

void test_value_t() {
    printf("\n=== value_t ===\n");

    value_t n_bool(bool_t{false});
    CHECK(n_bool.kind() == value_t::kind_t::bool_v);

    value_t n_int(int_t{99LL});
    CHECK(n_int.kind() == value_t::kind_t::int_v);
    CHECK(n_int.int_value().native_value() == 99LL);

    value_t n_float(float_t{2.71});
    CHECK(n_float.kind() == value_t::kind_t::float_v);

    // sentinels map to null for value_t.
    value_t n_null(null_t{});
    CHECK(n_null.kind() == value_t::kind_t::null_v);
    CHECK(n_null.is_null().native_value() == true);

    CHECK_THROWS(n_int.bool_value());
    CHECK(sizeof(value_t) == 24);
}

// ── section 3: table_t<value_t> - packed mode ───────────────────────────────

void test_packed_mode() {
    printf("\n=== table_t<value_t> packed mode ===\n");

    table_t<value_t> t;
    CHECK(t.empty().native_value());
    CHECK(t.is_packed().native_value());
    CHECK(t.size() == 0);

    auto k0 = t.append(value_t{int_t{10LL}});
    auto k1 = t.append(value_t{int_t{20LL}});
    auto k2 = t.append(value_t{int_t{30LL}});

    CHECK(k0.native_value() == 0);
    CHECK(k1.native_value() == 1);
    CHECK(k2.native_value() == 2);
    CHECK(t.size() == 3);
    CHECK(t.is_packed().native_value());
    CHECK(!t.empty().native_value());

    CHECK(t.has(int_t{0}).native_value());
    CHECK(t.has(int_t{1}).native_value());
    CHECK(!t.has(int_t{99}).native_value());

    CHECK(t.at(int_t{0}).int_value().native_value() == 10LL);
    CHECK(t.at(int_t{1}).int_value().native_value() == 20LL);
    CHECK(t.at(int_t{2}).int_value().native_value() == 30LL);

    CHECK_THROWS(t.at(int_t{5}));

    // find returns nullopt when not found
    auto mv_found = t.find(int_t{1});
    CHECK(was_found(mv_found).native_value());
    CHECK(mv_found.value().int_value().native_value() == 20LL);

    auto mv_miss = t.find(int_t{99});
    CHECK(!was_found(mv_miss).native_value());
    CHECK(is_nullopt(mv_miss).native_value());
}

// ── section 4: table_t<value_t> - string keys ───────────────────────────────

void test_string_keys() {
    printf("\n=== table_t<value_t> string keys ===\n");

    table_t<value_t> t;
    t.set(string_t{"name"},  value_t{string_t{"Alice"}});
    t.set(string_t{"score"}, value_t{int_t{100LL}});
    t.set(string_t{"pi"},    value_t{float_t{3.14159}});

    CHECK(!t.is_packed().native_value());   // string key forces associative
    CHECK(t.size() == 3);

    CHECK(t.has(string_t{"name"}).native_value());
    CHECK(!t.has(string_t{"missing"}).native_value());

    CHECK(t.at(string_t{"name"}).string_if()->native_value() == "Alice");
    CHECK(t.at(string_t{"score"}).int_value().native_value() == 100LL);

    auto mv = t.find(string_t{"pi"});
    CHECK(was_found(mv).native_value());

    auto mv2 = t.find(string_t{"nope"});
    CHECK(is_nullopt(mv2).native_value());
}

// ── section 5: mixed int + string keys ──────────────────────────────────────

void test_mixed_keys() {
    printf("\n=== mixed int + string keys ===\n");

    table_t<value_t> t;
    (void)t.append(value_t{int_t{1LL}});
    (void)t.append(value_t{int_t{2LL}});
    t.set(string_t{"x"}, value_t{string_t{"hello"}});
    (void)t.append(value_t{int_t{3LL}});   // appends at max-int-key+1 = 2

    CHECK(t.has(int_t{0}).native_value());
    CHECK(t.has(int_t{1}).native_value());
    CHECK(t.has(string_t{"x"}).native_value());
    CHECK(t.has(int_t{2}).native_value());
}

// ── section 6: remove ───────────────────────────────────────────────────────

void test_remove() {
    printf("\n=== remove ===\n");

    table_t<value_t> t;
    (void)t.append(value_t{int_t{100LL}});
    (void)t.append(value_t{int_t{200LL}});
    (void)t.append(value_t{int_t{300LL}});

    CHECK(t.remove(int_t{0}));
    CHECK(!t.has(int_t{0}).native_value());
    CHECK( t.has(int_t{1}).native_value());
    CHECK( t.has(int_t{2}).native_value());
    CHECK(!t.remove(int_t{0}));   // already gone

    // append after remove uses max-int-key+1 (= 3 here, not 0)
    auto k = t.append(value_t{int_t{400LL}});
    CHECK(k.native_value() == 3);
    CHECK(t.has(int_t{3}).native_value());

    // string key remove
    table_t<value_t> t2;
    t2.set(string_t{"a"}, value_t{int_t{1LL}});
    t2.set(string_t{"b"}, value_t{int_t{2LL}});
    CHECK(t2.remove(string_t{"a"}));
    CHECK(!t2.has(string_t{"a"}).native_value());
    CHECK( t2.has(string_t{"b"}).native_value());
}

// ── section 7: operator[] insert ────────────────────────────────────────────

void test_operator_bracket() {
    printf("\n=== operator[] ===\n");

    table_t<value_t> t;
    t[int_t{5}] = value_t{string_t{"five"}};
    t[string_t{"k"}] = value_t{bool_t{true}};

    CHECK(t.has(int_t{5}).native_value());
    CHECK(t.at(int_t{5}).string_if()->native_value() == "five");
    CHECK(t.has(string_t{"k"}).native_value());
    CHECK(t.at(string_t{"k"}).bool_value().native_value() == true);

    // default-insert: [] on missing key inserts null_v
    auto &v = t[int_t{99}];
    CHECK(v.is_null().native_value());
}

// ── section 8: table_t<int_t> - typed table ─────────────────────────────────

void test_typed_int_table() {
    printf("\n=== table_t<int_t> ===\n");

    table_t<int_t> t;
    (void)t.append(int_t{10LL});
    (void)t.append(int_t{20LL});
    (void)t.append(int_t{30LL});

    CHECK(t.size() == 3);
    CHECK(t.at(int_t{0}).native_value() == 10LL);
    CHECK(t.at(int_t{2}).native_value() == 30LL);

    t.set(string_t{"x"}, int_t{99LL});
    CHECK(t.at(string_t{"x"}).native_value() == 99LL);

    auto mv = t.find(int_t{1});
    CHECK(was_found(mv).native_value());
    CHECK(mv.value().native_value() == 20LL);

    auto mv_miss = t.find(int_t{99});
    CHECK(is_nullopt(mv_miss).native_value());
}

// ── section 9: table_t<string_t> ────────────────────────────────────────────

void test_typed_string_table() {
    printf("\n=== table_t<string_t> ===\n");

    table_t<string_t> t;
    (void)t.append(string_t{"alpha"});
    (void)t.append(string_t{"beta"});
    t.set(string_t{"key"}, string_t{"gamma"});

    CHECK(t.size() == 3);
    CHECK(t.at(int_t{0}).native_value() == "alpha");
    CHECK(t.at(int_t{1}).native_value() == "beta");
    CHECK(t.at(string_t{"key"}).native_value() == "gamma");
}

// ── section 10: table_t<value_t> ────────────────────────────────────────

void test_typed_num_table() {
    printf("\n=== table_t<value_t> ===\n");

    table_t<value_t> t;
    (void)t.append(value_t{int_t{1LL}});
    (void)t.append(value_t{float_t{2.5}});
    (void)t.append(value_t{bool_t{true}});

    CHECK(t.size() == 3);
    CHECK(t.at(int_t{0}).kind() == value_t::kind_t::int_v);
    CHECK(t.at(int_t{1}).kind() == value_t::kind_t::float_v);
    CHECK(t.at(int_t{2}).kind() == value_t::kind_t::bool_v);
    CHECK(sizeof(value_t) == 24);
}

// ── section 11: table inside value_t (nested tables) ────────────────────────

void test_nested_table() {
    printf("\n=== nested table_t inside value_t ===\n");

    auto inner = std::make_unique<table_t<value_t>>();
    inner->set(string_t{"x"}, value_t{int_t{42LL}});

    table_t<value_t> outer;
    outer.set(string_t{"inner"}, value_t{std::move(inner)});

    CHECK(outer.has(string_t{"inner"}).native_value());
    auto *t = outer.at(string_t{"inner"}).table_if();
    CHECK(t != nullptr);
    CHECK(t->at(string_t{"x"}).int_value().native_value() == 42LL);
}

// ── section 12: shared_ptr<table_t> inside value_t ──────────────────────────

void test_shared_table_in_value() {
    printf("\n=== shared_ptr<table_t> in value_t ===\n");

    auto shared = std::make_shared<table_t<value_t>>();
    shared->set(string_t{"v"}, value_t{int_t{7LL}});

    value_t v1{shared};
    value_t v2 = v1.clone();   // clone shares the ptr

    CHECK(v1.kind() == value_t::kind_t::shared_table_v);
    CHECK(v2.kind() == value_t::kind_t::shared_table_v);
    CHECK(v1.table_if() == v2.table_if());   // same pointer
    CHECK(v1.table_if()->at(string_t{"v"}).int_value().native_value() == 7LL);
}

// ── section 13: table_new_ builder ──────────────────────────────────────────

void test_table_builder() {
    printf("\n=== table_new_() builder ===\n");

    auto t = table_new_(
        table_item_(int_t{1LL}),
        table_item_(string_t{"hello"}),
        table_kv_(string_t{"key"}, int_t{99LL}),
        table_kv_(2, bool_t{true})
    );

    CHECK(t.has(int_t{0}).native_value());
    CHECK(t.has(int_t{1}).native_value());
    CHECK(t.has(string_t{"key"}).native_value());
    CHECK(t.has(int_t{2}).native_value());
    CHECK(t.at(string_t{"key"}).int_value().native_value() == 99LL);
}

// ── section 14: copy semantics ───────────────────────────────────────────────

void test_copy() {
    printf("\n=== copy semantics ===\n");

    table_t<value_t> orig;
    (void)orig.append(value_t{int_t{1LL}});
    orig.set(string_t{"s"}, value_t{string_t{"hello"}});

    table_t<value_t> copy = orig;
    CHECK(copy.size() == 2);
    CHECK(copy.at(int_t{0}).int_value().native_value() == 1LL);
    CHECK(copy.at(string_t{"s"}).string_if()->native_value() == "hello");

    // mutation of copy doesn't affect original
    copy.set(string_t{"s"}, value_t{string_t{"changed"}});
    CHECK(orig.at(string_t{"s"}).string_if()->native_value() == "hello");
    CHECK(copy.at(string_t{"s"}).string_if()->native_value() == "changed");
}

// ── section 15: clear ────────────────────────────────────────────────────────

void test_clear() {
    printf("\n=== clear ===\n");

    table_t<value_t> t;
    (void)t.append(value_t{int_t{1LL}});
    (void)t.append(value_t{int_t{2LL}});
    t.set(string_t{"k"}, value_t{bool_t{true}});
    CHECK(t.size() == 3);

    t.clear();
    CHECK(t.empty().native_value());
    CHECK(t.size() == 0);
    CHECK(t.is_packed().native_value());

    // can reuse after clear
    (void)t.append(value_t{int_t{42LL}});
    CHECK(t.at(int_t{0}).int_value().native_value() == 42LL);
}

// ── section 16: table_find_ chaining ─────────────────────────────────────────

void test_table_find_chain() {
    printf("\n=== table_find_ chaining ===\n");

    auto inner = std::make_unique<table_t<value_t>>();
    inner->set(string_t{"leaf"}, value_t{int_t{123LL}});

    table_t<value_t> outer;
    outer.set(string_t{"child"}, value_t{std::move(inner)});

    auto mv1 = table_find_(outer, string_t{"child"});
    CHECK(was_found(mv1).native_value());

    auto mv2 = table_find_(mv1, string_t{"leaf"});
    CHECK(was_found(mv2).native_value());
    CHECK(mv2.value().int_value().native_value() == 123LL);

    auto mv3 = table_find_(mv1, string_t{"nope"});
    CHECK(is_nullopt(mv3).native_value());

    // chain from nullopt stops gracefully
    auto mv4 = table_find_(mv3, string_t{"anything"});
    CHECK(is_nullopt(mv4).native_value());
}

// ── section 17: was_found / is_nullopt generic over nullable<T> ──────────────

void test_generic_find_helpers() {
    printf("\n=== was_found / is_nullopt - generic over nullable<T> ===\n");

    // table_t<value_t> - was already tested, confirm still works
    table_t<value_t> tv;
    tv.set(string_t{"k"}, value_t{int_t{1LL}});
    CHECK(was_found(tv.find(string_t{"k"})).native_value());
    CHECK(is_nullopt(tv.find(string_t{"nope"})).native_value());

    // table_t<int_t>
    table_t<int_t> ti;
    ti.append(int_t{42LL});
    CHECK(was_found(ti.find(int_t{0})).native_value());
    CHECK(is_nullopt(ti.find(int_t{99})).native_value());
    CHECK(ti.find(int_t{0}).value().native_value() == 42LL);

    // table_t<float_t>
    table_t<float_t> tf;
    tf.append(float_t{1.5});
    CHECK(was_found(tf.find(int_t{0})).native_value());
    CHECK(is_nullopt(tf.find(string_t{"nope"})).native_value());

    // table_t<string_t>
    table_t<string_t> ts;
    ts.set(string_t{"x"}, string_t{"hello"});
    CHECK(was_found(ts.find(string_t{"x"})).native_value());
    CHECK(is_nullopt(ts.find(string_t{"y"})).native_value());
    CHECK(ts.find(string_t{"x"}).value().native_value() == "hello");

    // table_t<value_t>
    table_t<value_t> tn;
    tn.append(value_t{int_t{7LL}});
    CHECK(was_found(tn.find(int_t{0})).native_value());
    CHECK(is_nullopt(tn.find(int_t{1})).native_value());
    CHECK(tn.find(int_t{0}).value().int_value().native_value() == 7LL);
}



int main() {
    printf("Running table_t tests...\n");

    test_value_t_basics();
    test_value_t();
    test_packed_mode();
    test_string_keys();
    test_mixed_keys();
    test_remove();
    test_operator_bracket();
    test_typed_int_table();
    test_typed_string_table();
    test_typed_num_table();
    test_nested_table();
    test_shared_table_in_value();
    test_table_builder();
    test_copy();
    test_clear();
    test_table_find_chain();

    test_generic_find_helpers();

    printf("\n────────────────────────────────\n");
    printf("  PASSED: %d\n", g_pass);
    printf("  FAILED: %d\n", g_fail);
    printf("────────────────────────────────\n");

    return g_fail > 0 ? 1 : 0;
}
