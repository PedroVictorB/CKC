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
    public function indexAction($id = 'aa', $nome = 'bb', $cpf = 'cc'){
        $client = new GuzzleClient();
        $token = "";
        $ck = $this->getRandomCarAndKey();
        $id = $this->request->get('id');
        $nome = $this->request->get('nome');
        $cpf = $this->request->get('cpf');
        
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
                        'X-Auth-Token' => $token
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
            array($ck['car'])
        );
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