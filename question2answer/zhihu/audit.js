function ajaxDo(method, url, data, func) {
  var request = new XMLHttpRequest();
  request.open(method, url, true);
  request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
  request.onload = func;
  if (data) {
    request.send(data);
  } else {
    request.send();
  }
}

function show_undo() {
  var id = Cookies.get('undo');
  var undo_text = Cookies.get('undo_text');
  var old_state = Cookies.get('old_state');
  document.getElementById('undo_box_msg').textContent = "您刚刚更改了 "+undo_text;
  var undo_box = document.getElementById('undo_box');
  undo_box.style.display = '';
  setTimeout(function() {
    undo_box.style.display = 'none';
    Cookies.set('undo', "");
  }, 5000);
}
function undo() {
  var id = Cookies.get('undo');
  Cookies.set('undo', "");
  var undo_text = Cookies.get('undo_text');
  var old_state = Cookies.get('old_state');
  var data = [
    "change_to_state="+ encodeURIComponent(old_state),
    "id="+ encodeURIComponent(id),
  ];
  ajaxDo('POST', '?ajax', data.join("&"), function() {
    if (this.status >= 200 && this.status < 400) {
      // Success!
      var resp = this.responseText;
      alert(resp);
      location.href = "?state="+old_state;
    } else {
      // We reached our target server, but it returned an error
      alert("error");
    }
  });
}
function send_msg(username) {
  $('#msgM').modal();
  $('#userLink').text(username).attr('href', 'https://www.zhihu.com/people/'+username+'/activities');
  $('#msgM .modal-body textarea').text('loading...');
  $.get('?site_mail_to='+username, function(ret) {
    $('#msgM .modal-body textarea').text(ret);
    var i = ret.indexOf("，");
    var name = ret.substr(0, i);
    $('#userLink').text(name);
  });
}