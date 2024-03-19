/*
 * Documentation:
 * https://www.bootstrap-year-calendar.com/
 */
$(document).ready(function() {
  var $lateral_menu_trigger = $('#cd-menu-trigger'),
  		$content_wrapper = $('.cd-main-content'),
  		$navigation = $('header');

  // Open-close lateral menu clicking on the menu icon
	$lateral_menu_trigger.on('click', function(event){
    event.preventDefault();

    $lateral_menu_trigger.toggleClass('is-clicked');
    $navigation.toggleClass('lateral-menu-is-open');
    $content_wrapper.toggleClass('lateral-menu-is-open').one('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend', function(){
      // Firefox transitions break when parent overflow is changed, so we need to wait for the end of the trasition to give the body an overflow hidden
	    $('body').toggleClass('overflow-hidden');
		});

    $('#cd-lateral-nav').toggleClass('lateral-menu-is-open');

		// Check if transitions are not supported - i.e. in IE9
		if($('html').hasClass('no-csstransitions')) {
			$('body').toggleClass('overflow-hidden');
		}
	});

	// Close lateral menu clicking outside the menu itself
	$content_wrapper.on('click', function(event){
		if( !$(event.target).is('#cd-menu-trigger, #cd-menu-trigger span') ) {
			$lateral_menu_trigger.removeClass('is-clicked');
			$navigation.removeClass('lateral-menu-is-open');

      $content_wrapper.removeClass('lateral-menu-is-open').one('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend', function(){
				$('body').removeClass('overflow-hidden');
			});

      $('#cd-lateral-nav').removeClass('lateral-menu-is-open');

      // Check if transitions are not supported
			if($('html').hasClass('no-csstransitions')) {
				$('body').removeClass('overflow-hidden');
			}
		}
	});

	// Open (or close) submenu items in the lateral menu. Close all the other open submenu items.
	$('.item-has-children').children('a').on('click', function(event){
		event.preventDefault();
		$(this).toggleClass('submenu-open').next('.sub-menu').slideToggle(200).end().parent('.item-has-children').siblings('.item-has-children').children('a').removeClass('submenu-open').next('.sub-menu').slideUp(200);
	});

  var currentYear = new Date().getFullYear();

  $('#calendar').calendar({
    language: 'de',
    style: 'background',
    displayWeekNumber: true,
    dataSource: [
          {
              startDate: new Date(currentYear, 1, 4),
              endDate: new Date(currentYear, 1, 15)
          },
          {
              startDate: new Date(currentYear, 3, 5),
              endDate: new Date(currentYear, 5, 15)
          }
    ]
  });
});
