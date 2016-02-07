// This function wont be invoved until the complete page is loaded.
function Loaded()
{
document.getElementById("loader").style.display = "none";
}

function checkAll(element) {
 	var theForm = element.form, z = 0;
 
	for(z=0; z<theForm.length;z++)
  		if(theForm[z].type == 'checkbox')
  			theForm[z].checked = 'true';
}

function uncheckAll(element) {
	
	var theForm = element.form, z = 0;

	for(z=0; z<theForm.length;z++)
	  	if(theForm[z].type == 'checkbox')
	  		theForm[z].checked = '';
}
