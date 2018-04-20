<?php

$date_time_format_php_pattern = array(
	//day of month
	'd', //Numeric, with leading zeros
	'j', //Numeric, without leading zeros

	//weekday
	'l', //full name of the day
	'D', //Three letter name

	//month
	'F', //Month name full
	'M', //Month name short
	'n', //numeric month no leading zeros
	'm', //numeric month leading zeros

	//year
	'Y', //full numeric year
	'y', //numeric year: 2 digit

	//time
	'a',
	'A',
	'g', //Hour, 12-hour, without leading zeros
	'h', //Hour, 12-hour, with leading zeros
	'G', //Hour, 24-hour, without leading zeros
	'H', //Hour, 24-hour, with leading zeros
	'i' //Minutes, with leading zeros
);

$ns->date_time_format_php_to_jquery = function( $date_time_format )
	use ( $ns, &$date_time_format_php_pattern ) {

	$pattern = $date_time_format_php_pattern;
	$replace = array(
		'dd', 'd',
		'DD', 'D',
		'MM', 'M', 'm', 'mm',
		'yy', 'y',
		'am', 'AM', '', '', '', '', ''
	);
	foreach ( $pattern as &$p ) {
		$p = '/'.$p.'/';
	}
	return preg_replace( $pattern, $replace, $date_time_format );
};

$ns->date_time_format_php_to_fullcalendar = function( $date_time_format )
	use ( $ns, &$date_time_format_php_pattern ) {

	$pattern = $date_time_format_php_pattern;
	$replace = array(
		'dd', 'd',
		'dddd', 'ddd',
		'MMMM', 'MMM', 'M', 'MM',
		'yyyy', 'yy',
		'tt', 'TT', 'h', 'hh', 'H', 'HH', 'mm'
	);
	foreach ( $pattern as &$p ) {
		$p = '/'.$p.'/';
	}
	return preg_replace( $pattern, $replace, $date_time_format );
};

$ns->wp_format_time = function( $datetime ) use ( $ns ) {
	$time_format = get_option( 'time_format' );
	return $datetime->format( $time_format );
};

$ns->wp_format_date = function( $datetime ) use ( $ns ) {
	$date_format = get_option( 'date_format' );
	$timestamp = $datetime->format( 'U' );
	return $ns->date_i18n( $date_format, $timestamp );
};

$ns->convert_to_datetime = function ( $timestamp ) use ( $ns ) {
	$date_format = get_option( 'date_format' );
	$time_format = get_option( 'time_format' );
	$datetime = $ns->get_wp_datetime( $timestamp );
	$datetime_separator = $ns->get_datetime_separator();
	return $ns->date_i18n( $date_format, $timestamp ) . $datetime_separator .
	$datetime->format( $time_format );
};

$ns->get_datetime_separator = function () use ( $ns ) {
	return ' ';
};

$ns->get_wp_timezone = function () use ( $ns ) {
	$timezone = get_option( 'timezone_string' );
	$offset = get_option( 'gmt_offset' );
	if ( $timezone ) {
		$timezone = new DateTimeZone( $timezone );
	} else if ( $offset ) {
		$offset = -round( $offset );
		if ( $offset > 0 ) {
			$offset = '+' . $offset;
		}
		$timezone = new DateTimeZone( 'Etc/GMT' . $offset );
	} else {
		$timezone = date_default_timezone_get();
		$timezone = new DateTimeZone( $timezone );
	}
	return $timezone;
};

$ns->date_i18n = function( $dateformatstring, $unixtimestamp ) use ( $ns ) {

	global $wp_locale;

	$datetime = $ns->get_wp_datetime( $unixtimestamp );
	if ( ( !empty( $wp_locale->month ) ) && ( !empty( $wp_locale->weekday ) ) ) {
		$datemonth = $datetime->format( 'm' );
		$datemonth = $wp_locale->get_month( $datemonth );
		$datemonth_abbrev = $wp_locale->get_month_abbrev( $datemonth );

		$dateweekday = $datetime->format( 'w' );
		$dateweekday = $wp_locale->get_weekday( $dateweekday );
		$dateweekday_abbrev = $wp_locale->get_weekday_abbrev( $dateweekday );

		$datemeridiem = $datetime->format( 'a' );
		$datemeridiem = $wp_locale->get_meridiem( $datemeridiem );
		$datemeridiem_capital = $datetime->format( 'A' );
		$datemeridiem_capital = $wp_locale->get_meridiem( $datemeridiem_capital );

		$dateformatstring = ' '.$dateformatstring;
		$dateformatstring = preg_replace( "/([^\\\])D/", "\\1" . backslashit( $dateweekday_abbrev ), $dateformatstring );
		$dateformatstring = preg_replace( "/([^\\\])F/", "\\1" . backslashit( $datemonth ), $dateformatstring );
		$dateformatstring = preg_replace( "/([^\\\])l/", "\\1" . backslashit( $dateweekday ), $dateformatstring );
		$dateformatstring = preg_replace( "/([^\\\])M/", "\\1" . backslashit( $datemonth_abbrev ), $dateformatstring );
		$dateformatstring = preg_replace( "/([^\\\])a/", "\\1" . backslashit( $datemeridiem ), $dateformatstring );
		$dateformatstring = preg_replace( "/([^\\\])A/", "\\1" . backslashit( $datemeridiem_capital ), $dateformatstring );

		$dateformatstring = substr( $dateformatstring, 1, strlen( $dateformatstring ) -1 );
	}
	$timezone_formats = array( 'P', 'I', 'O', 'T', 'Z', 'e' );
	$timezone_formats_re = implode( '|', $timezone_formats );
	if ( preg_match( "/$timezone_formats_re/", $dateformatstring ) ) {
		$timezone_object = $ns->get_wp_timezone();
		$date_object = date_create( null, $timezone_object );
		foreach ( $timezone_formats as $timezone_format ) {
			if ( false !== strpos( $dateformatstring, $timezone_format ) ) {
				$formatted = date_format( $date_object, $timezone_format );
				$dateformatstring = ' '.$dateformatstring;
				$dateformatstring = preg_replace( "/([^\\\])$timezone_format/", "\\1" . backslashit( $formatted ), $dateformatstring );
				$dateformatstring = substr( $dateformatstring, 1, strlen( $dateformatstring ) -1 );
			}
		}
	}
	return $datetime->format( $dateformatstring );
};

$ns->to_mysql_date = function ( $arg ) {
	$date = $arg['date'];
	$date = explode( '/', $date );
	$date = $date[2] . '-' . $date[0] . '-' . $date[1];
	$time = $arg['time'];
	$hours = floor( $time / 60 );
	$minutes = $time % 60;
	$date .= ' ' . $hours . ':' . $minutes . ':00';
	return $date;
};

$ns->get_wp_datetime = function ( $arg ) use ( $ns ) {
	$timezone = $ns->get_wp_timezone();
	try {
		if ( is_array( $arg ) ) {
			$datetime = $ns->to_mysql_date( $arg );
			$datetime = new DateTime( $datetime, $timezone );
			return $datetime;
		}
		if ( (string) (int) $arg == $arg && (int) $arg > 0 ) {
			$datetime = new DateTime( '@' . $arg );
			$datetime->setTimezone( $timezone );
			return $datetime;
		}
		$datetime = new DateTime( $arg, $timezone );
		return $datetime;
	} catch( Exception $e ) {
		return new DateTime( '@408240000' );
	}
};

$ns->convert_mins_to_time_option = function( $mins ) use ( $ns ) {
	$hour = $mins / 60;
	$min = $mins % 60;
	$date_sample = '2013-01-01 %02d:%02d:00';
	$timezone = $ns->get_wp_timezone();
	$datetime = new DateTime( sprintf( $date_sample, $hour, $min ), $timezone );
	$option_text = $datetime->format( get_option( 'time_format' ) );
	return $option_text;
};

$ns->get_time_options = function( $interval = 15 ) use ( $ns ) {
	$options = array();
	$value = 0;
	$format1 = '%d:%02d AM';
	$format2 = '%d:%02d PM';
	$date_sample = '2013-01-01 %02d:%02d:00';
	for ( $i = 0; $i < 24; $i++ ) {
		if ( $i === 0 ) {
			$hour = 12;
			$format = $format1;
		} else if ( $i === 12 ) {
			$hour = 12;
			$format = $format2;
		} else if ( $i < 12 ) {
			$hour = $i;
			$format = $format1;
		} else if ( $i > 12 ) {
			$hour = $i - 12;
			$format = $format2;
		}
		for ( $min = 0; $min < 60; $min += $interval ) {
			$timezone = $ns->get_wp_timezone();
			$datetime = new DateTime( sprintf( $date_sample, $i, $min ), $timezone );
			$option_text = $datetime->format( get_option( 'time_format' ) );
			$options[$value] = $option_text;
			$value += $interval;
		}
	}
	return $options;
};

$ns->get_gmt_offset = function() use ( $ns ) {
	return -round( $ns->get_wp_datetime( time() )->getOffset() / 60 );
};

$ns->get_calendar_views = function() use ( $ns ) {
	return array(
		'month' => __( 'Month', 'birchschedule' ),
		'agendaWeek' => __( 'Week', 'birchschedule' ),
		'agendaDay' => __( 'Day', 'birchschedule' )
	);
};

