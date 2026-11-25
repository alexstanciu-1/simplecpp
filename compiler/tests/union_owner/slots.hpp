#pragma once
#include "names.hpp"

namespace App {
using NameOrField = owner_Expr;
using NameOrLiteral = owner_Expr;

int FREE_FIELD(NameOrField);
int FREE_LITERAL(NameOrLiteral);

NameOrField make_field();
NameOrLiteral make_literal();

struct Reader {
    virtual NameOrField RETURN_FIELD() = 0;
    virtual NameOrLiteral RETURN_LITERAL() = 0;
    virtual int SLOT_FIELD(NameOrField) = 0;
    virtual int SLOT_LITERAL(NameOrLiteral) = 0;
};

Reader& reader();
NameOrField forward_field(NameOrField);
int dispatch_field(Reader&, NameOrField);
int dispatch_literal(Reader&, NameOrLiteral);
}
