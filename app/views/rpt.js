function custom_date() {
  url = document.getElementById("base_url").value;
  start = document.getElementById("form_rptStart").value;
  end = document.getElementById("form_rptEnd").value;
  window.location.href= url + start + "/" + end;
}
function changePage() {
  url = document.getElementById("base_url").value;
  period = document.getElementById("form_period").value;
  window.location.href= url + period;
}