$ns->get_client_title_options = function() use ( $ns ) {
	return array( 'Mr' => __( 'Mr', 'birchschedule' ),
				  'Mrs' => __( 'Mrs', 'birchschedule' ),
				  'Miss' => __( 'Miss', 'birchschedule' ),
				  'Ms' => __( 'Ms', 'birchschedule' ),
				  'Dr' => __( 'Dr', 'birchschedule' ) );
};

$ns->get_weekdays_short = function() use ( $ns ) {
	return array(
		__( 'Sun', 'birchschedule' ),
		__( 'Mon', 'birchschedule' ),
		__( 'Tue', 'birchschedule' ),
		__( 'Wed', 'birchschedule' ),
		__( 'Thu', 'birchschedule' ),
		__( 'Fri', 'birchschedule' ),
		__( 'Sat', 'birchschedule' )
	);
};

$ns->get_day_minutes = function( $datetime ) use( $ns ) {
	$time = $datetime->format( 'H' ) * 60 + $datetime->format( 'i' );
	return $time;
};

$ns->get_fullcalendar_i18n_params = function () use ( $ns ) {
	return array(
		'firstDay' => $ns->get_first_day_of_week(),
		'monthNames'=> array(
			__( 'January' ),
			__( 'February' ),
			__( 'March' ),
			__( 'April' ),
			__( 'May' ),
			__( 'June' ),
			__( 'July' ),
			__( 'August' ),
			__( 'September' ),
			__( 'October' ),
			__( 'November' ),
			__( 'December' )
		),
		'monthNamesShort'=> array(
			_x( 'Jan', 'January abbreviation' ),
			_x( 'Feb', 'February abbreviation' ),
			_x( 'Mar', 'March abbreviation' ),
			_x( 'Apr', 'April abbreviation' ),
			_x( 'May', 'May abbreviation' ),
			_x( 'Jun', 'June abbreviation' ),
			_x( 'Jul', 'July abbreviation' ),
			_x( 'Aug', 'August abbreviation' ),
			_x( 'Sep', 'September abbreviation' ),
			_x( 'Oct', 'October abbreviation' ),
			_x( 'Nov', 'November abbreviation' ),
			_x( 'Dec', 'December abbreviation' )
		),
		'dayNames'=> array(
			__( 'Sun', 'birchschedule' ),
			__( 'Mon', 'birchschedule' ),
			__( 'Tue', 'birchschedule' ),
			__( 'Wed', 'birchschedule' ),
			__( 'Thu', 'birchschedule' ),
			__( 'Fri', 'birchschedule' ),
			__( 'Sat', 'birchschedule' )
		),
		'dayNamesShort'=> array(
			__( 'Sun', 'birchschedule' ),
			__( 'Mon', 'birchschedule' ),
			__( 'Tue', 'birchschedule' ),
			__( 'Wed', 'birchschedule' ),
			__( 'Thu', 'birchschedule' ),
			__( 'Fri', 'birchschedule' ),
			__( 'Sat', 'birchschedule' )
		),
		'buttonText' => array(
			'today' => __( 'today', 'birchschedule' ),
			'month' => __( 'month', 'birchschedule' ),
			'week' => __( 'week', 'birchschedule' ),
			'day' => __( 'day', 'birchschedule' )
		),
		'columnFormat' => array(
			'month' => 'ddd',    // Mon
			'week' => 'ddd M/d', // Mon 9/7
			'day' => 'dddd M/d'  // Monday 9/7
		),
		'titleFormat' => array(
			'month' => 'MMMM yyyy',                             // September 2009
			'week' => "MMM d[ yyyy]{ '&#8212;'[ MMM] d yyyy}", // Sep 7 - 13 2009
			'day' => 'dddd, MMM d, yyyy'                  // Tuesday, Sep 8, 2009
		),
		'timeFormat' => $ns->date_time_format_php_to_fullcalendar( get_option( 'time_format', 'g:i a' ) ),
		'axisFormat' => $ns->date_time_format_php_to_fullcalendar( get_option( 'time_format', 'g:i a' ) )
	);
};

$ns->get_datepicker_i18n_params = function () use ( $ns ) {
	return array(
		'firstDay' => $ns->get_first_day_of_week(),
		'monthNames'=> array(
			__( 'January' ),
			__( 'February' ),
			__( 'March' ),
			__( 'April' ),
			__( 'May' ),
			__( 'June' ),
			__( 'July' ),
			__( 'August' ),
			__( 'September' ),
			__( 'October' ),
			__( 'November' ),
			__( 'December' )
		),
		'monthNamesShort'=> array(
			_x( 'Jan', 'January abbreviation' ),
			_x( 'Feb', 'February abbreviation' ),
			_x( 'Mar', 'March abbreviation' ),
			_x( 'Apr', 'April abbreviation' ),
			_x( 'May', 'May abbreviation' ),
			_x( 'Jun', 'June abbreviation' ),
			_x( 'Jul', 'July abbreviation' ),
			_x( 'Aug', 'August abbreviation' ),
			_x( 'Sep', 'September abbreviation' ),
			_x( 'Oct', 'October abbreviation' ),
			_x( 'Nov', 'November abbreviation' ),
			_x( 'Dec', 'December abbreviation' )
		),
		'dayNames'=> array(
			__( 'Sunday', 'birchschedule' ),
			__( 'Monday', 'birchschedule' ),
			__( 'Tuesday', 'birchschedule' ),
			__( 'Wednesday', 'birchschedule' ),
			__( 'Thursday', 'birchschedule' ),
			__( 'Friday', 'birchschedule' ),
			__( 'Saturday', 'birchschedule' )
		),
		'dayNamesShort'=> array(
			__( 'Sun', 'birchschedule' ),
			__( 'Mon', 'birchschedule' ),
			__( 'Tue', 'birchschedule' ),
			__( 'Wed', 'birchschedule' ),
			__( 'Thu', 'birchschedule' ),
			__( 'Fri', 'birchschedule' ),
			__( 'Sat', 'birchschedule' )
		),
		'dayNamesMin'=> array(
			__( 'Su', 'birchschedule' ),
			__( 'Mo', 'birchschedule' ),
			__( 'Tu', 'birchschedule' ),
			__( 'We', 'birchschedule' ),
			__( 'Th', 'birchschedule' ),
			__( 'Fr', 'birchschedule' ),
			__( 'Sa', 'birchschedule' )
		),
		'dateFormat' => $ns->date_time_format_php_to_jquery( get_option( 'date_format' ) )
	);
};

