
function edit_post(p_id, p_date, p_descr, p_catid, p_catgrp,p_amt,p_acct) {
  console.log("EDIT: "+p_id);
  document.getElementById("form_acctId").value = p_acct;
  document.getElementById("form_postingId").value = p_id;
  document.getElementById("form_postingDate").value = p_date;
  document.getElementById("form_description").value = p_descr;
  document.getElementById("form_catgroup").value = p_catgrp;
  document.getElementById("form_categoryId").value = p_catid;
  document.getElementById("form_amount").value = p_amt;
  document.getElementById("form_command").value= "update";
  document.getElementById("form_submit").value= "Edit";
}

function reset_search() {
  document.getElementById("form_min_amt").value= "";
  document.getElementById("form_max_amt").value= "";
  document.getElementById("form_desc_search").value= "";
  document.getElementById("form_full_text").value= "";
}
