def build():
    return [
        {"text":'{"max_instances":1}',"value":1},
        {"text":'{"max_instances":4096}',"value":4096},
        {"text":'{"max_instances":4294967295}',"value":4294967295},
        {"text":'{"max_instances":17,"future":true}',"value":17},
    ] + [{"text": text,"value":0} for text in [
        '{}','null','[]','true','12','"text"',
        '{"max_instances":null}','{"max_instances":false}',
        '{"max_instances":"12"}','{"max_instances":[]}',
        '{"max_instances":{}}','{"max_instances":0}',
        '{"max_instances":-1}','{"max_instances":4294967296}',
        '{"max_instances":1.0}','{"max_instances":1e1}',
        '{"max_instances":9223372036854775808}',
        '{"max_instances":1',
    ]]
