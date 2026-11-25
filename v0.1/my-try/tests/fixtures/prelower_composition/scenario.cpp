#include "interface.hpp"

int main() {
    verify_traits();
    begin(); {
        Invoice invoice = make_invoice();
        require(invoice.total() == 30);
        invoice.amounts.append(Integer(30));
        require(invoice.total() == 60);
        mutate_amounts(invoice.amounts);
        require(invoice.total() == 91);
    } report("field_workload");
    require(live_values == 0);

    {
        Invoice invoice = make_invoice();
        const Invoice& readonly = invoice;
        begin();
        auto& field = invoice.amounts;
        const auto& const_field = readonly.amounts;
        require(&field == &const_field);
        auto& integer = field.at(0);
        const auto& const_integer = const_field.at(0);
        require(&integer == &const_integer);
        integer = Integer(12);
        require(const_integer.native_value() == 12);
        auto& payload = invoice.payloads.at(0);
        const auto& const_payload = readonly.payloads.at(0);
        require(&payload == &const_payload);
        payload.value = 17;
        require(const_payload.value == 17);
        report("field_references");

        begin(); {
            Invoice copy(invoice);
            copy.amounts.at(0) = Integer(99);
            copy.payloads.at(0).value = 88;
            require(invoice.amounts.at(0).native_value() == 12);
            require(invoice.payloads.at(0).value == 17);
            Invoice moved(static_cast<Invoice&&>(copy));
            require(moved.amounts.at(0).native_value() == 99);
            copy = invoice;
            moved = static_cast<Invoice&&>(copy);
            require(moved.payloads.at(0).value == 17);
        } report("record_copy_move");

        const int before = live_values;
        begin();
        copies_before_throw = 1; // Payload copy succeeds; marker copy fails.
        bool caught = false;
        try { Invoice copy(invoice); } catch (const CopyFailure&) { caught = true; }
        copies_before_throw = -1;
        require(caught && live_values == before);
        require(invoice.total() == 32 && invoice.payloads.at(0).value == 17);
        report("record_copy_failure");

        Vector_Invoice invoices;
        invoices.reserve(1);
        invoices.append(invoice);
        begin();
        invoices.append(invoices.at(0)); // Aliasing append and forced outer growth.
        require(invoices.size() == 2 && invoices.at(1).total() == 32);
        report("record_vector_growth");

        begin();
        auto& nested_field = invoices.at(0).amounts;
        mutate_amounts(nested_field);
        require(invoices.at(0).total() == 61);
        require(invoices.at(1).total() == 32 && invoice.total() == 32);
        const auto& const_invoices = invoices;
        require(&nested_field == &const_invoices.at(0).amounts);
        report("record_nested_references");

        Vector_Vector_Int nested;
        nested.append(invoice.amounts);
        begin();
        auto& row = nested.at(0);
        const auto& constant_nested = nested;
        require(&row == &constant_nested.at(0));
        mutate_amounts(row);
        require(constant_nested.at(0).at(0).native_value() == 41);
        require(invoice.amounts.at(0).native_value() == 12);
        report("concrete_nested_references");
        begin();
        nested.append(nested.at(0));
        require(nested.size() == 2 && nested.at(1).at(0).native_value() == 41);
        report("concrete_nested_growth");
    }
    require(live_values == 0);

    begin(); {
        auto shared = make_shared_invoice();
        shared.arrow()->amounts.append(Integer(5));
        auto retained = shared;
        Invoice* original = retained.arrow();
        shared = make_shared_invoice();
        require(retained.arrow() == original && retained.arrow()->total() == 5);
        require(shared.arrow() != original && shared.arrow()->total() == 0);
        auto unique = make_unique_invoice();
        unique.arrow()->amounts.append(Integer(9));
        Invoice* owned = unique.arrow();
        auto transferred = static_cast<Unique_Invoice&&>(unique);
        require(unique.arrow() == nullptr && transferred.arrow() == owned);
        require(transferred.arrow()->total() == 9);
    } report("record_ownership");
    require(live_values == 0);
}
