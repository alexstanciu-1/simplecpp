#pragma once

// The identical source-defined record in direct and concrete builds.
// Field aliases in the direct build become real concrete classes in the other.
struct Invoice {
    Vector_Int amounts;
    Vector_Tracked payloads;
    Tracked marker;

    long total() const;
};

void mutate_amounts(Vector_Int& amounts);
Invoice make_invoice();
void verify_traits();
