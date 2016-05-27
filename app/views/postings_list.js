function changePage() {
    var new_page = document.getElementById("base_url").value +
	"/" + document.getElementById("form_account").value +
	"," + document.getElementById("form_month").value +
        "," + document.getElementById("form_year").value;

    //alert("KillRoy was Here: "+ new_page);
    window.location.href = new_page;
}

function edit_post(p_id, p_date, p_descr, p_catid, p_catgrp,p_amt) {
  console.log("EDIT: "+p_id);
  document.getElementById("form_postingId").value = p_id;
  document.getElementById("form_postingDate").value = p_date;
  document.getElementById("form_description").value = p_descr;
  document.getElementById("form_catgroup").value = p_catgrp;
  document.getElementById("form_categoryId").value = p_catid;
  document.getElementById("form_amount").value = p_amt;
  document.getElementById("form_command").value= "update";
  document.getElementById("form_submit").value= "Edit";
}

