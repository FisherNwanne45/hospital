jQuery(document).ready(function($){

	var x = sessionStorage.getItem("newUser");
	if(x)
	{
		console.log("old user");
	}
	else{
		console.log("new user");
	}

	$('#preloader video').on('ended',function(){
		$("#preloader").removeClass('load');
	});


	$('.home-slider').slick({
		slidesToShow: 1,
		slidesToScroll: 1,
		dots: true,
		infinite: true,
		speed: 500,
		fade: false,
		cssEase: 'linear'
	});
	$('.ramp-down-slider').slick({
		slidesToShow: 1,
		slidesToScroll: 1,
		dots: false,
		arrow: true,
		nextArrow: '<div class="arrow-right"><i class="fa fa-chevron-right"></i></div>',
		prevArrow: '<div class="arrow-left"><i class="fa fa-chevron-left"></i></div>' 
	});
	$('.single-image-slider').slick({
		slidesToShow: 1,
		slidesToScroll: 1,
		dots: false,
		arrow: true,
		nextArrow: '<div class="arrow-right"><i class="fa fa-chevron-right"></i></div>',
		prevArrow: '<div class="arrow-left"><i class="fa fa-chevron-left"></i></div>' 
	});
	$('.testimonial-slider').slick({
		slidesToShow: 2,
		slidesToScroll: 1,
		dots: false,
		arrows: true,
		autoplay: false,
		autoplaySpeed: 5000,
		pauseOnHover: true,
		nextArrow: '<div class="arrow-right"><i class="fa fa-chevron-right"></i></div>',
		prevArrow: '<div class="arrow-left"><i class="fa fa-chevron-left"></i></div>',
		responsive: [
			{
				breakpoint: 992,
				settings: {
					slidesToShow: 1,
					slidesToScroll: 1,
					infinite: true,
					dots: true
				}
			}
		]

	});

	$('.service-slider').slick({
		dots: true,
		infinite: false,
		speed: 300,
		slidesToShow: 3,
		slidesToScroll: 1,
		responsive: [
			{
				breakpoint: 992,
				settings: {
					slidesToShow: 2,
					slidesToScroll: 1,
					infinite: true,
					dots: true
				}
			},
			{
				breakpoint: 768,
				settings: {
					slidesToShow: 1,
					slidesToScroll: 1
				}
			},
			{
				breakpoint: 480,
				settings: {
					slidesToShow: 1,
					slidesToScroll: 1
				}
			}
			// You can unslick at a given breakpoint now by adding:
			// settings: "unslick"
			// instead of a settings object
		]
	});


	$('.search-box input[type="text"]').addClass('search-field');

	$('.search-field').attr({
		placeholder: 'search'
	});

	$('.tnp-email').attr({
		placeholder: 'Your Email'
	});


	$('.choose-box > .vc_column-inner > .wpb_wrapper').addClass('border-box');


	$('[data-action="nav-toggle"]').on('click', function(e){
		e.preventDefault();
		let id = $(this).attr('data-id');
		$(id).toggleClass('resnav-collapsed');
	});

	$('[data-action="nav-close"]').on('click', function(e){
		e.preventDefault();
		let id = $(this).attr('data-id');
		$(id).removeClass('resnav-collapsed');
	});
	AOS.init();
});