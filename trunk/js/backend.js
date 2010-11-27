// teachPress javascript for the admin menu

// for jumpmenu
function teachpress_jumpMenu(targ,selObj,restore){
  eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
  if (restore) selObj.selectedIndex=0;
}

// for adding new tags
function teachpress_inserttag(tag) {
	if (document.getElementsByName("tags")[0].value == "") {
		document.getElementsByName("tags")[0].value = tag;
	}
	else {
		document.getElementsByName("tags")[0].value = document.getElementsByName("tags")[0].value+', '+tag;
		document.getElementsByName("tags")[0].value = document.getElementsByName("tags")[0].value;
	}	
}

// for changing the color of a label
function teachpress_change_label_color(id) {
	checkbox = "checkbox_" + id;
	label = "tag_label_" + id;
	if (document.getElementById(checkbox).checked == true) {
		document.getElementById(label).style.color = "#FF0000";
	}
	else {
		document.getElementById(label).style.color = "#333";
	}
}

// for show/hide buttons
function teachpress_showhide(where) {
	 var mode = "block";
	 if (where == "show_all_fields" || where == "show_recommend_fields") {
		 mode = "inline";
	 }
     if (document.getElementById(where).style.display != mode) {
     	document.getElementById(where).style.display = mode;
     }
     else {
     	document.getElementById(where).style.display = "none";
     }
}

// validate forms
function teachpress_validateForm() {
  if (document.getElementById){
    var i,p,q,nm,test,num,min,max,errors='',args=teachpress_validateForm.arguments;
    for (i=0; i<(args.length-2); i+=3) { test=args[i+2]; val=document.getElementById(args[i]);
      if (val) { nm=val.name; if ((val=val.value)!="") {
        if (test.indexOf('isEmail')!=-1) { p=val.indexOf('@');
          if (p<1 || p==(val.length-1)) errors+='* '+nm+' must contain an e-mail address.\n';
        } else if (test!='R') { num = parseFloat(val);
          if (isNaN(val)) errors+='* '+nm+' must contain a number.\n';
          if (test.indexOf('inRange') != -1) { p=test.indexOf(':');
            min=test.substring(8,p); max=test.substring(p+1);
            if (num<min || max<num) errors+='* '+nm+' must contain a number between '+min+' and '+max+'.\n';
      } } } else if (test.charAt(0) == 'R') errors += '* '+nm+' is required.\n'; }
    } if (errors) alert('Sorry, but you must relieve the following error(s):\n'+errors);
    document.teachpress_returnValue = (errors == '');
} }

