using ::scpp::bool_t;
namespace detail { struct @access_name@; }
class @n@ final {
public:
@section_output_declaration@
@section_declaration@
    const iteration_output::value_type& scpp_escape_read_slot(@count@ index) const &;
    const iteration_output::value_type& scpp_escape_read_slot(@count@) const && = delete;
private:
@section_private_declaration@
struct storage;
storage* storage_ = nullptr;
friend struct @access@;
@destination_friend@
};
@section_storage@
namespace detail { struct @access_name@ {
@section_access@
}; }
@section_definition@
inline const @n@::iteration_output::value_type& @n@::scpp_escape_read_slot(@count@ index) const & {
    const auto& values = @access@::read(*this);
    return values[@buffer@::vector_index(index, values.size())];
}
@section_output_definition@
