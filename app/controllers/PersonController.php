<?php

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;

/**
 * Created by PhpStorm.
 * User: Pedro
 * Date: 16/05/2018
 * Time: 12:11
 */
class PersonController extends ControllerBase
{
    public function indexAction(){
        $id =$this->request->get('id');
        $tag = $this->request->get('tag');
        $nome = $this->request->get('nome');
        $cpf = $this->request->get('cpf');

        $this->view->disable();

        error_log("key");

        if(!isset($tag) || empty($tag)){
            echo json_encode(array('ERROR' => 'TAG IS NULL'));
            return;
        }
        
        $key = Key::findFirst(array('conditions' => 'ptag = ?1 ', 'bind' => array(1 => $tag)));

        if(empty($key)){
            $client = new GuzzleClient();

            $res = $client->get(
                $this->config->CKC->ESP_CONFIGURATION->protocol.'://'
                .$this->config->CKC->ESP_CONFIGURATION->url.':'
                .$this->config->CKC->ESP_CONFIGURATION->port.'/'
                .'5'
            );

            echo json_encode(array('STATUS' => 'SAIDA NAO PERMITIDA - KEY NOT FOUND'));
            return;
        }
        
        $car = Car::findFirst(array('conditions' => 'ptag = ?1 ', 'bind' => array(1 => $tag)));

        if(empty($car)){
            $client = new GuzzleClient();

            $res = $client->get(
                $this->config->CKC->ESP_CONFIGURATION->protocol.'://'
                .$this->config->CKC->ESP_CONFIGURATION->url.':'
                .$this->config->CKC->ESP_CONFIGURATION->port.'/'
                .'5'
            );

            echo json_encode(array('STATUS' => 'SAIDA NAO PERMITIDA - CAR NOT FOUND'));
            return;
        }

        if( $car->status == 0){
            echo json_encode(array('ERROR' => 'CAR WAITING IN THE GARAGE'));
            return;
        } else if($car->status == 1){
            //carro tentando sair -> verificar motorista
            if($car->ptag == $tag){
                $car->status = 2;

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
                        "updateAction" => "UPDATE"
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
                
                if(!$car->save()){
                    echo json_encode(array('ERROR' => 'DB ERROR WHILE UPDATING CAR STATUS'));
                    return;
                }

                $client = new GuzzleClient();

                $res = $client->get(
                    $this->config->CKC->ESP_CONFIGURATION->protocol.'://'
                    .$this->config->CKC->ESP_CONFIGURATION->url.':'
                    .$this->config->CKC->ESP_CONFIGURATION->port.'/'
                    .'7'
                );

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

                //liberado
                echo json_encode(array('STATUS' => 'APPROVED'));
                return;
            }else{
                $client = new GuzzleClient();

                $res = $client->get(
                    $this->config->CKC->ESP_CONFIGURATION->protocol.'://'
                    .$this->config->CKC->ESP_CONFIGURATION->url.':'
                    .$this->config->CKC->ESP_CONFIGURATION->port.'/'
                    .'5'
                );

                echo json_encode(array('STATUS' => 'SAIDA NAO PERMITIDA'));
                return;

            }
        } else if($car->status == 2){
            echo json_encode(array('ERROR' => 'CAR ALREADY MOVING'));
            return;
        }

        echo json_encode(array('ERROR' => 'SOMETHING WRONG IS NOT RIGHT'));
    }

    public function loginAction(){

        error_log(print_r($this->request->getRawBody(), true));
        error_log(print_r($this->request->get(), true));
        error_log($this->request->get("nome")." ".$this->request->get("cpf"));
        error_log("111");
        $client = new GuzzleClient();
        $id = $this->request->get('id');
        $nome = $this->request->get('nome');
        $cpf = $this->request->get('cpf');
        //$tag = $this->request->get('tag');
        $tag = $this->config->CKC->ptag;

        $this->view->disable();

        $person = Person::findFirst(array('conditions' => 'tag = ?1 ', 'bind' => array(1 => $tag)));

        if(empty($person)){
            //Person not registered
            $person = new Person();
            $person->tag = $this->config->CKC->ptag;
            $person->nome = $nome;
            $person->cpf = $cpf;

            if(!$person->save()){
                error_log(print_r($person->getMessages(), true));
                echo json_encode(array('ERROR' => 'DB ERROR WHILE SAVING PERSON'));
                return;
            }
        }else{
            if($person->nome != $nome){
                $person->tag = $this->config->CKC->ptag;
                $person->nome = $nome;
                $person->cpf = $cpf;

                if(!$person->save()){
                    error_log(print_r($person->getMessages(), true));
                    echo json_encode(array('ERROR' => 'DB ERROR WHILE SAVING PERSON'));
                    return;
                }
            }
        }

        echo json_encode(['tag' => $this->config->CKC->ptag]);
    }
}