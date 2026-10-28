file(MAKE_DIRECTORY "${WORK}")
separate_arguments(flags NATIVE_COMMAND "${CXX_FLAGS}")
file(WRITE "${WORK}/consumer.cpp" "#include <scpp_compiler/token.hpp>\n#include <scpp_compiler/token_buffer.hpp>\n#include <scpp_compiler/token_sequence.hpp>\nint main() { scpp::compiler::token t{scpp::uint64{0}, scpp::uint64{1}, scpp::compiler::token_kind::identifier}; }\n")
execute_process(COMMAND "${CXX}" ${flags} -std=c++23 "-I${GENERATED}/public" "-I${MODEL}"
  -fsyntax-only "${WORK}/consumer.cpp" RESULT_VARIABLE status ERROR_VARIABLE errors)
if(NOT status EQUAL 0)
  message(FATAL_ERROR "Compiler model consumer failed: ${errors}")
endif()
execute_process(COMMAND "${CXX}" ${flags} -std=c++23 "-I${GENERATED}/public" "-I${MODEL}"
  -E -P "${WORK}/consumer.cpp" RESULT_VARIABLE status OUTPUT_VARIABLE preprocessed ERROR_VARIABLE errors)
if(NOT status EQUAL 0 OR preprocessed MATCHES "(^|[^A-Za-z0-9_])(template|typename)([^A-Za-z0-9_]|$)")
  message(FATAL_ERROR "Compiler include closure exposes templates: ${errors}")
endif()
file(WRITE "${WORK}/implicit.cpp" "#include <scpp_compiler/token.hpp>\nscpp::int64 value = 12;\n")
execute_process(COMMAND "${CXX}" ${flags} -std=c++23 "-I${GENERATED}/public" "-I${MODEL}"
  -fsyntax-only "${WORK}/implicit.cpp" RESULT_VARIABLE status ERROR_VARIABLE errors)
if(status EQUAL 0 OR NOT errors MATCHES "no viable conversion|no matching")
  message(FATAL_ERROR "Explicit construction fence failed: ${errors}")
endif()
execute_process(COMMAND "${CXX}" ${flags} -std=c++23 "-I${GENERATED}/public" "-I${MODEL}"
  -M "${WORK}/consumer.cpp" RESULT_VARIABLE status OUTPUT_VARIABLE dependencies ERROR_VARIABLE errors)
if(NOT status EQUAL 0 OR dependencies MATCHES "scalar_semantics|runtime/include|scalar_access|api_generator")
  message(FATAL_ERROR "Compiler model leaks implementation dependencies: ${errors} ${dependencies}")
endif()
