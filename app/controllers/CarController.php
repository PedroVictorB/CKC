<?php

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;

/**
 * Created by PhpStorm.
 * User: Pedro
 * Date: 16/05/2018
 * Time: 12:11
 */
class CarController extends ControllerBase
{
    public function indexAction(){
        //$id = $this->request->get('id');
        $tag = $this->request->get('tag');
        $marca = $this->request->get('marca');
        $nome = $this->request->get('nome');
        $placa = $this->request->get('placa');
        $status = $this->request->get('status');

        $this->view->disable();
        
        if(!isset($tag) || empty($tag)){
            echo json_encode(array('ERROR' => 'TAG IS NULL'));
            return;
        }
        $car = Car::findFirst(array('conditions' => 'tag = ?1 ', 'bind' => array(1 => $tag)));

        error_log("car");


        if(empty($car)){
            //Não é cadastrado ainda
            $car = new Car();
            $car->tag = $tag;
            $car->marca = 'Hyundai';
            $car->nome = 'HB20';
            $car->placa = 'QUG-1921';
            $car->status = 0;
            $car->ptag = $this->config->CKC->ptag;
            
            if(!$car->save()){
                echo json_encode(array('ERROR' => 'DB ERROR WHILE SAVING CAR'));
                return;
            }
        }
        
        // status 0 -> carro na garagem
        // status 1 -> carro na cancela
        // status 2 -> carro em movimento
        
        if($car->status == 0){
            if($status == 1){
                //Carro quer sair

                $client = new GuzzleClient();

                try{
                    $load = array(
                        'contextElements' => [
                            [
                                "type" => "CAR",
                                "isPattern" => "false",
                                "id" => "car".$car->id,
                                "attributes" => [
                                    [
                                        'name' => 'marca',
                                        'type' => 'String',
                                        'value' => ''.$car->marca
                                    ],
                                    [
                                        'name' => 'nome',
                                        'type' => 'String',
                                        'value' => ''.$car->nome
                                    ],
                                    [
                                        'name' => 'placa',
                                        'type' => 'String',
                                        'value' => ''.$car->placa
                                    ],
                                    [
                                        'name' => 'ptag',
                                        'type' => 'String',
                                        'value' => ''.$car->ptag
                                    ],
                                    [
                                        'name' => 'status',
                                        'type' => 'String',
                                        'value' => ''.$car->status
                                    ],
                                    [
                                        'name' => 'tag',
                                        'type' => 'String',
                                        'value' => ''.$car->tag
                                    ],
                                    [
                                        'name' => 'data',
                                        'type' => 'String',
                                        'value' => new DateTime()
                                    ]
                                ]
                            ]
                        ],
                        "updateAction" => "APPEND"
                    );

                    $res = $client->post(
                        $this->config->CKC->ORION_CONFIGURATION->protocol.'://'
                        .$this->config->CKC->ORION_CONFIGURATION->url.':'
                        .$this->config->CKC->ORION_CONFIGURATION->port
                        .'/v1/updateContext'
                        , array(
                            'headers' => [
                                'Content-Type' => 'application/json',
                                'Accept' => 'application/json',
                                'X-Auth-Token' => ''
                            ],
                            "json" => $load
                        )

                    );
                }catch (GuzzleRequestException $e){
                    echo json_encode(
                        array("CKC_STATUS" =>
                            array(
                                "code" => $e->getResponse()->getStatusCode(),
                                "reasonPhrase" => $e->getResponse()->getReasonPhrase(),
                                "details" => "Error while communicating to ORION (Contact admin)"
                            )
                        )
                    );
                    return;
                }
                
                //Atualizar BD
                $car->status = $status;

                if(!$car->save()){
                    echo json_encode(array('ERROR' => 'DB ERROR WHILE UPDATING CAR'));
                    return;
                }
                $client = new GuzzleClient();

                $res = $client->get(
                    $this->config->CKC->ESP_CONFIGURATION->protocol.'://'
                    .$this->config->CKC->ESP_CONFIGURATION->url.':'
                    .$this->config->CKC->ESP_CONFIGURATION->port.'/'
                    .'6'
                );

                echo json_encode(array('SUCCESS' => 'CAR APPROVED'));
                return;
            }
        }else if ($car->status == 1){
            if($status == 1){
                error_log("Carro tentando sair novamente (esperando cracha)");
                //Carro tentando sair novamente (esperando cracha)
            }
        }else if ($car->status == 2){
            if($status == 1){
                echo json_encode(array('ERROR' => 'CAR ALREADY APPROVED'));
                return;
            }
        }else{
            echo json_encode(array('ERROR' => 'CAR STATUS ????'));
            return;
        }

        echo json_encode(array('STATUS' => 'WAITING FOR PERSON ID'));
    }

