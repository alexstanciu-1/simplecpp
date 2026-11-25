#include "__types/ProviderTraitDescriptorRow.hpp"
#include "__types/TypeTraitRow.hpp"
#include "__types/TypeTraitTable.hpp"
#include <cstdio>
using namespace scpp;
int main() {
 ProviderTraitDescriptorRow provider{};
 provider.integer_width_bits = int_t<std::uint16_t>(32);
 auto provider_copy = provider;
 provider_copy.integer_width_bits = int_t<std::uint16_t>(99);
 if (!static_cast<bool>(php::identical(provider.integer_width_bits,int_t<std::uint16_t>(32)))) return 5;
 std::puts("provider_definition=unchanged;provider_copy=independent;standalone_header=ok");

 TypeTraitRow row{};
 row.integer_width_bits = int_t<std::uint16_t>(32);
 TypeTraitRow copy = row;
 copy.integer_width_bits = int_t<std::uint16_t>(99);
 TypeTraitTable table{};
 table.traits.push_back(row);
 TypeTraitTable table_copy = table;
 table_copy.traits.push_back(copy);
 TypeTraitRow stored = table.traits[int_t<>(0)];
 stored.integer_width_bits = int_t<std::uint16_t>(17);
 TypeTraitRow retained = table.traits[int_t<>(0)];
 if (!static_cast<bool>(php::identical(row.integer_width_bits,int_t<std::uint16_t>(32)))) return 1;
 if (!static_cast<bool>(php::identical(copy.integer_width_bits,int_t<std::uint16_t>(99)))) return 2;
 if (!static_cast<bool>(php::identical(retained.integer_width_bits,int_t<std::uint16_t>(32)))) return 3;
 if (table.traits.size()!=1 || table_copy.traits.size()!=2) return 4;
 std::puts("definition_bytes=unchanged;row_copy=32:99;stored_value=32;table_copy=1:2;standalone_headers=ok");
}
