file(MAKE_DIRECTORY "${WORK}")
file(REMOVE_RECURSE "${WORK}/generated")
file(READ "${PUBLICATION}" publication)
string(JSON publication REMOVE "${publication}" vectors)
# Request only one direction and one native type. Other signatures must not become
# available through standard native conversions merely because this one exists.
string(JSON publication SET "${publication}" operators
  "[{\"operation\":\"add\",\"operands\":[\"int32\",\"int32\"]},{\"operation\":\"add\",\"operands\":[\"int32\",{\"native\":\"int\"}]}]")
file(WRITE "${WORK}/publication.json" "${publication}")
execute_process(COMMAND "${GENERATOR}" "${METADATA}" "${WORK}/publication.json" "${WORK}/generated"
  RESULT_VARIABLE status ERROR_VARIABLE errors)
if(NOT status EQUAL 0)
  message(FATAL_ERROR "Subset generation failed: ${errors}")
endif()
separate_arguments(flags NATIVE_COMMAND "${CXX_FLAGS}")
file(WRITE "${WORK}/positive.cpp" "#include <scpp/short_names.hpp>\n#include <scpp/native_bridge.hpp>\n#include <type_traits>\nstatic_assert(std::is_same_v<decltype(int32{} + 1), int32>);\nint main() {}\n")
execute_process(COMMAND "${CXX}" ${flags} -std=c++23 "-I${WORK}/generated/public"
  -fsyntax-only "${WORK}/positive.cpp" RESULT_VARIABLE status ERROR_VARIABLE errors)
if(NOT status EQUAL 0)
  message(FATAL_ERROR "Requested native overload failed: ${errors}")
endif()
foreach(expression "1 + int32{}" "int32{} + 1L" "int32{} + 1u" "int32{} + 1ULL" "int32{} + 1.0" "int64{} + 1")
  file(WRITE "${WORK}/negative.cpp" "#include <scpp/short_names.hpp>\n#include <scpp/native_bridge.hpp>\nauto invalid = ${expression};\n")
  execute_process(COMMAND "${CXX}" ${flags} -std=c++23 "-I${WORK}/generated/public"
    -fsyntax-only "${WORK}/negative.cpp" RESULT_VARIABLE status ERROR_VARIABLE errors)
  if(status EQUAL 0 OR NOT errors MATCHES "deleted")
    message(FATAL_ERROR "Expected an intentionally deleted candidate for ${expression}: ${errors}")
  endif()
endforeach()
