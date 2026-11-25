using ::scpp::bool_t;
namespace detail { struct @access_name@; }
class @n@ final {
public:
@section_output_declaration@
@section_declaration@
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
@section_output_definition@
