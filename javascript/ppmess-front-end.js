// Front end - private messages 
/*----------------------------------------------------------*/
/*------------- Prvi poziv - ako postoji #anchor -----------*/
/*----------------------------------------------------------*/
$('html, body').hide()
$(document).ready(function()
{
	$("a[href^='#']").bind("click", pmess_jump_to); // Ako postoji #anchor poziva funkciju pmess_jump_to()
	
	// alert("location.hash: " + location.hash);
	
	// Izvrsava se kada postoji #anchor
	if(location.hash){
		// alert("location.hash = TRUE");
		
		setTimeout(function(){
			$('html, body').scrollTop(0).show()
			pmess_jump_to() // bez poziva ove funkcije Sroll ce biti izvrsen ali bez animacije
		}, 0);
	}else{
		// alert("location.hash = FALSE");
		
		$('html, body').show()
	}
});

var pmess_jump_to=function(e)
{		
	// alert("pmess_jump_to function: this.hash = " + this.hash); 
	
	if(this.hash == '#ppmessPmHref'){		
		// aktivni link menu
		$("#ppmessWpMenuId").removeAttr("class");
		$("#ppmessPmMenuId").attr( "class", "ppmess-a-navMenu mark" );
		$("#ppmessWpMenuId").attr( "class", "ppmess-a-navMenu unmark" );
		
		$("#respond").css({"display": "none", "opacity":"0.0"});		
		$("#ppmessSingleCommun").css( "display", "block" ).animate({opacity: '1.0'}, 1000);			
	}
	if(this.hash == '#ppmessWpHref'){
		// aktivni link menu
		$("#ppmessPmMenuId").removeAttr("class");
		$("#ppmessPmMenuId").attr( "class", "ppmess-a-navMenu unmark" ); 
		$("#ppmessWpMenuId").attr( "class", "ppmess-a-navMenu mark" );
		
		/*---------------- Prikaz forme za Javni ili Privatni komentar ----------------*/
		$( "#ppmessSingleCommun" ).css({'display':'none', 'opacity':'0.0'});
		$( "#respond" ).css({"display": "block", "opacity":"1.0"});
	}
	/*---------------------------------------------------------------------------*/
	/*---------------------------------------------------------------------------*/
	
	if(e){
		e.preventDefault();
		var target = $(this).attr("href");
	}else{
	    var target = location.hash;
	}

	$('html,body').delay(200).animate({
	   scrollTop: $(target).offset().top-350
	},1500,function(){
	   location.hash = target;
	});
}

/*---------- Change style on the page "all communications" ----------*/
function ppmess_change_style(x) {
	x.style.backgroundColor = "#d9d9d9";
}
function ppmess_return_style(x) {
	x.removeAttribute("style");
}

