using ::scpp::bool_t;
namespace detail { struct @access_name@; }
class @n@ final {
public:
@section_declaration@
private:
struct storage;
storage* storage_ = nullptr;
friend struct @access@;
};
@section_storage@
namespace detail { struct @access_name@ {
@section_access@
}; }
@section_definition@