$ns->get_countries = function() use ($ns) {
	return array(
		"AF" => __("Afghanistan", 'birchschedule'),
		"AL" => __("Albania", 'birchschedule'),
		"DZ" => __("Algeria", 'birchschedule'),
		"AS" => __("American Samoa", 'birchschedule'),
		"AD" => __("Andorra", 'birchschedule'),
		"AO" => __("Angola", 'birchschedule'),
		"AI" => __("Anguilla", 'birchschedule'),
		"AQ" => __("Antarctica", 'birchschedule'),
		"AG" => __("Antigua And Barbuda", 'birchschedule'),
		"AR" => __("Argentina", 'birchschedule'),
		"AM" => __("Armenia", 'birchschedule'),
		"AW" => __("Aruba", 'birchschedule'),
		"AU" => __("Australia", 'birchschedule'),
		"AT" => __("Austria", 'birchschedule'),
		"AZ" => __("Azerbaijan", 'birchschedule'),
		"BS" => __("Bahamas", 'birchschedule'),
		"BH" => __("Bahrain", 'birchschedule'),
		"BD" => __("Bangladesh", 'birchschedule'),
		"BB" => __("Barbados", 'birchschedule'),
		"BY" => __("Belarus", 'birchschedule'),
		"BE" => __("Belgium", 'birchschedule'),
		"BZ" => __("Belize", 'birchschedule'),
		"BJ" => __("Benin", 'birchschedule'),
		"BM" => __("Bermuda", 'birchschedule'),
		"BT" => __("Bhutan", 'birchschedule'),
		"BO" => __("Bolivia", 'birchschedule'),
		"BA" => __("Bosnia And Herzegowina", 'birchschedule'),
		"BW" => __("Botswana", 'birchschedule'),
		"BV" => __("Bouvet Island", 'birchschedule'),
		"BR" => __("Brazil", 'birchschedule'),
		"IO" => __("British Indian Ocean Territory", 'birchschedule'),
		"BN" => __("Brunei Darussalam", 'birchschedule'),
		"BG" => __("Bulgaria", 'birchschedule'),
		"BF" => __("Burkina Faso", 'birchschedule'),
		"BI" => __("Burundi", 'birchschedule'),
		"KH" => __("Cambodia", 'birchschedule'),
		"CM" => __("Cameroon", 'birchschedule'),
		"CA" => __("Canada", 'birchschedule'),
		"CV" => __("Cape Verde", 'birchschedule'),
		"KY" => __("Cayman Islands", 'birchschedule'),
		"CF" => __("Central African Republic", 'birchschedule'),
		"TD" => __("Chad", 'birchschedule'),
		"CL" => __("Chile", 'birchschedule'),
		"CN" => __("China", 'birchschedule'),
		"CX" => __("Christmas Island", 'birchschedule'),
		"CC" => __("Cocos (Keeling) Islands", 'birchschedule'),
		"CO" => __("Colombia", 'birchschedule'),
		"KM" => __("Comoros", 'birchschedule'),
		"CG" => __("Congo", 'birchschedule'),
		"CD" => __("Congo, The Democratic Republic Of The", 'birchschedule'),
		"CK" => __("Cook Islands", 'birchschedule'),
		"CR" => __("Costa Rica", 'birchschedule'),
		"CI" => __("Cote D'Ivoire", 'birchschedule'),
		"HR" => __("Croatia (Local Name: Hrvatska)", 'birchschedule'),
		"CU" => __("Cuba", 'birchschedule'),
		"CY" => __("Cyprus", 'birchschedule'),
		"CZ" => __("Czech Republic", 'birchschedule'),
		"DK" => __("Denmark", 'birchschedule'),
		"DJ" => __("Djibouti", 'birchschedule'),
		"DM" => __("Dominica", 'birchschedule'),
		"DO" => __("Dominican Republic", 'birchschedule'),
		"TP" => __("East Timor", 'birchschedule'),
		"EC" => __("Ecuador", 'birchschedule'),
		"EG" => __("Egypt", 'birchschedule'),
		"SV" => __("El Salvador", 'birchschedule'),
		"GQ" => __("Equatorial Guinea", 'birchschedule'),
		"ER" => __("Eritrea", 'birchschedule'),
		"EE" => __("Estonia", 'birchschedule'),
		"ET" => __("Ethiopia", 'birchschedule'),
		"FK" => __("Falkland Islands (Malvinas)", 'birchschedule'),
		"FO" => __("Faroe Islands", 'birchschedule'),
		"FJ" => __("Fiji", 'birchschedule'),
		"FI" => __("Finland", 'birchschedule'),
		"FR" => __("France", 'birchschedule'),
		"FX" => __("France, Metropolitan", 'birchschedule'),
		"GF" => __("French Guiana", 'birchschedule'),
		"PF" => __("French Polynesia", 'birchschedule'),
		"TF" => __("French Southern Territories", 'birchschedule'),
		"GA" => __("Gabon", 'birchschedule'),
		"GM" => __("Gambia", 'birchschedule'),
		"GE" => __("Georgia", 'birchschedule'),
		"DE" => __("Germany", 'birchschedule'),
		"GH" => __("Ghana", 'birchschedule'),
		"GI" => __("Gibraltar", 'birchschedule'),
		"GR" => __("Greece", 'birchschedule'),
		"GL" => __("Greenland", 'birchschedule'),
		"GD" => __("Grenada", 'birchschedule'),
		"GP" => __("Guadeloupe", 'birchschedule'),
		"GU" => __("Guam", 'birchschedule'),
		"GT" => __("Guatemala", 'birchschedule'),
		"GN" => __("Guinea", 'birchschedule'),
		"GW" => __("Guinea-Bissau", 'birchschedule'),
		"GY" => __("Guyana", 'birchschedule'),
		"HT" => __("Haiti", 'birchschedule'),
		"HM" => __("Heard And Mc Donald Islands", 'birchschedule'),
		"VA" => __("Holy See (Vatican City State)", 'birchschedule'),
		"HN" => __("Honduras", 'birchschedule'),
		"HK" => __("Hong Kong", 'birchschedule'),
		"HU" => __("Hungary", 'birchschedule'),
		"IS" => __("Iceland", 'birchschedule'),
		"IN" => __("India", 'birchschedule'),
		"ID" => __("Indonesia", 'birchschedule'),
		"IR" => __("Iran (Islamic Republic Of)", 'birchschedule'),
		"IQ" => __("Iraq", 'birchschedule'),
		"IE" => __("Ireland", 'birchschedule'),
		"IL" => __("Israel", 'birchschedule'),
		"IT" => __("Italy", 'birchschedule'),
		"JM" => __("Jamaica", 'birchschedule'),
		"JP" => __("Japan", 'birchschedule'),
		"JO" => __("Jordan", 'birchschedule'),
		"KZ" => __("Kazakhstan", 'birchschedule'),
		"KE" => __("Kenya", 'birchschedule'),
		"KI" => __("Kiribati", 'birchschedule'),
		"KP" => __("Korea, Democratic People's Republic Of", 'birchschedule'),
		"KR" => __("Korea, Republic Of", 'birchschedule'),
		"KW" => __("Kuwait", 'birchschedule'),
		"KG" => __("Kyrgyzstan", 'birchschedule'),
		"LA" => __("Lao People's Democratic Republic", 'birchschedule'),
		"LV" => __("Latvia", 'birchschedule'),
		"LB" => __("Lebanon", 'birchschedule'),
		"LS" => __("Lesotho", 'birchschedule'),
		"LR" => __("Liberia", 'birchschedule'),
		"LY" => __("Libyan Arab Jamahiriya", 'birchschedule'),
		"LI" => __("Liechtenstein", 'birchschedule'),
		"LT" => __("Lithuania", 'birchschedule'),
		"LU" => __("Luxembourg", 'birchschedule'),
		"MO" => __("Macau", 'birchschedule'),
		"MK" => __("Macedonia, Former Yugoslav Republic Of", 'birchschedule'),
		"MG" => __("Madagascar", 'birchschedule'),
		"MW" => __("Malawi", 'birchschedule'),
		"MY" => __("Malaysia", 'birchschedule'),
		"MV" => __("Maldives", 'birchschedule'),
		"ML" => __("Mali", 'birchschedule'),
		"MT" => __("Malta", 'birchschedule'),
		"MH" => __("Marshall Islands", 'birchschedule'),
		"MQ" => __("Martinique", 'birchschedule'),
		"MR" => __("Mauritania", 'birchschedule'),
		"MU" => __("Mauritius", 'birchschedule'),
		"YT" => __("Mayotte", 'birchschedule'),
		"MX" => __("Mexico", 'birchschedule'),
		"FM" => __("Micronesia, Federated States Of", 'birchschedule'),
		"MD" => __("Moldova, Republic Of", 'birchschedule'),
		"MC" => __("Monaco", 'birchschedule'),
		"MN" => __("Mongolia", 'birchschedule'),
		"MS" => __("Montserrat", 'birchschedule'),
		"MA" => __("Morocco", 'birchschedule'),
		"MZ" => __("Mozambique", 'birchschedule'),
		"MM" => __("Myanmar", 'birchschedule'),
		"NA" => __("Namibia", 'birchschedule'),
		"NR" => __("Nauru", 'birchschedule'),
		"NP" => __("Nepal", 'birchschedule'),
		"NL" => __("Netherlands", 'birchschedule'),
		"AN" => __("Netherlands Antilles", 'birchschedule'),
		"NC" => __("New Caledonia", 'birchschedule'),
		"NZ" => __("New Zealand", 'birchschedule'),
		"NI" => __("Nicaragua", 'birchschedule'),
		"NE" => __("Niger", 'birchschedule'),
		"NG" => __("Nigeria", 'birchschedule'),
		"NU" => __("Niue", 'birchschedule'),
		"NF" => __("Norfolk Island", 'birchschedule'),
		"MP" => __("Northern Mariana Islands", 'birchschedule'),
		"NO" => __("Norway", 'birchschedule'),
		"OM" => __("Oman", 'birchschedule'),
		"PK" => __("Pakistan", 'birchschedule'),
		"PW" => __("Palau", 'birchschedule'),
		"PA" => __("Panama", 'birchschedule'),
		"PG" => __("Papua New Guinea", 'birchschedule'),
		"PY" => __("Paraguay", 'birchschedule'),
		"PE" => __("Peru", 'birchschedule'),
		"PH" => __("Philippines", 'birchschedule'),
		"PN" => __("Pitcairn", 'birchschedule'),
		"PL" => __("Poland", 'birchschedule'),
		"PT" => __("Portugal", 'birchschedule'),
		"PR" => __("Puerto Rico", 'birchschedule'),
		"QA" => __("Qatar", 'birchschedule'),
		"RE" => __("Reunion", 'birchschedule'),
		"RO" => __("Romania", 'birchschedule'),
		"RU" => __("Russian Federation", 'birchschedule'),
		"RW" => __("Rwanda", 'birchschedule'),
		"KN" => __("Saint Kitts And Nevis", 'birchschedule'),
		"LC" => __("Saint Lucia", 'birchschedule'),
		"VC" => __("Saint Vincent And The Grenadines", 'birchschedule'),
		"WS" => __("Samoa", 'birchschedule'),
		"SM" => __("San Marino", 'birchschedule'),
		"ST" => __("Sao Tome And Principe", 'birchschedule'),
		"SA" => __("Saudi Arabia", 'birchschedule'),
		"SN" => __("Senegal", 'birchschedule'),
		"SC" => __("Seychelles", 'birchschedule'),
		"SL" => __("Sierra Leone", 'birchschedule'),
		"SG" => __("Singapore", 'birchschedule'),
		"SK" => __("Slovakia (Slovak Republic)", 'birchschedule'),
		"SI" => __("Slovenia", 'birchschedule'),
		"SB" => __("Solomon Islands", 'birchschedule'),
		"SO" => __("Somalia", 'birchschedule'),
		"ZA" => __("South Africa", 'birchschedule'),
		"GS" => __("South Georgia, South Sandwich Islands", 'birchschedule'),
		"ES" => __("Spain", 'birchschedule'),
		"LK" => __("Sri Lanka", 'birchschedule'),
		"SH" => __("St. Helena", 'birchschedule'),
		"PM" => __("St. Pierre And Miquelon", 'birchschedule'),
		"SD" => __("Sudan", 'birchschedule'),
		"SR" => __("Suriname", 'birchschedule'),
		"SJ" => __("Svalbard And Jan Mayen Islands", 'birchschedule'),
		"SZ" => __("Swaziland", 'birchschedule'),
		"SE" => __("Sweden", 'birchschedule'),
		"CH" => __("Switzerland", 'birchschedule'),
		"SY" => __("Syrian Arab Republic", 'birchschedule'),
		"TW" => __("Taiwan", 'birchschedule'),
		"TJ" => __("Tajikistan", 'birchschedule'),
		"TZ" => __("Tanzania, United Republic Of", 'birchschedule'),
		"TH" => __("Thailand", 'birchschedule'),
		"TG" => __("Togo", 'birchschedule'),
		"TK" => __("Tokelau", 'birchschedule'),
		"TO" => __("Tonga", 'birchschedule'),
		"TT" => __("Trinidad And Tobago", 'birchschedule'),
		"TN" => __("Tunisia", 'birchschedule'),
		"TR" => __("Turkey", 'birchschedule'),
		"TM" => __("Turkmenistan", 'birchschedule'),
		"TC" => __("Turks And Caicos Islands", 'birchschedule'),
		"TV" => __("Tuvalu", 'birchschedule'),
		"UG" => __("Uganda", 'birchschedule'),
		"UA" => __("Ukraine", 'birchschedule'),
		"AE" => __("United Arab Emirates", 'birchschedule'),
		"GB" => __("United Kingdom", 'birchschedule'),
		"US" => __("United States", 'birchschedule'),
		"UM" => __("United States Minor Outlying Islands", 'birchschedule'),
		"UY" => __("Uruguay", 'birchschedule'),
		"UZ" => __("Uzbekistan", 'birchschedule'),
		"VU" => __("Vanuatu", 'birchschedule'),
		"VE" => __("Venezuela", 'birchschedule'),
		"VN" => __("Viet Nam", 'birchschedule'),
		"VG" => __("Virgin Islands (British)", 'birchschedule'),
		"VI" => __("Virgin Islands (U.S.)", 'birchschedule'),
		"WF" => __("Wallis And Futuna Islands", 'birchschedule'),
		"EH" => __("Western Sahara", 'birchschedule'),
		"YE" => __("Yemen", 'birchschedule'),
		"YU" => __("Yugoslavia", 'birchschedule'),
		"ZM" => __("Zambia", 'birchschedule'),
		"ZW" => __("Zimbabwe", 'birchschedule')
	);
};

