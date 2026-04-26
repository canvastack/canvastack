$('.limit-info').maxlength({
	threshold         : 100,
	alwaysShow        : false,
	validate          : true,
	placement         : 'bottom',
	warningClass      : "label label-warning",
	limitReachedClass : "label label-danger",
	separator         : ' out of ',
	preText           : 'You typed ',
	postText          : ' chars available.'
});