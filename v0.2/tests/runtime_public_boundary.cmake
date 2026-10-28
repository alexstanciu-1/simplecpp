file(MAKE_DIRECTORY "${WORK}")
separate_arguments(flags NATIVE_COMMAND "${CXX_FLAGS}")
foreach(header integers numeric short_names native_bridge values string vectors)
  file(READ "${GENERATED}/public/scpp/${header}.hpp" text)
  if(text MATCHES "template[ \t\r\n]*<" OR text MATCHES "numeric_add|cast_int|std::")
    message(FATAL_ERROR "Unexpected public implementation exposure in ${header}")
  endif()
  file(WRITE "${WORK}/${header}.cpp" "#include <scpp/${header}.hpp>\nint main() {}\n")
  execute_process(COMMAND "${CXX}" ${flags} -std=c++23
    "-I${GENERATED}/public" -fsyntax-only "${WORK}/${header}.cpp"
    RESULT_VARIABLE status ERROR_VARIABLE errors)
  if(NOT status EQUAL 0)
    message(FATAL_ERROR "Independent header inclusion failed: ${errors}")
  endif()
  execute_process(COMMAND "${CXX}" ${flags} -std=c++23
    "-I${GENERATED}/public" -M "${WORK}/${header}.cpp"
    RESULT_VARIABLE status OUTPUT_VARIABLE dependencies ERROR_VARIABLE errors)
  if(NOT status EQUAL 0 OR dependencies MATCHES "scalar_semantics|sequence_semantics|tools/api_generator|runtime/include")
    message(FATAL_ERROR "Public dependency closure failure: ${errors} ${dependencies}")
  endif()
endforeach()
# Core headers alone do not admit native arithmetic or implicit construction.
file(WRITE "${WORK}/core.cpp" "#include <scpp/short_names.hpp>\n#include <type_traits>\ntemplate<class L, class R> concept adds = requires(L l, R r) { l + r; };\nstatic_assert(!adds<int64, int> && !adds<int, int64>);\nstatic_assert(!std::is_convertible_v<int, int64>);\nstatic_assert(!std::is_convertible_v<long long, int64>);\nstatic_assert(!std::is_convertible_v<double, int64>);\nstatic_assert(std::is_same_v<decltype(int32{} + int64{}), int64>);\nint main() { int64 x{12}; auto y = x + static_cast<int64>(1); return y.native_value() == 13 ? 0 : 1; }\n")
execute_process(COMMAND "${CXX}" ${flags} -std=c++23
  "-I${GENERATED}/public" -fsyntax-only "${WORK}/core.cpp"
  RESULT_VARIABLE status ERROR_VARIABLE errors)
if(NOT status EQUAL 0)
  message(FATAL_ERROR "Core/bridge boundary failed: ${errors}")
endif()