    public function getPersonCarsAction(){
        $tag = $this->request->get('tag');

        if(!isset($tag) || empty($tag)){
            echo json_encode(array('ERROR' => 'TAG IS EMPTY!'));
            return;
        }

        //$cars = Car::find(array('conditions' => 'ptag = ?1 ', 'bind' => array(1 => $tag)));

        $this->view->disable();

        //echo json_encode($cars->toArray());

        $client = new GuzzleClient();

        try{
            $load = array(
                "entities" => [
                    [
                        "type" => "CAR",
                        "isPattern" => "true",
                        "id" => ".*"
                    ]
                ],
                "restriction" => [
                    "scopes" => [
                        [
                            "type" => "FIWARE::StringQuery",
                            "value" => "ptag==".$tag
                        ]
                    ]
                ]
            );

            $res = $client->post(
                $this->config->CKC->ORION_CONFIGURATION->protocol.'://'
                .$this->config->CKC->ORION_CONFIGURATION->url.':'
                .$this->config->CKC->ORION_CONFIGURATION->port
                .'/v1/queryContext'
                , array(
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'X-Auth-Token' => ''
                    ],
                    "json" => $load
                )

            );
        }catch (GuzzleRequestException $e){
            echo json_encode(
                array("CKC_STATUS" =>
                    array(
                        "code" => $e->getResponse()->getStatusCode(),
                        "reasonPhrase" => $e->getResponse()->getReasonPhrase(),
                        "details" => "Error while communicating to ORION (Contact admin)"
                    )
                )
            );
            return;
        }

