file(REMOVE_RECURSE "${WORK}")
file(MAKE_DIRECTORY "${WORK}")
file(READ "${PUBLICATION}" publication)
# All adapters off; this publication exercises core-only members, additional
# element families and explicit conversion requests in both directions.
string(JSON publication SET "${publication}" vectors specializations [=[[
  {"element":"int32","native_index":false,"native_value":"none","operations":["equal","not_equal"],"casts_from":["vector_int64","vector_string"]},
  {"element":"int64","native_index":false,"native_value":"none","operations":["equal","not_equal"],"casts_from":["vector_int32","vector_string"]},
  {"element":"string","native_index":false,"native_value":"none","operations":["equal","not_equal"],"casts_from":["vector_int64","vector_float64","vector_bool_t"]},
  {"element":"float64","native_index":false,"native_value":"none","operations":["equal","not_equal"],"casts_from":["vector_int64"]},
  {"element":"bool_t","native_index":false,"native_value":"none","operations":["equal","not_equal"],"casts_from":["vector_string"]},
  {"element":"null_t","native_index":false,"native_value":"none","operations":["equal","not_equal"],"casts_from":[]}
]]=])
file(WRITE "${WORK}/publication.json" "${publication}")
execute_process(COMMAND "${GENERATOR}" "${METADATA}" "${WORK}/publication.json" "${WORK}/generated"
  RESULT_VARIABLE status ERROR_VARIABLE errors)
if(NOT status EQUAL 0)
  message(FATAL_ERROR "Vector publication failed: ${errors}")
endif()
separate_arguments(flags NATIVE_COMMAND "${CXX_FLAGS}")
execute_process(COMMAND "${CXX}" ${flags} -std=c++23 "--ld-path=${MOLD_LINKER}"
  "-I${WORK}/generated/public" "-I${SOURCE}/runtime/src/numeric" "-I${SOURCE}/runtime/src/sequence"
  "${WORK}/generated/src/runtime.cpp" "${SOURCE}/tests/vector_cast_consumer.cpp" -o "${WORK}/consumer"
  RESULT_VARIABLE status ERROR_VARIABLE errors)
if(NOT status EQUAL 0)
  message(FATAL_ERROR "Vector cast publication compilation failed: ${errors}")
endif()
execute_process(COMMAND "${WORK}/consumer" RESULT_VARIABLE status ERROR_VARIABLE errors)
if(NOT status EQUAL 0)
  message(FATAL_ERROR "Vector cast behavior failed: ${errors}")
endif()
