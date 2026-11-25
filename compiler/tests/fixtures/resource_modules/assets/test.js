window.scpp.invoke("ok", 42).then(value => {
    if (value === 43) window.scpp.invoke("ok_ack", null);
});
window.scpp.invoke("bad", null).catch(error => {
    if (error.code === "expected" && error.message === "fail") window.scpp.invoke("bad_ack", null);
});
window.scpp.invoke(window.verificationWebsite, null);
