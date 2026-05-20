/**
 * Set Scrollbar First Load Position
 * 
 * created @Apr 1, 2019
 * author: wisnuwidi
 */
jQuery(document).ready(function() {
	var getBaseURL         = window.location.href.replace('/create', '');
	var activeURL          = [getBaseURL];
	var menuSelected       = 'a[href=\"' + activeURL.join('\"], a[href=\"') + '\"]';
	var parentMenuSelected = $(menuSelected).parent();
	parentMenuSelected.addClass('sidebar-active-url').parent().addClass('in').removeAttr('style');
	var firstBoxPosition   = parentMenuSelected.position();
	if (firstBoxPosition !== undefined) {
		$('.menu-inner').animate({scrollTop:firstBoxPosition.top},'slow').slimScroll({scrollBy:firstBoxPosition.top+'px'});
	}
});