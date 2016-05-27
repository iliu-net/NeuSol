
function do_submit($command) {
  //console.log("Welcome: " + $command);
  document.getElementById("command_msg").value = $command;
  var rowcount = parseInt(document.getElementById("rowcount").value);
  var rows = "\n" + rowcount + "\n";
  for (i = 0; i < rowcount; i++) {
    //console.log("FETCH xid"+i);
    xid = document.getElementById("xid"+i).value;
    cat = document.getElementById("cat"+i).value;
    cgn = document.getElementById("cgn"+i).value;
    if (cgn != "") {
      cgn = parseInt(cgn);
      if (isNaN(cgn)) cgn = "";
    }

    rows += xid + "\t" + i + "\t" + cat + "\t" + cgn + "\n";
  }

  document.getElementById("override").value = rows;
  document.getElementById("dlg").submit();

}

