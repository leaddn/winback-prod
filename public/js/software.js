// ######### Sort Table by alphabetical order ######### //
softTablesClick = document.getElementsByClassName("softTableClick");
for(let e of softTablesClick){
    e.addEventListener("click", function sortTable()
    {
    n=0;
    clickId = e.id;
    newId = clickId.replace("softTableClick_","");
    tableId = "softTable_"+newId;
    var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
    table = document.getElementById(tableId);
    //$(this).addClass('btn-dark');
    switching = true;
    // Set the sorting direction to ascending:
    dir = "asc";
    /* Make a loop that will continue until
    no switching has been done: */
    while (switching) {
    // Start by saying: no switching is done:
    switching = false;
    rows = table.rows;
    /* Loop through all table rows (except the
    first, which contains table headers): */
        for (i = 1; i < (rows.length - 1); i++) {
            // Start by saying there should be no switching:
            shouldSwitch = false;
            /* Get the two elements you want to compare,
            one from current row and one from the next: */
            x = rows[i].getElementsByTagName("a")[n];
            y = rows[i + 1].getElementsByTagName("a")[n];
            /* Check if the two rows should switch place,
            based on the direction, asc or desc: */
            if (dir == "asc") {
              if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                    // If so, mark as a switch and break the loop:
                    shouldSwitch = true;
                    break;
                }
            } else if (dir == "desc") {
                if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                    // If so, mark as a switch and break the loop:
                  console.log(x.innerHTML)
                    shouldSwitch = true;

                    break;
                }
            }
        }
        if (shouldSwitch) {
            /* If a switch has been marked, make the switch
            and mark that a switch has been done: */
          rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            // Each time a switch is done, increase this count by 1:
            switchcount ++;
        } else {
            /* If no switching has been done AND the direction is "asc",
            set the direction to "desc" and run the while loop again. */
            if (switchcount == 0 && dir == "asc") {
                dir = "desc";
                switching = true;
            }
        }
    }
        
    }
    );
}

function addUpdateComment(id, comment) {
  $.ajax({
    type:"POST",
    cache:false,
    url: `/addUpdateComment/${id}/${comment}`,
    //data: {id:id, comment:comment},
    success: function () {
      console.log("comment added");
      console.log(comment);
    }
  });
}

function addActualVersion(id, version) {
  $.ajax({
    type:"POST",
    cache:false,
    url: `/addActualVersion/${id}/${version}/`,
    //data: {id:id, comment:comment},
    success: function () {
      console.log("Actual version updated");
      console.log(version);
    }
  });
}

let updateCommentButtons = document.getElementsByClassName("comment_update_button");
let updateCommentInputs = document.getElementsByClassName("comment_update_input");

for (let element of updateCommentButtons) {
    element.onclick = function() {
      let id = $(element).data("id");
      let comment = element.previousElementSibling.value;
      if (comment == "") {
        comment = null
      }
      console.log(comment)
      addUpdateComment(id, comment);
      window.location.reload();
    };
}
  
let updateActualVersionButtons = document.getElementsByClassName("actualVersion_update_button");
let updateActualVersionInputs = document.getElementsByClassName("actualVersion_update_input");

for (let element of updateActualVersionButtons) {
    element.onclick = function() {
      let id = $(element).data("id");
      let version = element.previousElementSibling.value;
      if (version == "") {
        version = null
      }
      console.log(version)
      addActualVersion(id, version);
      window.location.reload();
    };
  }