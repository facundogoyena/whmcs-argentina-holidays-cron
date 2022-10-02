<?php

use WHMCS\Database\Capsule;

include("../init.php");

try {
    if (!Capsule::schema()->hasTable('fgholidays')) {
        Capsule::schema()->create('fgholidays', function ($table) {
            $table->integer('id');
            $table->increments('id');
            $table->date('at');
            $table->string('reason');
            $table->primary('id');
        });
    } else {
        Capsule::table('fgholidays')->delete();
    }
} catch (\Exception $e) {
    http_response_code(500);
    die("Error interno (Conexion DB / Tabla).");
}

$year = date('Y');

try {
    $connection = curl_init();
    $options = array(
        CURLOPT_URL => 'http://www.nolaborables.com.ar/api/v2/feriados/' . $year,

        CURLOPT_HEADER => true,
        CURLINFO_HEADER_OUT => true,
        CURLOPT_FRESH_CONNECT => true,

        CURLOPT_CONNECTTIMEOUT => 60,

        CURLOPT_VERBOSE => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,

        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    );

    curl_setopt_array($connection, $options);

    $data = curl_exec($connection);
    $response = curl_getinfo($connection);

    $data = json_decode(substr($data, $response['header_size']));

    curl_close($connection);
} catch (\Exception $e) {
    http_response_code(500);
    die("Error interno (API nolaborables.com.ar).");
}

$holidays = array();

foreach ($data as $holiday) {
    $holidayDate = DateTime::createFromFormat('Y-m-d', $year . '-' . $holiday->mes . '-' . $holiday->dia);

    array_push($holidays, array(
        'reason' => $holiday->motivo,
        'at' => $holidayDate->format('Y-m-d')
    ));
}

Capsule::table('fgholidays')->insert($holidays);