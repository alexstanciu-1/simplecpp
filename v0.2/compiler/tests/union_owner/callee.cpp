// Compiled with the ordinary generated callee unit in scope.
#include "slots.hpp"

namespace App {
int FREE_FIELD(NameOrField node) { return 10 + node->mark; }
int FREE_LITERAL(NameOrLiteral node) { return 20 + node->mark; }

struct ConcreteReader final : Reader {
    NameOrField RETURN_FIELD() override { return make_field(); }
    NameOrLiteral RETURN_LITERAL() override { return make_literal(); }
    int SLOT_FIELD(NameOrField node) override { return 30 + node->mark; }
    int SLOT_LITERAL(NameOrLiteral node) override { return 40 + node->mark; }
};

Reader& reader() { static ConcreteReader instance; return instance; }
NameOrField forward_field(NameOrField node) { return node; }
int dispatch_field(Reader& target, NameOrField node) {
    return target.SLOT_FIELD(static_cast<NameOrField&&>(node));
}
int dispatch_literal(Reader& target, NameOrLiteral node) {
    return target.SLOT_LITERAL(static_cast<NameOrLiteral&&>(node));
}
}
