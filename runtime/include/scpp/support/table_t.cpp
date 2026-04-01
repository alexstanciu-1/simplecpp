#include "scpp/table_t.hpp"
#include "scpp/value_t.hpp"

namespace scpp {

// Explicit instantiations one per whitelisted T_VALUE.
// Keeps template code compiled once, not per translation unit.
template class table_t<null_t>;
template class table_t<bool_t>;
template class table_t<int_t>;
template class table_t<float_t>;
template class table_t<string_t>;
template class table_t<value_t>;

// Table-of-table ownership variants.
template class table_t<std::shared_ptr<table_t<value_t>>>;
template class table_t<std::unique_ptr<table_t<value_t>>>;
template class table_t<std::weak_ptr<table_t<value_t>>>;

} // namespace scpp
