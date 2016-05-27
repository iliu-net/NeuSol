function showHide() {
  var input = document.getElementById("form_hint");
  if (input.getAttribute("type") == "password") {
    show();
  } else {
    hide();
  }
}

function show() {
    var p = document.getElementById('form_hint');
    p.setAttribute('type', 'text');
    document.getElementById('show_button').value="Hide";
}

function hide() {
    var p = document.getElementById('form_hint');
    p.setAttribute('type', 'password');
    document.getElementById('show_button').value="Show";
}

