function purge() {
  url = document.getElementById("base_url").value;
  keep = document.getElementById("form_keep").value;
  window.location.href= url + keep;
}


