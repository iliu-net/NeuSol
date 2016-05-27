//
// Help bind pikaday controls
//
var x = document.getElementsByClassName("mypikadayclass");
var i;
for (i=0;i<x.length;i++) {
  new Pikaday({
    field: x[i],
    format: 'YYYY-MM-DD'
  });
}
