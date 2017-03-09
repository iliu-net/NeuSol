
function addcol() {
  document.getElementById("add_column_menu").style.display = "none";
  document.getElementById("add_date").style.display = "block";
}
function go_add_col() {
  if (document.getElementById("add_date").style.display == "block") {
    url = document.getElementById("base_url").value;
    date = document.getElementById("form_posDate").value;
    window.location.href= url + "add/" + date; 
  }
}
function do_submit() {
  document.getElementById("dlg").submit();
}
