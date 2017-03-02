<?php

/**
 * Created by PhpStorm.
 * User: vasilyevr
 * Date: 02.03.17
 * Time: 11:19
 */
class GoogleMaps {
    protected static function getResponse($parameters) {

        $url  = 'https://maps.googleapis.com/maps/api/directions/json?';
        $url .= http_build_query($parameters);

        return file_get_contents($url);
    }

    public static function getDirection($parameters) {
        $directions = \GuzzleHttp\json_decode(
            self::getResponse(
                [
                    'language' => 'ru',
                    'mode' => 'transit',
                    'origin' => $parameters['origin'],
                    'destination' => $parameters['destination'],
                    'key' => $parameters['KEY'],
                ]
            )
        );
        $direction  = $directions->routes[0]->legs[0];
        $distance   = $direction->distance->text;
        $departure  = $direction->departure_time->text;
        $arrival    = $direction->arrival_time->text;
        $duration   = $direction->duration->text;
        $waypoints  = '';
        foreach ($direction->steps as $step) {
            $step_text = strip_tags($step->html_instructions);
            if (key_exists('transit_details', (array) $step)) {
                $step_stop  = $step->transit_details->arrival_stop->name;
                $step_text .= " до $step_stop";
            }

            $waypoints .= "\n$step_text - {$step->duration->text}";
        }

        return "Расстояние: $distance\nОтправление: $departure\nПрибытие: $arrival\nВремя в пути: $duration\n$waypoints";

    }
}
