file(MAKE_DIRECTORY "${WORK}")
file(REMOVE_RECURSE "${WORK}/generated")
file(READ "${PUBLICATION}" publication)
string(JSON publication SET "${publication}" types "[\"int8\",\"int16\",\"int32\",\"int64\",\"uint8\",\"uint16\",\"uint32\",\"uint64\"]")
set(requests "[]")
set(index 0)
foreach(type int8 int16 int32 int64 uint8 uint16 uint32 uint64)
  foreach(operation add subtract multiply divide remainder bit_and bit_or bit_xor
      shift_left shift_right add_assign subtract_assign multiply_assign divide_assign
      remainder_assign bit_and_assign bit_or_assign bit_xor_assign shift_left_assign shift_right_assign)
    string(JSON requests SET "${requests}" ${index}
      "{\"operation\":\"${operation}\",\"operands\":[\"${type}\",\"${type}\"]}")
    math(EXPR index "${index} + 1")
  endforeach()
endforeach()
foreach(type int8 int16 int32 int64 uint8 uint16 uint32 uint64)
  foreach(operation positive negate bit_not prefix_increment postfix_increment prefix_decrement postfix_decrement)
    string(JSON requests SET "${requests}" ${index}
      "{\"operation\":\"${operation}\",\"operands\":[\"${type}\"]}")
    math(EXPR index "${index} + 1")
  endforeach()
endforeach()
# Shift count types have independent ranges and do not participate in promotion.
string(JSON requests SET "${requests}" ${index}
  "{\"operation\":\"shift_left\",\"operands\":[\"int8\",\"uint64\"]}")
math(EXPR index "${index} + 1")
string(JSON requests SET "${requests}" ${index}
  "{\"operation\":\"add_assign\",\"operands\":[\"int8\",\"int64\"]}")
math(EXPR index "${index} + 1")
foreach(left int8 int16 int32 int64 uint8 uint16 uint32 uint64)
  foreach(right int8 int16 int32 int64 uint8 uint16 uint32 uint64)
    foreach(operation equal not_equal less less_equal greater greater_equal)
      string(JSON requests SET "${requests}" ${index}
        "{\"operation\":\"${operation}\",\"operands\":[\"${left}\",\"${right}\"]}")
      math(EXPR index "${index} + 1")
    endforeach()
  endforeach()
endforeach()
foreach(operation add subtract multiply divide equal not_equal less less_equal greater greater_equal
    add_assign subtract_assign multiply_assign divide_assign)
  string(JSON requests SET "${requests}" ${index}
    "{\"operation\":\"${operation}\",\"operands\":[\"float64\",\"float64\"]}")
  math(EXPR index "${index} + 1")
  foreach(type int8 int16 int32 uint8 uint16 uint32)
    foreach(order 0 1)
      if(order EQUAL 0)
        set(pair "\"${type}\",\"float64\"")
      else()
        set(pair "\"float64\",\"${type}\"")
      endif()
      string(JSON requests SET "${requests}" ${index}
        "{\"operation\":\"${operation}\",\"operands\":[${pair}]}")
      math(EXPR index "${index} + 1")
    endforeach()
  endforeach()
endforeach()
foreach(operation positive negate prefix_increment postfix_increment prefix_decrement postfix_decrement)
  string(JSON requests SET "${requests}" ${index}
    "{\"operation\":\"${operation}\",\"operands\":[\"float64\"]}")
  math(EXPR index "${index} + 1")
endforeach()
string(JSON publication SET "${publication}" operators "${requests}")
file(WRITE "${WORK}/publication.json" "${publication}")
execute_process(COMMAND "${GENERATOR}" "${METADATA}" "${WORK}/publication.json" "${WORK}/generated"
  RESULT_VARIABLE status ERROR_VARIABLE errors)
if(NOT status EQUAL 0)
  message(FATAL_ERROR "Full integer publication failed: ${errors}")
endif()
separate_arguments(flags NATIVE_COMMAND "${CXX_FLAGS}")
execute_process(COMMAND "${CXX}" ${flags} -std=c++23 "--ld-path=${MOLD_LINKER}"
  "-I${WORK}/generated/public" "-I${SOURCE}/runtime/src/numeric"
  "${WORK}/generated/src/scalars.cpp" "${SOURCE}/tests/integer_all_types.cpp"
  -o "${WORK}/consumer" RESULT_VARIABLE status ERROR_VARIABLE errors)
if(NOT status EQUAL 0)
  message(FATAL_ERROR "Full integer compilation failed: ${errors}")
endif()
execute_process(COMMAND "${WORK}/consumer" RESULT_VARIABLE status ERROR_VARIABLE errors)
if(NOT status EQUAL 0)
  message(FATAL_ERROR "Full integer behavior failed: ${errors}")
endif()
