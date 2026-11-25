namespace demo.schema;

class model {
	attached_storage: ?storage = null;
}

class storage {
	owner_model: ?model = null;
}

namespace demo.app;

use demo.schema.model;
use demo.schema.storage;

let m: model = new model();
let s: storage = new storage();
m.attached_storage = s;
s.owner_model = m;

print("ok\n");
