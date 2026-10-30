set(json_generated ${runtime_generated}-json-proof)
set(json_publication ${CMAKE_CURRENT_SOURCE_DIR}/publication.json)
set(json_outputs)
foreach(path public/scpp/integers.hpp public/scpp/values.hpp public/scpp/numeric.hpp
    public/scpp/native_bridge.hpp public/scpp/short_names.hpp public/scpp/string.hpp public/scpp/boxed.hpp public/scpp/carriers.hpp
    src/runtime.cpp src/boxed.cpp src/json.cpp public/scpp/json.hpp prepared.json inventory.json)
  list(APPEND json_outputs ${json_generated}/${path})
endforeach()
add_custom_command(OUTPUT ${json_outputs}
  COMMAND ${generator_launcher} ${runtime_metadata} ${json_publication} ${json_generated}
  DEPENDS scpp_api_generator ${generator_sources} ${native_facts} ${native_context} ${generator_launcher} ${runtime_metadata} ${json_publication}
  VERBATIM)
add_custom_target(json_publication DEPENDS ${json_outputs})
add_library(json_proof_runtime STATIC ${json_generated}/src/runtime.cpp ${json_generated}/src/boxed.cpp)
add_dependencies(json_proof_runtime json_publication)
target_include_directories(json_proof_runtime PUBLIC ${json_generated}/public
  PRIVATE ${PROJECT_SOURCE_DIR}/runtime/src/numeric ${PROJECT_SOURCE_DIR}/runtime/src/sequence
    ${PROJECT_SOURCE_DIR}/runtime/src/boxed ${PROJECT_SOURCE_DIR}/runtime/src/ownership)
target_link_libraries(json_proof_runtime PRIVATE scpp_boxed_storage)
target_compile_features(json_proof_runtime PUBLIC cxx_std_23)
scpp_add_json(json_proof_module json_proof_runtime ${json_generated})
add_executable(json_consumer_test consumer.cpp)
target_link_libraries(json_consumer_test PRIVATE json_proof_module)
add_test(NAME json_consumer COMMAND json_consumer_test)
add_test(NAME json_freshness COMMAND ${generator_launcher} ${runtime_metadata} ${json_publication} ${json_generated} --check)
add_test(NAME json_publication COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/publication.py
  --generator ${api_tooling} --source ${PROJECT_SOURCE_DIR} --facts ${native_facts}
  --context ${native_context} --cxx ${CMAKE_CXX_COMPILER})