// for show/hide buttons
function teachpress_publicationFields(mode) {
	if (mode == "std" || mode == "std2") {
		if (mode == "std2") {
			teachpress_showhide("show_all_fields");
			teachpress_showhide("show_recommend_fields");
		}
		var test = document.getElementsByName("type")[0].value
		// journal field
		if (test == "article") {
			document.getElementById("div_journal").style.display = "block";
		}
		else {
			document.getElementById("div_journal").style.display = "none";
		}
		// volume field
		if (test == "article" || test == "book" || test == "booklet" || test == "conference" || test == "inbook" || test =="incollection" || test == "inproceedings" || test == "proceedings") {
			document.getElementById("div_volume").style.display = "block";
		}
		else {
			document.getElementById("div_volume").style.display = "none";
		}
		// number field
		if (test == "article" || test == "book" || test == "conference" || test == "inbook" || test =="incollection" || test == "inproceedings" || test == "proceedings" || test == "techreport") {
			document.getElementById("div_number").style.display = "block";
		}
		else {
			document.getElementById("div_number").style.display = "none";
		}
		// pages field
		if (test == "article" || test == "conference" || test == "inbook" || test =="incollection" || test == "inproceedings") {
			document.getElementById("div_pages").style.display = "block";
		}
		else {
			document.getElementById("div_pages").style.display = "none";
		}
		// address field
		if (test == "book" || test == "booklet" || test == "conference" || test == "inbook" || test =="incollection" || test == "inproceedings" || test == "manual" || test == "masterthesis" || test == "phdthesis" || test == "proceedings" || test == "techreport") {
			document.getElementById("div_address").style.display = "block";
		}
		else {
			document.getElementById("div_address").style.display = "none";
		}
		// chapter field
		if (test == "inbook" || test == "incollection") {
			document.getElementById("div_chapter").style.display = "block";
		}
		else {
			document.getElementById("div_chapter").style.display = "none";
		}
		// institution field
		if (test == "techreport") {
			document.getElementById("div_institution").style.display = "block";
		}
		else {
			document.getElementById("div_institution").style.display = "none";
		}
		// school field
		if (test == "masterthesis" || test == "phdthesis") {
			document.getElementById("div_school").style.display = "block";
		}
		else {
			document.getElementById("div_school").style.display = "none";
		}
		// series field
		if (test == "book" || test == "conference" || test == "inbook" || test =="incollection" || test == "inproceedings" || test == "proceedings") {
			document.getElementById("div_series").style.display = "block";
		}
		else {
			document.getElementById("div_series").style.display = "none";
		}
		// howpublished field
		if (test == "booklet" || test == "misc") {
			document.getElementById("div_howpublished").style.display = "block";
		}
		else {
			document.getElementById("div_howpublished").style.display = "none";
		}
		// edition field
		if (test == "book" || test == "inbook" || test =="incollection" || test == "manual") {
			document.getElementById("div_edition").style.display = "block";
		}
		else {
			document.getElementById("div_edition").style.display = "none";
		}
		// organization field
		if (test == "conference" || test == "inproceedings" || test == "manual" || test == "proceedings") {
			document.getElementById("div_organization").style.display = "block";
		}
		else {
			document.getElementById("div_organization").style.display = "none";
		}
		// techtype field
		if (test == "inbook" || test == "incollection" || test == "masterthesis" || test == "phdthesis" || test == "techreport" ) {
			document.getElementById("div_techtype").style.display = "block";
		}
		else {
			document.getElementById("div_techtype").style.display = "none";
		}
		// booktitle field
		if (test == "conference" || test =="incollection" || test == "inproceedings") {
			document.getElementById("div_booktitle").style.display = "block";
		}
		else {
			document.getElementById("div_booktitle").style.display = "none";
		}
		// publisher field
		if (test == "book" || test == "conference" || test == "inbook" || test =="incollection" || test == "inproceedings" || test == "proceedings") {
			document.getElementById("div_publisher").style.display = "block";
		}
		else {
			document.getElementById("div_publisher").style.display = "none";
		}
		// key field
		document.getElementById("div_key").style.display = "none";
		// crossref field
		document.getElementById("div_crossref").style.display = "none";
	}
	else {
		teachpress_showhide("show_all_fields");
		teachpress_showhide("show_recommend_fields");
		document.getElementById("div_journal").style.display = "block";
		document.getElementById("div_volume").style.display = "block";
		document.getElementById("div_number").style.display = "block";
		document.getElementById("div_pages").style.display = "block";
		document.getElementById("div_address").style.display = "block";
		document.getElementById("div_chapter").style.display = "block";
		document.getElementById("div_institution").style.display = "block";
		document.getElementById("div_school").style.display = "block";
		document.getElementById("div_series").style.display = "block";
		document.getElementById("div_howpublished").style.display = "block";
		document.getElementById("div_edition").style.display = "block";
		document.getElementById("div_organization").style.display = "block";
		document.getElementById("div_techtype").style.display = "block";
		document.getElementById("div_booktitle").style.display = "block";
		document.getElementById("div_publisher").style.display = "block";
		document.getElementById("div_crossref").style.display = "block";
		document.getElementById("div_key").style.display = "block";
	}
}

// Make it possible to use the wordpress media uploader
jQuery(document).ready(function() {
	jQuery('#upload_image_button').click(function() {
	 formfield = jQuery('#image_url').attr('name');
	 tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
	 return false;
	});
	
	window.send_to_editor = function(html) {
	 imgurl = jQuery('img',html).attr('src');
	 jQuery('#image_url').val(imgurl);
	 tb_remove();
	}
});