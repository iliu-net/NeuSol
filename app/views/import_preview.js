
function do_submit($command) {
  //console.log("Welcome: " + $command);
  document.getElementById("command_msg").value = $command;
  var rowcount = parseInt(document.getElementById("rowcount").value);
  var rows = "\n" + rowcount + "\n";
  var qadds = "\n" + rowcount + "\n";

  for (i = 0; i < rowcount; i++) {
    //console.log("FETCH xid"+i);
    xid = document.getElementById("xid"+i).value;
    cat = document.getElementById("cat"+i).value;
    cgn = document.getElementById("cgn"+i).value;
    if (cgn != "") {
      cgn = parseInt(cgn);
      if (isNaN(cgn)) cgn = "";
    }

    ctl = document.getElementById("qadd" + i);
    if (ctl) {
      qadd_it = ctl.value;
    } else {
      qadd_it = 0;
    }
    //~ console.log("QADD"+i+": "+qadd_it);

    if (qadd_it == 1) {
      qdesc = document.getElementById("desc_re"+i).value;
      //~ console.log("QDEC: "+ qdesc);
      qadds += xid + "\t" + i + "\t" + cat + "\t" + cgn + "\t" + qdesc +"\n";
    } else {
      rows += xid + "\t" + i + "\t" + cat + "\t" + cgn + "\n";
    }
  }

  document.getElementById("override").value = rows;
  document.getElementById("qrules").value = qadds;

  document.getElementById("dlg").submit();

}

function qadd_link(id) {
  document.getElementById("qadd_cmd" + id).style.display = 'none';
  document.getElementById("qadd_form" + id).style.display = 'block';
  document.getElementById("qadd" + id).value = 1;
}

function qadd_cancel(id) {
  document.getElementById("qadd_cmd" + id).style.display = 'block';
  document.getElementById("qadd_form" + id).style.display = 'none';
  document.getElementById("qadd" + id).value = 0;
}