$ns->get_us_states = function() use ($ns) {
	$states = $ns->get_states();
	return $states['US'];
};

$ns->get_states = function() use ($ns) {
	return array(
		'US' => array(
			'AL' => __('Alabama (AL)', 'birchschedule'),
			'AK' => __('Alaska (AK)', 'birchschedule'),
			'AZ' => __('Arizona (AZ)', 'birchschedule'),
			'AR' => __('Arkansas (AR)', 'birchschedule'),
			'CA' => __('California (CA)', 'birchschedule'),
			'CO' => __('Colorado (CO)', 'birchschedule'),
			'CT' => __('Connecticut (CT)', 'birchschedule'),
			'DC' => __('District of Columbia (DC)', 'birchschedule'),
			'DE' => __('Delaware (DE)', 'birchschedule'),
			'FL' => __('Florida (FL)', 'birchschedule'),
			'GA' => __('Georgia (GA)', 'birchschedule'),
			'HI' => __('Hawaii (HI)', 'birchschedule'),
			'ID' => __('Idaho (ID)', 'birchschedule'),
			'IL' => __('Illinois (IL)', 'birchschedule'),
			'IN' => __('Indiana (IN)', 'birchschedule'),
			'IA' => __('Iowa (IA)', 'birchschedule'),
			'KS' => __('Kansas (KS)', 'birchschedule'),
			'KY' => __('Kentucky (KY)', 'birchschedule'),
			'LA' => __('Louisiana (LA)', 'birchschedule'),
			'ME' => __('Maine (ME)', 'birchschedule'),
			'MD' => __('Maryland (MD)', 'birchschedule'),
			'MA' => __('Massachusetts (MA)', 'birchschedule'),
			'MI' => __('Michigan (MI)', 'birchschedule'),
			'MN' => __('Minnesota (MN)', 'birchschedule'),
			'MS' => __('Mississippi (MS)', 'birchschedule'),
			'MO' => __('Missouri (MO)', 'birchschedule'),
			'MT' => __('Montana (MT)', 'birchschedule'),
			'NE' => __('Nebraska (NE)', 'birchschedule'),
			'NV' => __('Nevada (NV)', 'birchschedule'),
			'NH' => __('New Hampshire (NH)', 'birchschedule'),
			'NJ' => __('New Jersey (NJ)', 'birchschedule'),
			'NM' => __('New Mexico (NM)', 'birchschedule'),
			'NY' => __('New York (NY)', 'birchschedule'),
			'NC' => __('North Carolina(NC)', 'birchschedule'),
			'ND' => __('North Dakota (ND)', 'birchschedule'),
			'OH' => __('Ohio (OH)', 'birchschedule'),
			'OK' => __('Oklahoma (OK)', 'birchschedule'),
			'OR' => __('Oregon (OR)', 'birchschedule'),
			'PA' => __('Pennsylvania (PA)', 'birchschedule'),
			'PR' => __('Puerto Rico (PR)', 'birchschedule'),
			'RI' => __('Rhode Island (RI)', 'birchschedule'),
			'SC' => __('South Carolina (SC)', 'birchschedule'),
			'SD' => __('South Dakota', 'birchschedule'),
			'TN' => __('Tennessee (TN)', 'birchschedule'),
			'TX' => __('Texas (TX)', 'birchschedule'),
			'UT' => __('Utah (UT)', 'birchschedule'),
			'VA' => __('Virginia (VA)', 'birchschedule'),
			'VI' => __('Virgin Islands (VI)', 'birchschedule'),
			'VT' => __('Vermont (VT)', 'birchschedule'),
			'WA' => __('Washington (WA)', 'birchschedule'),
			'WV' => __('West Virginia (WV)', 'birchschedule'),
			'WI' => __('Wisconsin (WI)', 'birchschedule'),
			'WY' => __('Wyoming (WY)', 'birchschedule')
		),
		'AU' => array(
			'ACT' => __( 'Australian Capital Territory', 'birchschedule' ),
			'NSW' => __( 'New South Wales', 'birchschedule' ),
			'NT'  => __( 'Northern Territory', 'birchschedule' ),
			'QLD' => __( 'Queensland', 'birchschedule' ),
			'SA'  => __( 'South Australia', 'birchschedule' ),
			'TAS' => __( 'Tasmania', 'birchschedule' ),
			'VIC' => __( 'Victoria', 'birchschedule' ),
			'WA'  => __( 'Western Australia', 'birchschedule' )
		),
		'BR' => array(
			'AC' => __( 'Acre', 'birchschedule' ),
			'AL' => __( 'Alagoas', 'birchschedule' ),
			'AP' => __( 'Amap&aacute;', 'birchschedule' ),
			'AM' => __( 'Amazonas', 'birchschedule' ),
			'BA' => __( 'Bahia', 'birchschedule' ),
			'CE' => __( 'Cear&aacute;', 'birchschedule' ),
			'DF' => __( 'Distrito Federal', 'birchschedule' ),
			'ES' => __( 'Esp&iacute;rito Santo', 'birchschedule' ),
			'GO' => __( 'Goi&aacute;s', 'birchschedule' ),
			'MA' => __( 'Maranh&atilde;o', 'birchschedule' ),
			'MT' => __( 'Mato Grosso', 'birchschedule' ),
			'MS' => __( 'Mato Grosso do Sul', 'birchschedule' ),
			'MG' => __( 'Minas Gerais', 'birchschedule' ),
			'PA' => __( 'Par&aacute;', 'birchschedule' ),
			'PB' => __( 'Para&iacute;ba', 'birchschedule' ),
			'PR' => __( 'Paran&aacute;', 'birchschedule' ),
			'PE' => __( 'Pernambuco', 'birchschedule' ),
			'PI' => __( 'Piau&iacute;', 'birchschedule' ),
			'RJ' => __( 'Rio de Janeiro', 'birchschedule' ),
			'RN' => __( 'Rio Grande do Norte', 'birchschedule' ),
			'RS' => __( 'Rio Grande do Sul', 'birchschedule' ),
			'RO' => __( 'Rond&ocirc;nia', 'birchschedule' ),
			'RR' => __( 'Roraima', 'birchschedule' ),
			'SC' => __( 'Santa Catarina', 'birchschedule' ),
			'SP' => __( 'S&atilde;o Paulo', 'birchschedule' ),
			'SE' => __( 'Sergipe', 'birchschedule' ),
			'TO' => __( 'Tocantins', 'birchschedule' )
		),
		'CA' => array(
			'AB' => __( 'Alberta', 'birchschedule' ),
			'BC' => __( 'British Columbia', 'birchschedule' ),
			'MB' => __( 'Manitoba', 'birchschedule' ),
			'NB' => __( 'New Brunswick', 'birchschedule' ),
			'NF' => __( 'Newfoundland', 'birchschedule' ),
			'NT' => __( 'Northwest Territories', 'birchschedule' ),
			'NS' => __( 'Nova Scotia', 'birchschedule' ),
			'NU' => __( 'Nunavut', 'birchschedule' ),
			'ON' => __( 'Ontario', 'birchschedule' ),
			'PE' => __( 'Prince Edward Island', 'birchschedule' ),
			'QC' => __( 'Quebec', 'birchschedule' ),
			'SK' => __( 'Saskatchewan', 'birchschedule' ),
			'YT' => __( 'Yukon Territory', 'birchschedule' )
		),
		'CN' => array(
			'CN1'  => __( 'Yunnan / &#20113;&#21335;', 'birchschedule' ),
			'CN2'  => __( 'Beijing / &#21271;&#20140;', 'birchschedule' ),
			'CN3'  => __( 'Tianjin / &#22825;&#27941;', 'birchschedule' ),
			'CN4'  => __( 'Hebei / &#27827;&#21271;', 'birchschedule' ),
			'CN5'  => __( 'Shanxi / &#23665;&#35199;', 'birchschedule' ),
			'CN6'  => __( 'Inner Mongolia / &#20839;&#33945;&#21476;', 'birchschedule' ),
			'CN7'  => __( 'Liaoning / &#36797;&#23425;', 'birchschedule' ),
			'CN8'  => __( 'Jilin / &#21513;&#26519;', 'birchschedule' ),
			'CN9'  => __( 'Heilongjiang / &#40657;&#40857;&#27743;', 'birchschedule' ),
			'CN10' => __( 'Shanghai / &#19978;&#28023;', 'birchschedule' ),
			'CN11' => __( 'Jiangsu / &#27743;&#33487;', 'birchschedule' ),
			'CN12' => __( 'Zhejiang / &#27993;&#27743;', 'birchschedule' ),
			'CN13' => __( 'Anhui / &#23433;&#24509;', 'birchschedule' ),
			'CN14' => __( 'Fujian / &#31119;&#24314;', 'birchschedule' ),
			'CN15' => __( 'Jiangxi / &#27743;&#35199;', 'birchschedule' ),
			'CN16' => __( 'Shandong / &#23665;&#19996;', 'birchschedule' ),
			'CN17' => __( 'Henan / &#27827;&#21335;', 'birchschedule' ),
			'CN18' => __( 'Hubei / &#28246;&#21271;', 'birchschedule' ),
			'CN19' => __( 'Hunan / &#28246;&#21335;', 'birchschedule' ),
			'CN20' => __( 'Guangdong / &#24191;&#19996;', 'birchschedule' ),
			'CN21' => __( 'Guangxi Zhuang / &#24191;&#35199;&#22766;&#26063;', 'birchschedule' ),
			'CN22' => __( 'Hainan / &#28023;&#21335;', 'birchschedule' ),
			'CN23' => __( 'Chongqing / &#37325;&#24198;', 'birchschedule' ),
			'CN24' => __( 'Sichuan / &#22235;&#24029;', 'birchschedule' ),
			'CN25' => __( 'Guizhou / &#36149;&#24030;', 'birchschedule' ),
			'CN26' => __( 'Shaanxi / &#38485;&#35199;', 'birchschedule' ),
			'CN27' => __( 'Gansu / &#29976;&#32899;', 'birchschedule' ),
			'CN28' => __( 'Qinghai / &#38738;&#28023;', 'birchschedule' ),
			'CN29' => __( 'Ningxia Hui / &#23425;&#22799;', 'birchschedule' ),
			'CN30' => __( 'Macau / &#28595;&#38376;', 'birchschedule' ),
			'CN31' => __( 'Tibet / &#35199;&#34255;', 'birchschedule' ),
			'CN32' => __( 'Xinjiang / &#26032;&#30086;', 'birchschedule' )
		),
		"ES" => array(
			'C' => __('A Coru&ntilde;a', 'birchschedule'),
			'VI' => __('&Aacute;lava', 'birchschedule'),
			'AB' => __('Albacete', 'birchschedule'),
			'A' => __('Alicante', 'birchschedule'),
			'AL' => __('Almer&iacute;a', 'birchschedule'),
			'O' => __('Asturias', 'birchschedule'),
			'AV' => __('&Aacute;vila', 'birchschedule'),
			'BA' => __('Badajoz', 'birchschedule'),
			'PM' => __('Baleares', 'birchschedule'),
			'B' => __('Barcelona', 'birchschedule'),
			'BU' => __('Burgos', 'birchschedule'),
			'CC' => __('C&aacute;ceres', 'birchschedule'),
			'CA' => __('C&aacute;diz', 'birchschedule'),
			'S' => __('Cantabria', 'birchschedule'),
			'CS' => __('Castell&oacute;n', 'birchschedule'),
			'CE' => __('Ceuta', 'birchschedule'),
			'CR' => __('Ciudad Real', 'birchschedule'),
			'CO' => __('C&oacute;rdoba', 'birchschedule'),
			'CU' => __('Cuenca', 'birchschedule'),
			'GI' => __('Girona', 'birchschedule'),
			'GR' => __('Granada', 'birchschedule'),
			'GU' => __('Guadalajara', 'birchschedule'),
			'SS' => __('Guip&uacute;zcoa', 'birchschedule'),
			'H' => __('Huelva', 'birchschedule'),
			'HU' => __('Huesca', 'birchschedule'),
			'J' => __('Ja&eacute;n', 'birchschedule'),
			'LO' => __('La Rioja', 'birchschedule'),
			'GC' => __('Las Palmas', 'birchschedule'),
			'LE' => __('Le&oacute;n', 'birchschedule'),
			'L' => __('Lleida', 'birchschedule'),
			'LU' => __('Lugo', 'birchschedule'),
			'M' => __('Madrid', 'birchschedule'),
			'MA' => __('M&aacute;laga', 'birchschedule'),
			'ML' => __('Melilla', 'birchschedule'),
			'MU' => __('Murcia', 'birchschedule'),
			'NA' => __('Navarra', 'birchschedule'),
			'OR' => __('Ourense', 'birchschedule'),
			'P' => __('Palencia', 'birchschedule'),
			'PO' => __('Pontevedra', 'birchschedule'),
			'SA' => __('Salamanca', 'birchschedule'),
			'TF' => __('Santa Cruz de Tenerife', 'birchschedule'),
			'SG' => __('Segovia', 'birchschedule'),
			'SE' => __('Sevilla', 'birchschedule'),
			'SO' => __('Soria', 'birchschedule'),
			'T' => __('Tarragona', 'birchschedule'),
			'TE' => __('Teruel', 'birchschedule'),
			'TO' => __('Toledo', 'birchschedule'),
			'V' => __('Valencia', 'birchschedule'),
			'VA' => __('Valladolid', 'birchschedule'),
			'BI' => __('Vizcaya', 'birchschedule'),
			'ZA' => __('Zamora', 'birchschedule'),
			'Z' => __('Zaragoza', 'birchschedule')
		),
		'HK' => array(
			'HONG KONG'       => __( 'Hong Kong Island', 'birchschedule' ),
			'KOWLOON'         => __( 'Kowloon', 'birchschedule' ),
			'NEW TERRITORIES' => __( 'New Territories', 'birchschedule' )
		),
		'HU' => array(
			'BK' => __( 'Bács-Kiskun', 'birchschedule' ),
			'BE' => __( 'Békés', 'birchschedule' ),
			'BA' => __( 'Baranya', 'birchschedule' ),
			'BZ' => __( 'Borsod-Abaúj-Zemplén', 'birchschedule' ),
			'BU' => __( 'Budapest', 'birchschedule' ),
			'CS' => __( 'Csongrád', 'birchschedule' ),
			'FE' => __( 'Fejér', 'birchschedule' ),
			'GS' => __( 'Győr-Moson-Sopron', 'birchschedule' ),
			'HB' => __( 'Hajdú-Bihar', 'birchschedule' ),
			'HE' => __( 'Heves', 'birchschedule' ),
			'JN' => __( 'Jász-Nagykun-Szolnok', 'birchschedule' ),
			'KE' => __( 'Komárom-Esztergom', 'birchschedule' ),
			'NO' => __( 'Nógrád', 'birchschedule' ),
			'PE' => __( 'Pest', 'birchschedule' ),
			'SO' => __( 'Somogy', 'birchschedule' ),
			'SZ' => __( 'Szabolcs-Szatmár-Bereg', 'birchschedule' ),
			'TO' => __( 'Tolna', 'birchschedule' ),
			'VA' => __( 'Vas', 'birchschedule' ),
			'VE' => __( 'Veszprém', 'birchschedule' ),
			'ZA' => __( 'Zala', 'birchschedule' )
		),
		'HZ' => array(
			'AK' => __( 'Auckland', 'birchschedule' ),
			'BP' => __( 'Bay of Plenty', 'birchschedule' ),
			'CT' => __( 'Canterbury', 'birchschedule' ),
			'HB' => __( 'Hawke&rsquo;s Bay', 'birchschedule' ),
			'MW' => __( 'Manawatu-Wanganui', 'birchschedule' ),
			'MB' => __( 'Marlborough', 'birchschedule' ),
			'NS' => __( 'Nelson', 'birchschedule' ),
			'NL' => __( 'Northland', 'birchschedule' ),
			'OT' => __( 'Otago', 'birchschedule' ),
			'SL' => __( 'Southland', 'birchschedule' ),
			'TK' => __( 'Taranaki', 'birchschedule' ),
			'TM' => __( 'Tasman', 'birchschedule' ),
			'WA' => __( 'Waikato', 'birchschedule' ),
			'WE' => __( 'Wellington', 'birchschedule' ),
			'WC' => __( 'West Coast', 'birchschedule' )
		),
		'ID' => array(
			'AC'    => __( 'Daerah Istimewa Aceh', 'birchschedule' ),
			'SU' => __( 'Sumatera Utara', 'birchschedule' ),
			'SB' => __( 'Sumatera Barat', 'birchschedule' ),
			'RI' => __( 'Riau', 'birchschedule' ),
			'KR' => __( 'Kepulauan Riau', 'birchschedule' ),
			'JA' => __( 'Jambi', 'birchschedule' ),
			'SS' => __( 'Sumatera Selatan', 'birchschedule' ),
			'BB' => __( 'Bangka Belitung', 'birchschedule' ),
			'BE' => __( 'Bengkulu', 'birchschedule' ),
			'LA' => __( 'Lampung', 'birchschedule' ),
			'JK' => __( 'DKI Jakarta', 'birchschedule' ),
			'JB' => __( 'Jawa Barat', 'birchschedule' ),
			'BT' => __( 'Banten', 'birchschedule' ),
			'JT' => __( 'Jawa Tengah', 'birchschedule' ),
			'JI' => __( 'Jawa Timur', 'birchschedule' ),
			'YO' => __( 'Daerah Istimewa Yogyakarta', 'birchschedule' ),
			'BA' => __( 'Bali', 'birchschedule' ),
			'NB' => __( 'Nusa Tenggara Barat', 'birchschedule' ),
			'NT' => __( 'Nusa Tenggara Timur', 'birchschedule' ),
			'KB' => __( 'Kalimantan Barat', 'birchschedule' ),
			'KT' => __( 'Kalimantan Tengah', 'birchschedule' ),
			'KI' => __( 'Kalimantan Timur', 'birchschedule' ),
			'KS' => __( 'Kalimantan Selatan', 'birchschedule' ),
			'KU' => __( 'Kalimantan Utara', 'birchschedule' ),
			'SA' => __( 'Sulawesi Utara', 'birchschedule' ),
			'ST' => __( 'Sulawesi Tengah', 'birchschedule' ),
			'SG' => __( 'Sulawesi Tenggara', 'birchschedule' ),
			'SR' => __( 'Sulawesi Barat', 'birchschedule' ),
			'SN' => __( 'Sulawesi Selatan', 'birchschedule' ),
			'GO' => __( 'Gorontalo', 'birchschedule' ),
			'MA' => __( 'Maluku', 'birchschedule' ),
			'MU' => __( 'Maluku Utara', 'birchschedule' ),
			'PA' => __( 'Papua', 'birchschedule' ),
			'PB' => __( 'Papua Barat', 'birchschedule' )
		),
		'IN' => array(
			'AP' => __( 'Andra Pradesh', 'birchschedule' ),
			'AR' => __( 'Arunachal Pradesh', 'birchschedule' ),
			'AS' => __( 'Assam', 'birchschedule' ),
			'BR' => __( 'Bihar', 'birchschedule' ),
			'CT' => __( 'Chhattisgarh', 'birchschedule' ),
			'GA' => __( 'Goa', 'birchschedule' ),
			'GJ' => __( 'Gujarat', 'birchschedule' ),
			'HR' => __( 'Haryana', 'birchschedule' ),
			'HP' => __( 'Himachal Pradesh', 'birchschedule' ),
			'JK' => __( 'Jammu and Kashmir', 'birchschedule' ),
			'JH' => __( 'Jharkhand', 'birchschedule' ),
			'KA' => __( 'Karnataka', 'birchschedule' ),
			'KL' => __( 'Kerala', 'birchschedule' ),
			'MP' => __( 'Madhya Pradesh', 'birchschedule' ),
			'MH' => __( 'Maharashtra', 'birchschedule' ),
			'MN' => __( 'Manipur', 'birchschedule' ),
			'ML' => __( 'Meghalaya', 'birchschedule' ),
			'MZ' => __( 'Mizoram', 'birchschedule' ),
			'NL' => __( 'Nagaland', 'birchschedule' ),
			'OR' => __( 'Orissa', 'birchschedule' ),
			'PB' => __( 'Punjab', 'birchschedule' ),
			'RJ' => __( 'Rajasthan', 'birchschedule' ),
			'SK' => __( 'Sikkim', 'birchschedule' ),
			'TN' => __( 'Tamil Nadu', 'birchschedule' ),
			'TR' => __( 'Tripura', 'birchschedule' ),
			'UT' => __( 'Uttaranchal', 'birchschedule' ),
			'UP' => __( 'Uttar Pradesh', 'birchschedule' ),
			'WB' => __( 'West Bengal', 'birchschedule' ),
			'AN' => __( 'Andaman and Nicobar Islands', 'birchschedule' ),
			'CH' => __( 'Chandigarh', 'birchschedule' ),
			'DN' => __( 'Dadar and Nagar Haveli', 'birchschedule' ),
			'DD' => __( 'Daman and Diu', 'birchschedule' ),
			'DL' => __( 'Delhi', 'birchschedule' ),
			'LD' => __( 'Lakshadeep', 'birchschedule' ),
			'PY' => __( 'Pondicherry (Puducherry)', 'birchschedule' )
		),
		'MY' => array(
			'JHR' => __( 'Johor', 'birchschedule' ),
			'KDH' => __( 'Kedah', 'birchschedule' ),
			'KTN' => __( 'Kelantan', 'birchschedule' ),
			'MLK' => __( 'Melaka', 'birchschedule' ),
			'NSN' => __( 'Negeri Sembilan', 'birchschedule' ),
			'PHG' => __( 'Pahang', 'birchschedule' ),
			'PRK' => __( 'Perak', 'birchschedule' ),
			'PLS' => __( 'Perlis', 'birchschedule' ),
			'PNG' => __( 'Pulau Pinang', 'birchschedule' ),
			'SBH' => __( 'Sabah', 'birchschedule' ),
			'SWK' => __( 'Sarawak', 'birchschedule' ),
			'SGR' => __( 'Selangor', 'birchschedule' ),
			'TRG' => __( 'Terengganu', 'birchschedule' ),
			'KUL' => __( 'W.P. Kuala Lumpur', 'birchschedule' ),
			'LBN' => __( 'W.P. Labuan', 'birchschedule' ),
			'PJY' => __( 'W.P. Putrajaya', 'birchschedule' )
		),
		'NZ' => array(
			'NL' => __( 'Northland', 'birchschedule' ),
			'AK' => __( 'Auckland', 'birchschedule' ),
			'WA' => __( 'Waikato', 'birchschedule' ),
			'BP' => __( 'Bay of Plenty', 'birchschedule' ),
			'TK' => __( 'Taranaki', 'birchschedule' ),
			'HB' => __( 'Hawke&rsquo;s Bay', 'birchschedule' ),
			'MW' => __( 'Manawatu-Wanganui', 'birchschedule' ),
			'WE' => __( 'Wellington', 'birchschedule' ),
			'NS' => __( 'Nelson', 'birchschedule' ),
			'MB' => __( 'Marlborough', 'birchschedule' ),
			'TM' => __( 'Tasman', 'birchschedule' ),
			'WC' => __( 'West Coast', 'birchschedule' ),
			'CT' => __( 'Canterbury', 'birchschedule' ),
			'OT' => __( 'Otago', 'birchschedule' ),
			'SL' => __( 'Southland', 'birchschedule')
		),
		'TH' => array(
			'TH-37' => __( 'Amnat Charoen (&#3629;&#3635;&#3609;&#3634;&#3592;&#3648;&#3592;&#3619;&#3636;&#3597;)', 'birchschedule' ),
			'TH-15' => __( 'Ang Thong (&#3629;&#3656;&#3634;&#3591;&#3607;&#3629;&#3591;)', 'birchschedule' ),
			'TH-14' => __( 'Ayutthaya (&#3614;&#3619;&#3632;&#3609;&#3588;&#3619;&#3624;&#3619;&#3637;&#3629;&#3618;&#3640;&#3608;&#3618;&#3634;)', 'birchschedule' ),
			'TH-10' => __( 'Bangkok (&#3585;&#3619;&#3640;&#3591;&#3648;&#3607;&#3614;&#3617;&#3627;&#3634;&#3609;&#3588;&#3619;)', 'birchschedule' ),
			'TH-38' => __( 'Bueng Kan (&#3610;&#3638;&#3591;&#3585;&#3634;&#3628;)', 'birchschedule' ),
			'TH-31' => __( 'Buri Ram (&#3610;&#3640;&#3619;&#3637;&#3619;&#3633;&#3617;&#3618;&#3660;)', 'birchschedule' ),
			'TH-24' => __( 'Chachoengsao (&#3593;&#3632;&#3648;&#3594;&#3636;&#3591;&#3648;&#3607;&#3619;&#3634;)', 'birchschedule' ),
			'TH-18' => __( 'Chai Nat (&#3594;&#3633;&#3618;&#3609;&#3634;&#3607;)', 'birchschedule' ),
			'TH-36' => __( 'Chaiyaphum (&#3594;&#3633;&#3618;&#3616;&#3641;&#3617;&#3636;)', 'birchschedule' ),
			'TH-22' => __( 'Chanthaburi (&#3592;&#3633;&#3609;&#3607;&#3610;&#3640;&#3619;&#3637;)', 'birchschedule' ),
			'TH-50' => __( 'Chiang Mai (&#3648;&#3594;&#3637;&#3618;&#3591;&#3651;&#3627;&#3617;&#3656;)', 'birchschedule' ),
			'TH-57' => __( 'Chiang Rai (&#3648;&#3594;&#3637;&#3618;&#3591;&#3619;&#3634;&#3618;)', 'birchschedule' ),
			'TH-20' => __( 'Chonburi (&#3594;&#3621;&#3610;&#3640;&#3619;&#3637;)', 'birchschedule' ),
			'TH-86' => __( 'Chumphon (&#3594;&#3640;&#3617;&#3614;&#3619;)', 'birchschedule' ),
			'TH-46' => __( 'Kalasin (&#3585;&#3634;&#3628;&#3626;&#3636;&#3609;&#3608;&#3640;&#3660;)', 'birchschedule' ),
			'TH-62' => __( 'Kamphaeng Phet (&#3585;&#3635;&#3649;&#3614;&#3591;&#3648;&#3614;&#3594;&#3619;)', 'birchschedule' ),
			'TH-71' => __( 'Kanchanaburi (&#3585;&#3634;&#3597;&#3592;&#3609;&#3610;&#3640;&#3619;&#3637;)', 'birchschedule' ),
			'TH-40' => __( 'Khon Kaen (&#3586;&#3629;&#3609;&#3649;&#3585;&#3656;&#3609;)', 'birchschedule' ),
			'TH-81' => __( 'Krabi (&#3585;&#3619;&#3632;&#3610;&#3637;&#3656;)', 'birchschedule' ),
			'TH-52' => __( 'Lampang (&#3621;&#3635;&#3611;&#3634;&#3591;)', 'birchschedule' ),
			'TH-51' => __( 'Lamphun (&#3621;&#3635;&#3614;&#3641;&#3609;)', 'birchschedule' ),
			'TH-42' => __( 'Loei (&#3648;&#3621;&#3618;)', 'birchschedule' ),
			'TH-16' => __( 'Lopburi (&#3621;&#3614;&#3610;&#3640;&#3619;&#3637;)', 'birchschedule' ),
			'TH-58' => __( 'Mae Hong Son (&#3649;&#3617;&#3656;&#3630;&#3656;&#3629;&#3591;&#3626;&#3629;&#3609;)', 'birchschedule' ),
			'TH-44' => __( 'Maha Sarakham (&#3617;&#3627;&#3634;&#3626;&#3634;&#3619;&#3588;&#3634;&#3617;)', 'birchschedule' ),
			'TH-49' => __( 'Mukdahan (&#3617;&#3640;&#3585;&#3604;&#3634;&#3627;&#3634;&#3619;)', 'birchschedule' ),
			'TH-26' => __( 'Nakhon Nayok (&#3609;&#3588;&#3619;&#3609;&#3634;&#3618;&#3585;)', 'birchschedule' ),
			'TH-73' => __( 'Nakhon Pathom (&#3609;&#3588;&#3619;&#3611;&#3600;&#3617;)', 'birchschedule' ),
			'TH-48' => __( 'Nakhon Phanom (&#3609;&#3588;&#3619;&#3614;&#3609;&#3617;)', 'birchschedule' ),
			'TH-30' => __( 'Nakhon Ratchasima (&#3609;&#3588;&#3619;&#3619;&#3634;&#3594;&#3626;&#3637;&#3617;&#3634;)', 'birchschedule' ),
			'TH-60' => __( 'Nakhon Sawan (&#3609;&#3588;&#3619;&#3626;&#3623;&#3619;&#3619;&#3588;&#3660;)', 'birchschedule' ),
			'TH-80' => __( 'Nakhon Si Thammarat (&#3609;&#3588;&#3619;&#3624;&#3619;&#3637;&#3608;&#3619;&#3619;&#3617;&#3619;&#3634;&#3594;)', 'birchschedule' ),
			'TH-55' => __( 'Nan (&#3609;&#3656;&#3634;&#3609;)', 'birchschedule' ),
			'TH-96' => __( 'Narathiwat (&#3609;&#3619;&#3634;&#3608;&#3636;&#3623;&#3634;&#3626;)', 'birchschedule' ),
			'TH-39' => __( 'Nong Bua Lam Phu (&#3627;&#3609;&#3629;&#3591;&#3610;&#3633;&#3623;&#3621;&#3635;&#3616;&#3641;)', 'birchschedule' ),
			'TH-43' => __( 'Nong Khai (&#3627;&#3609;&#3629;&#3591;&#3588;&#3634;&#3618;)', 'birchschedule' ),
			'TH-12' => __( 'Nonthaburi (&#3609;&#3609;&#3607;&#3610;&#3640;&#3619;&#3637;)', 'birchschedule' ),
			'TH-13' => __( 'Pathum Thani (&#3611;&#3607;&#3640;&#3617;&#3608;&#3634;&#3609;&#3637;)', 'birchschedule' ),
			'TH-94' => __( 'Pattani (&#3611;&#3633;&#3605;&#3605;&#3634;&#3609;&#3637;)', 'birchschedule' ),
			'TH-82' => __( 'Phang Nga (&#3614;&#3633;&#3591;&#3591;&#3634;)', 'birchschedule' ),
			'TH-93' => __( 'Phatthalung (&#3614;&#3633;&#3607;&#3621;&#3640;&#3591;)', 'birchschedule' ),
			'TH-56' => __( 'Phayao (&#3614;&#3632;&#3648;&#3618;&#3634;)', 'birchschedule' ),
			'TH-67' => __( 'Phetchabun (&#3648;&#3614;&#3594;&#3619;&#3610;&#3641;&#3619;&#3603;&#3660;)', 'birchschedule' ),
			'TH-76' => __( 'Phetchaburi (&#3648;&#3614;&#3594;&#3619;&#3610;&#3640;&#3619;&#3637;)', 'birchschedule' ),
			'TH-66' => __( 'Phichit (&#3614;&#3636;&#3592;&#3636;&#3605;&#3619;)', 'birchschedule' ),
			'TH-65' => __( 'Phitsanulok (&#3614;&#3636;&#3625;&#3603;&#3640;&#3650;&#3621;&#3585;)', 'birchschedule' ),
			'TH-54' => __( 'Phrae (&#3649;&#3614;&#3619;&#3656;)', 'birchschedule' ),
			'TH-83' => __( 'Phuket (&#3616;&#3641;&#3648;&#3585;&#3655;&#3605;)', 'birchschedule' ),
			'TH-25' => __( 'Prachin Buri (&#3611;&#3619;&#3634;&#3592;&#3637;&#3609;&#3610;&#3640;&#3619;&#3637;)', 'birchschedule' ),
			'TH-77' => __( 'Prachuap Khiri Khan (&#3611;&#3619;&#3632;&#3592;&#3623;&#3610;&#3588;&#3637;&#3619;&#3637;&#3586;&#3633;&#3609;&#3608;&#3660;)', 'birchschedule' ),
			'TH-85' => __( 'Ranong (&#3619;&#3632;&#3609;&#3629;&#3591;)', 'birchschedule' ),
			'TH-70' => __( 'Ratchaburi (&#3619;&#3634;&#3594;&#3610;&#3640;&#3619;&#3637;)', 'birchschedule' ),
			'TH-21' => __( 'Rayong (&#3619;&#3632;&#3618;&#3629;&#3591;)', 'birchschedule' ),
			'TH-45' => __( 'Roi Et (&#3619;&#3657;&#3629;&#3618;&#3648;&#3629;&#3655;&#3604;)', 'birchschedule' ),
			'TH-27' => __( 'Sa Kaeo (&#3626;&#3619;&#3632;&#3649;&#3585;&#3657;&#3623;)', 'birchschedule' ),
			'TH-47' => __( 'Sakon Nakhon (&#3626;&#3585;&#3621;&#3609;&#3588;&#3619;)', 'birchschedule' ),
			'TH-11' => __( 'Samut Prakan (&#3626;&#3617;&#3640;&#3607;&#3619;&#3611;&#3619;&#3634;&#3585;&#3634;&#3619;)', 'birchschedule' ),
			'TH-74' => __( 'Samut Sakhon (&#3626;&#3617;&#3640;&#3607;&#3619;&#3626;&#3634;&#3588;&#3619;)', 'birchschedule' ),
			'TH-75' => __( 'Samut Songkhram (&#3626;&#3617;&#3640;&#3607;&#3619;&#3626;&#3591;&#3588;&#3619;&#3634;&#3617;)', 'birchschedule' ),
			'TH-19' => __( 'Saraburi (&#3626;&#3619;&#3632;&#3610;&#3640;&#3619;&#3637;)', 'birchschedule' ),
			'TH-91' => __( 'Satun (&#3626;&#3605;&#3641;&#3621;)', 'birchschedule' ),
			'TH-17' => __( 'Sing Buri (&#3626;&#3636;&#3591;&#3627;&#3660;&#3610;&#3640;&#3619;&#3637;)', 'birchschedule' ),
			'TH-33' => __( 'Sisaket (&#3624;&#3619;&#3637;&#3626;&#3632;&#3648;&#3585;&#3625;)', 'birchschedule' ),
			'TH-90' => __( 'Songkhla (&#3626;&#3591;&#3586;&#3621;&#3634;)', 'birchschedule' ),
			'TH-64' => __( 'Sukhothai (&#3626;&#3640;&#3650;&#3586;&#3607;&#3633;&#3618;)', 'birchschedule' ),
			'TH-72' => __( 'Suphan Buri (&#3626;&#3640;&#3614;&#3619;&#3619;&#3603;&#3610;&#3640;&#3619;&#3637;)', 'birchschedule' ),
			'TH-84' => __( 'Surat Thani (&#3626;&#3640;&#3619;&#3634;&#3625;&#3598;&#3619;&#3660;&#3608;&#3634;&#3609;&#3637;)', 'birchschedule' ),
			'TH-32' => __( 'Surin (&#3626;&#3640;&#3619;&#3636;&#3609;&#3607;&#3619;&#3660;)', 'birchschedule' ),
			'TH-63' => __( 'Tak (&#3605;&#3634;&#3585;)', 'birchschedule' ),
			'TH-92' => __( 'Trang (&#3605;&#3619;&#3633;&#3591;)', 'birchschedule' ),
			'TH-23' => __( 'Trat (&#3605;&#3619;&#3634;&#3604;)', 'birchschedule' ),
			'TH-34' => __( 'Ubon Ratchathani (&#3629;&#3640;&#3610;&#3621;&#3619;&#3634;&#3594;&#3608;&#3634;&#3609;&#3637;)', 'birchschedule' ),
			'TH-41' => __( 'Udon Thani (&#3629;&#3640;&#3604;&#3619;&#3608;&#3634;&#3609;&#3637;)', 'birchschedule' ),
			'TH-61' => __( 'Uthai Thani (&#3629;&#3640;&#3607;&#3633;&#3618;&#3608;&#3634;&#3609;&#3637;)', 'birchschedule' ),
			'TH-53' => __( 'Uttaradit (&#3629;&#3640;&#3605;&#3619;&#3604;&#3636;&#3605;&#3606;&#3660;)', 'birchschedule' ),
			'TH-95' => __( 'Yala (&#3618;&#3632;&#3621;&#3634;)', 'birchschedule' ),
			'TH-35' => __( 'Yasothon (&#3618;&#3650;&#3626;&#3608;&#3619;)', 'birchschedule' )
		),
		'ZA' => array(
			'EC'  => __( 'Eastern Cape', 'birchschedule' ) ,
			'FS'  => __( 'Free State', 'birchschedule' ) ,
			'GP'  => __( 'Gauteng', 'birchschedule' ) ,
			'KZN' => __( 'KwaZulu-Natal', 'birchschedule' ) ,
			'LP'  => __( 'Limpopo', 'birchschedule' ) ,
			'MP'  => __( 'Mpumalanga', 'birchschedule' ) ,
			'NC'  => __( 'Northern Cape', 'birchschedule' ) ,
			'NW'  => __( 'North West', 'birchschedule' ) ,
			'WC'  => __( 'Western Cape', 'birchschedule' )
		)
	);
};

