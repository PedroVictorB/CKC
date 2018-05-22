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
        
        $key = Key::findFirst(array('conditions' => 'ptag = ?1 ', 'bind' => array(1 => $tag)));

        if(empty($key)){
            echo json_encode(array('ERROR' => 'NO KEY FOUND FOR TAG '.$tag));
            return;
        }
        
        $car = Car::findFirst(array('conditions' => 'ptag = ?1 ', 'bind' => array(1 => $tag)));

        if(empty($car)){
            echo json_encode(array('ERROR' => 'NO CAR FOUND FOR TAG '.$tag));
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
}