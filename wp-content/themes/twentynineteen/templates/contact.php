<?php 

/**
 * Template Name: Contact 
 */
get_header();
?>

<form class="form-control" action="../customer-information.php" method="POST" id="cst-jsform"name="customer_information">
<div class="form-group"><label for="name">Name *</label><br>
<input id="name" class="form-control" name="name" type="text" placeholder="Full Name" /></div>
<div class="form-group"><label for="email">Email address *</label><br>
<input id="email" class="form-control" name="email" type="email" placeholder="Enter email" aria-describedby="emailHelp" />
<div class="form-group"><label for="phone">Phone*</label><br>
<input id="phone" class="form-control" type="text" name="phone" placeholder="Phone Number" /></div>
</div>

<div class="form-group"><label for="phone">Message</label><br>
<textarea name="message"></textarea>
<button class="btn btn-default" type="button" value="Submit" id="submit">Submit</button></div>
</form>
<span id="jsform-success" hidden="hidden"></span>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script type="text/javascript">
	jQuery(document).ready(function($) {

$('#submit').click(function(){
var name = $('#name').val();
var email = $('#email').val();
var phone = $('#phone').val();
if(name === ''){
	alert('Enter Name')
}else{
	if(email === ''){
		alert("Enter email")
	}else{
		var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
		  if(!regex.test(email)) {
		  	alert("Enter Correct Email")
		  }else{
		   if(phone === ''){
		   	alert("Enter Number")
		   }else{
		   	    var filter = /^((\+[1-9]{1,4}[ \-]*)|(\([0-9]{2,3}\)[ \-]*)|([0-9]{2,4})[ \-]*)*?[0-9]{3,4}?[ \-]*[0-9]{3,4}?$/;
			    if (filter.test(phone)) {
			        
			    }
			    else {
			        alert("Enter Correct number")
			    }

		   }	
		  }
	}
}

    return true;
});

});


</script>
<?php get_footer(); ?>