        echo $res->getBody();
    }

    public function readingAction(){
        //TODO: REMOVER GAMBY DE DOUGLAS
        $car = json_decode(json_decode(''.$this->request->getRawBody())


        );
        //$car = json_decode("{\"vehicle_id\": \"3\", \"latitude\": \"-5.834284922\", \"longitude\": \"-35.213327736\",\"timestamp\": \"0\", \"reading\": {\"Distance since codes cleared\":\"0\",\"Engine Load\":\"47.058823\",\"Diagnostic Trouble Codes\":\"0\",\"Engine Runtime\":\"4116\",\"Intake Manifold Pressure\":\"153\",\"Distance traveled with MIL on\":\"4096\",\"Long Term Fuel Trim Bank 1\":\"40.625\",\"Long Term Fuel Trim Bank 2\":\"40.625\",\"Fuel Rail Pressure\":\"1550\",\"Absolute load\":\"24.0\",\"Fuel Consumption Rate\":\"6.0\",\"Control Module Power Supply \":\"3.0\",\"Fuel Type\":\"1\",\"Ambient Air Temperature\":\"40.0\",\"Fuel Level\":\"39.215687\",\"Pending Trouble Codes\":\"\",\"Mass Air Flow\":\"257.0\",\"Timing Advance\":\"51.764706\",\"Permanent Trouble Codes\":\"\",\"Vehicle Speed\":\"160\",\"Engine oil temperature\":\"60.0\",\"Engine RPM\":\"3237\",\"Air\/Fuel Ratio\":\"11.598770141601562\",\"Short Term Fuel Trim Bank 1\":\"40.625\",\"Short Term Fuel Trim Bank 2\":\"40.625\",\"Wideband Air\/Fuel Ratio\":\"6.032436847686768\",\"Air Intake Temperature\":\"120.0\",\"Engine Coolant Temperature\":\"121.0\",\"Barometric Pressure\":\"150\",\"Throttle Position\":\"58.82353\",\"Command Equivalence Ratio\":\"0.0\",\"Fuel Pressure\":\"453\"}}");
        /*
         * {vehicle_id: 0, latitude: -5.834284922, longitude: -35.213327736,timestamp: 0,
         * reading: {"Distance since codes cleared":"0","Engine Load":"47.058823","Diagnostic Trouble Codes":"0",
         * "Engine Runtime":"4116","Intake Manifold Pressure":"153",
         * "Distance traveled with MIL on":"4096","Long Term Fuel Trim Bank 1":"40.625",
         * "Long Term Fuel Trim Bank 2":"40.625","Fuel Rail Pressure":"1550","Absolute load":"24.0",
         * "Fuel Consumption Rate":"6.0","Control Module Power Supply ":"3.0","Fuel Type":"1","Ambient Air Temperature":"40.0",
         * "Fuel Level":"39.215687","Pending Trouble Codes":"","Mass Air Flow":"257.0","Timing Advance":"51.764706",
         * "Permanent Trouble Codes":"","Vehicle Speed":"160","Engine oil temperature":"60.0","Engine RPM":"3237",
         * "Air\/Fuel Ratio":"11.598770141601562","Short Term Fuel Trim Bank 1":"40.625","Short Term Fuel Trim Bank 2":"40.625",
         * "Wideband Air\/Fuel Ratio":"6.032436847686768","Air Intake Temperature":"120.0","Engine Coolant Temperature":"121.0",
         * "Barometric Pressure":"150","Throttle Position":"58.82353","Command Equivalence Ratio":"0.0","Fuel Pressure":"453"}}
         */

        $this->view->disable();

        //error_log(print_r(json_decode($this->request->getRawBody()), true));
        error_log(print_r($car, true));
        $client = new GuzzleClient();

        $attributes = array();
        array_push($attributes, array(
            'name' => 'latitude',
            'type' => 'String',
            'value' => ''.$car->latitude
        ));
        array_push($attributes, array(
            'name' => 'longitude',
            'type' => 'String',
            'value' => ''.$car->longitude
        ));
        array_push($attributes, array(
            'name' => 'data',
            'type' => 'String',
            'value' => new DateTime()
        ));

        foreach ($car->reading as $key => $value){
            array_push($attributes, array(
                'name' => ''.$key,
                'type' => 'String',
                'value' => ''.$value
            ));
        }

        try{
            $load = array(
                'contextElements' => [
                    [
                        "type" => "CAR",
                        "isPattern" => "false",
                        "id" => "car".substr(''.$car->vehicle_id, -1),
                        "attributes" => $attributes
                    ]
                ],
                "updateAction" => "APPEND"
            );

            $res = $client->post(
                $this->config->CKC->ORION_CONFIGURATION->protocol.'://'
                .$this->config->CKC->ORION_CONFIGURATION->url.':'
                .$this->config->CKC->ORION_CONFIGURATION->port
                .'/v1/updateContext'
                , array(
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'X-Auth-Token' => ''
                    ],
                    "json" => $load
                )

            );
        }catch (GuzzleRequestException $e){
            echo json_encode(
                array("CKC_STATUS" =>
                    array(
                        "code" => $e->getResponse()->getStatusCode(),
                        "reasonPhrase" => $e->getResponse()->getReasonPhrase(),
                        "details" => "Error while communicating to ORION (Contact admin)"
                    )
                )
            );
            return;
        }

        echo $res->getBody();

    }

    public function testeAction($acao){
        if(empty($acao)){
            $acao = "sem ação :(";
        }
        error_log("REQUEST MADE FROM ".$this->request->getServerAddress());
        error_log("Ação: ".$acao);
        error_log(print_r($this->request->getRawBody(), true));
        $this->view->disable();
        echo
        "
        {
            \"contextResponses\": [
                {
                    \"contextElement\": {
                        \"type\": \"light\",
                        \"isPattern\": \"false\",
                        \"id\": \"lampada7\",
                        \"attributes\": [
                            {
                                \"name\": \"RawCommand\",
                                \"type\": \"command\",
                                \"value\": \"\"
                            }
                        ]
                    },
                    \"statusCode\": {
                        \"code\": \"200\",
                        \"reasonPhrase\": \"OK\"
                    }
                }
            ]
        }
        ";
    }

    public function teste2Action(){
        $client = new GuzzleClient();

        $res = $client->post(
            'https://onesignal.com/api/v1/notifications'
            , array(
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Authorization' => 'Basic YzNiOGUzYjMtZGRiNi00NmU1LTllNzMtMzEzOWE0YTljOWNh'
                ],
                "json" => [
                    'app_id' => 'f7f58a8c-eadb-4b4e-9d9f-27c3953057d3',
                    'included_segments' => ['All'],
                    'headings' => ['en' => 'Entrada Liberada'],
                    'contents' => ['en' => 'Iniciando monitoramento..']
                ]
            )

        );

        error_log(json_encode([
            'app_id' => 'f7f58a8c-eadb-4b4e-9d9f-27c3953057d3',
            'included_segments' => ['All'],
            'headings' => ['en' => 'Entrada Liberada'],
            'contents' => ['en' => 'Iniciando monitoramento..']
        ]));

        error_log(print_r($res, true));
    }
}