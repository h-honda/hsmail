function hsmail_add(url){
    var form = document.getElementById("mform1");
    var courseid   = document.getElementById("courseid");
    url = url + "?id=" + courseid.value;
    document.location = url;
}