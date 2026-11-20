// Compiled with the ordinary generated caller unit, including its real upcast adapters.
#include "slots.hpp"
#include <cassert>
#include <cstdlib>
#include <new>
#include <type_traits>

static unsigned allocations, disposals;
void* operator new(std::size_t size) {
    if (void* p = std::malloc(size ? size : 1)) { ++allocations; return p; }
    throw std::bad_alloc{};
}
void operator delete(void* p) noexcept { if (p) ++disposals; std::free(p); }
void operator delete(void* p, std::size_t) noexcept { ::operator delete(p); }

int main() {
    using namespace App;
    static_assert(std::is_same_v<NameOrField, NameOrLiteral>);
    static_assert(sizeof(NameOrField) == 2 * sizeof(void*));
    const unsigned initial = allocations;
    const unsigned freed = disposals;
    {
        auto name = owner_NameExpr::make();
        auto field = owner_FieldExpr::make();
        auto literal = owner_LiteralExpr::make();
        assert(allocations == initial + 3);
        name->mark = 1;
        field->mark = 2;
        literal->mark = 3;
        assert(FREE_FIELD(UP_NAME(name)) == 11 && FREE_FIELD(UP_FIELD(field)) == 12);
        assert(FREE_LITERAL(UP_NAME(name)) == 21 && FREE_LITERAL(UP_LITERAL(literal)) == 23);
        Reader& target = reader();
        assert(dispatch_field(target, UP_NAME(name)) == 31 && dispatch_literal(target, UP_NAME(name)) == 41);
        assert(dispatch_field(target, UP_FIELD(field)) == 32 && dispatch_literal(target, UP_LITERAL(literal)) == 43);
        auto* identity = name.ptr;
        auto* payload = name.view;
        weak_NameExpr witness(name);
        auto retained = forward_field(UP_NAME(name));
        assert(retained.ptr == identity && retained.view == payload && identity->count == 2);
        name = {};
        assert(identity->count == 1);
        retained->mark = 9;
        assert(witness.lock()->mark == 9);
        retained = {};
        assert(!witness.lock());
        assert(allocations == initial + 3);
    }
    assert(disposals == freed + 3);
}
