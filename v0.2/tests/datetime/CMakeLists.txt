set(datetime_generated ${runtime_generated}-datetime-proof)
set(datetime_publication ${CMAKE_CURRENT_SOURCE_DIR}/publication.json)
set(datetime_outputs)
foreach(path public/scpp/integers.hpp public/scpp/values.hpp public/scpp/numeric.hpp
    public/scpp/native_bridge.hpp public/scpp/short_names.hpp public/scpp/string.hpp
    public/scpp/carriers.hpp public/scpp/datetime.hpp src/runtime.cpp src/datetime.cpp prepared.json inventory.json)
  list(APPEND datetime_outputs ${datetime_generated}/${path})
endforeach()
add_custom_command(OUTPUT ${datetime_outputs}
  COMMAND ${generator_launcher} ${runtime_metadata} ${datetime_publication} ${datetime_generated}
  DEPENDS scpp_api_generator ${generator_sources} ${api_tooling} ${native_facts} ${native_context} ${generator_launcher} ${runtime_metadata} ${datetime_publication}
  VERBATIM)
add_custom_target(datetime_publication DEPENDS ${datetime_outputs})
add_library(datetime_proof_runtime STATIC ${datetime_generated}/src/runtime.cpp)
add_dependencies(datetime_proof_runtime datetime_publication)
target_include_directories(datetime_proof_runtime PUBLIC ${datetime_generated}/public
  PRIVATE ${PROJECT_SOURCE_DIR}/runtime/src/numeric ${PROJECT_SOURCE_DIR}/runtime/src/sequence)
target_compile_features(datetime_proof_runtime PUBLIC cxx_std_23)
scpp_add_datetime(datetime_proof_module datetime_proof_runtime ${datetime_generated})
add_dependencies(datetime_proof_module datetime_publication)
add_executable(datetime_consumer_test consumer.cpp)
target_link_libraries(datetime_consumer_test PRIVATE datetime_proof_module)
add_test(NAME datetime_consumer COMMAND datetime_consumer_test)
add_executable(datetime_semantics_test semantics.cpp ${PROJECT_SOURCE_DIR}/runtime/src/datetime/datetime.cpp)
target_include_directories(datetime_semantics_test PRIVATE ${PROJECT_SOURCE_DIR}/runtime/src/datetime)
target_compile_features(datetime_semantics_test PRIVATE cxx_std_23)
target_link_libraries(datetime_semantics_test PRIVATE Threads::Threads)
add_test(NAME datetime_semantics COMMAND datetime_semantics_test)
add_test(NAME datetime_freshness COMMAND ${generator_launcher} ${runtime_metadata} ${datetime_publication} ${datetime_generated} --check)
add_test(NAME datetime_publication COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/publication.py
  --generator ${generator_launcher} --metadata ${runtime_metadata} --publication ${datetime_publication}
  --cxx ${CMAKE_CXX_COMPILER} "--flags=${CMAKE_CXX_FLAGS}" --source ${PROJECT_SOURCE_DIR}
  --work ${CMAKE_CURRENT_BINARY_DIR}/publication-proof)
# A different local UTC offset must not alter the fixed UTC interface.
add_test(NAME datetime_timezone COMMAND ${CMAKE_COMMAND} -E env TZ=UTC+11 $<TARGET_FILE:datetime_consumer_test>)

add_executable(datetime_local_test local.cpp)
target_link_libraries(datetime_local_test PRIVATE datetime_proof_module)
add_test(NAME datetime_local_utc COMMAND ${CMAKE_COMMAND} -E env TZ=UTC0 $<TARGET_FILE:datetime_local_test> utc)
add_test(NAME datetime_local_dst COMMAND ${CMAKE_COMMAND} -E env TZ=EST5EDT,M3.2.0/2,M11.1.0/2 $<TARGET_FILE:datetime_local_test> dst)
add_test(NAME datetime_local_offset COMMAND ${CMAKE_COMMAND} -E env TZ=UTC+11 $<TARGET_FILE:datetime_local_test> offset)
