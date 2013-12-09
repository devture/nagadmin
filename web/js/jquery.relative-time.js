$.fn.relativeTime = function () {
	var MINUTE = 60;
	var HOUR = 60 * MINUTE;
	var DAY = 24 * HOUR;
	var WEEK = 7 * DAY;
	var YEAR = DAY * 365;
	var MONTH = YEAR / 12;

	/**
	 * Modified function code, based on: https://github.com/component/relative-date
	 */
	var relative = function (date, other) {
		var seconds = Math.round(Math.abs(other - date) / 1000);

		if (seconds <= 1) return 'one second';
		if (seconds < MINUTE) return Math.ceil(seconds) + ' seconds';

		if (seconds == MINUTE) return 'one minute';
		if (seconds < HOUR) return Math.ceil(seconds / MINUTE) + ' minutes';

		if (seconds == HOUR) return 'one hour';
		if (seconds < DAY) return Math.ceil(seconds / HOUR) + ' hours';

		if (seconds == DAY) return 'one day';
		if (seconds < WEEK) return Math.ceil(seconds / DAY) + ' days';

		if (seconds == WEEK) return 'one week';
		if (seconds < MONTH) return Math.ceil(seconds / WEEK) + ' weeks';

		if (seconds == MONTH) return 'one month';
		if (seconds < YEAR) return Math.ceil(seconds / MONTH) + ' months';

		if (seconds == YEAR) return 'one year';

		var years = Math.round(seconds / YEAR);
		return years + ' ' + (years == 1 ? 'year' : 'years');
	};

	var process = function () {
		//Intentionally using .attr('data-time'), instead of .data('time').
		//jQuery caches data attributes and future changes coming from outside jQuery are not detected.
		var date = new Date(parseInt($(this).attr('data-time'), 10));

		$(this).text(relative(date, new Date()));
		$(this).attr('title', date.toLocaleDateString() + ' ' + date.toLocaleTimeString());
	};

	$(this).each(process);

	return this;
};