<?php

function birchschedule_lib_icalcreator_load() {
    if(!class_exists('vcalendar')) {
        require_once dirname(__FILE__) . '/iCalCreator-2.22/iCalcreator.php';
    }
}