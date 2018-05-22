<?php

/**
 * Created by PhpStorm.
 * User: Pedro
 * Date: 02/05/2018
 * Time: 18:40
 */

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use CKC\Utilities\Car as Car;

class KeyController extends ControllerBase
{
    public function indexAction(){
        error_log("aaaaa");
        $client = new GuzzleClient();
        $ck = $this->getRandomCarAndKey();
        $id = $this->request->get('id');
        $nome = $this->request->get('nome');
        $cpf = $this->request->get('cpf');
        //$tag = $this->request->get('tag');
        $tag = 'E2001001250A001514707BC1';

        $person = Person::findFirst(array('conditions' => 'tag = ?1 ', 'bind' => array(1 => $tag)));

        if(empty($person)){
            //Person not registered
            $person = new Person();
            $person->tag = 'E2001001250A001514707BC1';
            $person->nome = $nome;
            $person->cpf = $cpf;

            if(!$person->save()){
                error_log(print_r($person->getMessages(), true));
                echo json_encode(array('ERROR' => 'DB ERROR WHILE SAVING PERSON'));
                return;
            }
        }

        $key = Key::findFirst(array('conditions' => 'ptag = ?1 ', 'bind' => array(1 => $tag)));

        if(!empty($key)){
            echo json_encode(array('ERROR' => 'THIS PERSON HAS A KEY!DON\'T BE GREEDY!'));
            return;
        }

        $key = new Key();
        $key->id = $ck['key'];
        $key->ptag = 'E2001001250A001514707BC1';

        if(!$key->save()){
            echo json_encode(array('ERROR' => 'DB ERROR WHILE SAVING KEY'));
            return;
        };
        
        try{
            $load = array(
                'contextElements' => [
                    [
                        "type" => "KEY",
                        "isPattern" => "false",
                        "id" => "key".$ck['key'],
                        "attributes" => [
                            [
                                'name' => 'nome-pessoa',
                                'type' => 'String',
                                'value' => $nome
                            ],
                            [
                                'name' => 'id-usuario',
                                'type' => 'String',
                                'value' => $id
                            ],
                            [
                                'name' => 'cpf-cnpj',
                                'type' => 'String',
                                'value' => $cpf
                            ],
                            [
                                'name' => 'ptag',
                                'type' => 'String',
                                'value' => $tag
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

        $response = json_decode($res->getBody()->getContents());

        if(isset($response->errorCode) && $response->errorCode->code != 200){
            echo json_encode(
                array("GEBEM_STATUS" =>
                    array(
                        "code" => $response->errorCode->code,
                        "reasonPhrase" => $response->errorCode->reasonPhrase,
                        "details" => isset($response->errorCode->details) ? $response->errorCode->details : "Error"
                    )
                )
            );
            return;
        }

        $res = $client->get(
            $this->config->CKC->ESP_CONFIGURATION->protocol.'://'
            .$this->config->CKC->ESP_CONFIGURATION->url.':'
            .$this->config->CKC->ESP_CONFIGURATION->port.'/'
            .$ck['key']
        );

        $this->view->disable();
        echo json_encode(
            $ck['car']
        );
    }

    public function releaseAction($key2 = null){
        error_log(print_r($key2, true));
        $key = substr($key2, -1);
        if($key == null || $key != 1 || $key != 2 || $key != 3 || $key != 4){
            echo json_encode(array('ERROR' => 'KEY IS NULL OR INCORRECT (1,2,3,4)'));
            return;
        }

        $ckey = Key::findFirst(array('conditions' => 'id = ?1 ', 'bind' => array(1 => $key)));

        if(empty($ckey)){
            echo json_encode(array('ERROR' => 'NO KEY FOUND'));
            return;
        }
        
        $ckey->ptag = '';

        if(!$ckey->save()){
            echo json_encode(array('ERROR' => 'DB ERROR WHILE SAVING KEY'));
            return;
        }

        $client = new GuzzleClient();

        try{
            $load = array(
                'contextElements' => [
                    [
                        "type" => "KEY",
                        "isPattern" => "false",
                        "id" => "key".$key,
                        "attributes" => [
                            [
                                'name' => 'nome-pessoa',
                                'type' => 'String',
                                'value' => ''
                            ],
                            [
                                'name' => 'id-usuario',
                                'type' => 'String',
                                'value' => ''
                            ],
                            [
                                'name' => 'cpf-cnpj',
                                'type' => 'String',
                                'value' => ''
                            ],
                            [
                                'name' => 'ptag',
                                'type' => 'String',
                                'value' => ''
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

        $res = $client->get(
            $this->config->CKC->ESP_CONFIGURATION->protocol.'://'
            .$this->config->CKC->ESP_CONFIGURATION->url.':'
            .$this->config->CKC->ESP_CONFIGURATION->port.'/'
            .$key
        );

        echo json_encode(array('STATUS' => '200'));
        return;
    }

    private function getRandomCarAndKey(){

        $num = rand(1, 4);

        if($num == 1){
            $car = Car::car1;
        }else if($num == 2){
            $car = Car::car2;
        }else if($num == 3){
            $car = Car::car3;
        }else if($num == 4){
            $car = Car::car4;
        }else{
            $car = Car::car1;
        }

        return array('key' => $num, 'car' => $car);
    }
}