$ns->get_currencies = function() use ( $ns ) {
	return array(
		'USD' => array('title' => __('U.S. Dollar', 'birchschedule'), 'code' => 'USD', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'EUR' => array('title' => __('Euro', 'birchschedule'), 'code' => 'EUR', 'symbol_left' => '', 'symbol_right' => '€', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'GBP' => array('title' => __('Pounds Sterling', 'birchschedule'), 'code' => 'GBP', 'symbol_left' => '£', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'AUD' => array('title' => __('Australian Dollar', 'birchschedule'), 'code' => 'AUD', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'BHD' => array('title' => __('Bahraini dinar', 'birchschedule'), 'code' => 'BHD', 'symbol_left' => '', 'symbol_right' => 'BD', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '3'),
		'BRL' => array('title' => __('Brazilian Real', 'birchschedule'), 'code' => 'BRL', 'symbol_left' => 'R$', 'symbol_right' => '', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
		'CAD' => array('title' => __('Canadian Dollar', 'birchschedule'), 'code' => 'CAD', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'CNY' => array('title' => __('Chinese RMB', 'birchschedule'), 'code' => 'CNY', 'symbol_left' => '￥', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'CZK' => array('title' => __('Czech Koruna', 'birchschedule'), 'code' => 'CZK', 'symbol_left' => '', 'symbol_right' => 'Kč', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
		'DKK' => array('title' => __('Danish Krone', 'birchschedule'), 'code' => 'DKK', 'symbol_left' => '', 'symbol_right' => 'kr', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
		'HKD' => array('title' => __('Hong Kong Dollar', 'birchschedule'), 'code' => 'HKD', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'HUF' => array('title' => __('Hungarian Forint', 'birchschedule'), 'code' => 'HUF', 'symbol_left' => '', 'symbol_right' => 'Ft', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'INR' => array('title' => __('Indian Rupee', 'birchschedule'), 'code' => 'INR', 'symbol_left' => 'Rs.', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'ILS' => array('title' => __('Israeli New Shekel', 'birchschedule'), 'code' => 'ILS', 'symbol_left' => '₪', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'JPY' => array('title' => __('Japanese Yen', 'birchschedule'), 'code' => 'JPY', 'symbol_left' => '¥', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '0'),
		'MYR' => array('title' => __('Malaysian Ringgit', 'birchschedule'), 'code' => 'MYR', 'symbol_left' => 'RM', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'MXN' => array('title' => __('Mexican Peso', 'birchschedule'), 'code' => 'MXN', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'NZD' => array('title' => __('New Zealand Dollar', 'birchschedule'), 'code' => 'NZD', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'NOK' => array('title' => __('Norwegian Krone', 'birchschedule'), 'code' => 'NOK', 'symbol_left' => 'kr', 'symbol_right' => '', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
		'PHP' => array('title' => __('Philippine Peso', 'birchschedule'), 'code' => 'PHP', 'symbol_left' => 'Php', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'PLN' => array('title' => __('Polish Zloty', 'birchschedule'), 'code' => 'PLN', 'symbol_left' => '', 'symbol_right' => 'zł', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
		'RON' => array('title' => __('Romanian leu', 'birchschedule'), 'code' => 'RON', 'symbol_left' => '', 'symbol_right' => 'ron', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
		'SGD' => array('title' => __('Singapore Dollar', 'birchschedule'), 'code' => 'SGD', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'ZAR' => array('title' => __('South Africa Rand', 'birchschedule'), 'code' => 'ZAR', 'symbol_left' => 'R', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'SEK' => array('title' => __('Swedish Krona', 'birchschedule'), 'code' => 'SEK', 'symbol_left' => '', 'symbol_right' => 'kr', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
		'CHF' => array('title' => __('Swiss Franc', 'birchschedule'), 'code' => 'CHF', 'symbol_left' => '', 'symbol_right' => 'CHF', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
		'TWD' => array('title' => __('Taiwan New Dollar', 'birchschedule'), 'code' => 'TWD', 'symbol_left' => 'NT$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'THB' => array('title' => __('Thai Baht', 'birchschedule'), 'code' => 'THB', 'symbol_left' => '', 'symbol_right' => '฿', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'TRY' => array('title' => __('Turkish Lira', 'birchschedule'), 'code' => 'TRY', 'symbol_left' => '', 'symbol_right' => 'TL', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
		'AED' => array('title' => __('United Arab Emirates Dirham', 'birchschedule'), 'code' => 'AED', 'symbol_left' => '', 'symbol_right' => 'AED', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
		'VND' => array('title' => __('VND - Vietnamese Dong', 'birchschedule'), 'code' => 'VND', 'symbol_left' => '₫', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2')
	);
};
