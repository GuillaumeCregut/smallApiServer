<?php

namespace App\Controllers;

use App\Models\HomeModel;
use App\Services\Connector;
use App\Services\JsonResponse;
use App\Services\ErrorResponse;
use App\Services\RequestObject;
use App\Interfaces\ResponseInterface;
use App\Services\ClientErrorResponse;

class HomeController
{
    private RequestObject $request;
    private HomeModel $model;

    public function __construct()
    {
        $connector = new Connector();
        $this->model = new HomeModel($connector->getConnection());
    }
    public function index(RequestObject $request): ResponseInterface
    {
        $this->request = $request;
        //Ici, on vérifie la méthode utilisée 
        switch ($request->getMethod()) {
            case 'GET':
                return $this->getDatas();
            case 'POST':
                return $this->addData();
            case 'PUT':
                return $this->changeData();
            case 'PATCH':
                return $this->changeData();
            case 'DELETE':
                return $this->deleteData();
            default:
                return new ErrorResponse();
        }
    }

    private function getDatas(): ResponseInterface
    {
        //Ici, c'est presque le plus compliqué, car si on demande une seule donnée, il ne faut pas retourner un tableau, mais juste l'entité
        $datas = []; //Le container de données de la BDD
        if (key_exists('id', $this->request->getAllDatas())) {
            //on récupère une donnée en particulier
            $id = (int) $this->request->getAllDatas()['id'];
            $datas = $this->model->getOne($id); // On récupère la donnée en BDD
            if (empty($datas)) {
                //si la donnée n'existe pas
                $response = new ClientErrorResponse(404);
                return $response;
            }
        } else {
            //on récupère toutes les données
            $datas = $this->model->getAll();
        }
        $response = new JsonResponse();
        $response->setBody(json_encode($datas));
        return $response;
    }

    private function addData():ResponseInterface
    {
        //On verifie que toutes les données nécessaires sont présentes et on ajoute
        $error = false;
        $datas = $this->request->getAllDatas();
        //Si il manque des données 
        if ($error) {
            return new ClientErrorResponse(404);
        } else {
            //On ajoute la donnée
            $this->model->add($datas);
            if (!$this->model) {
               return new ErrorResponse();
            }
            return new JsonResponse(201);
        }
    }

    private function changeData(): ResponseInterface
    {
        //On verifie que toutes les données nécessaires sont présentes et on modifie
        $error = false;
        $datas = $this->request->getAllDatas();
        //Si il manque des données 
        if ($error) {
            return new ClientErrorResponse(422);
        } else {
            //On modifie la donnée
            $this->model->update((int)$datas['id'], $datas);
            if (!$this->model) {
                return new ErrorResponse();
            }
            $response = new JsonResponse();
            return $response;
        }
    }
    private function deleteData(): ResponseInterface
    {
        if (key_exists('id', $this->request->getAllDatas())) {
            //on supprime une donnée en particulier
            $id = (int) $this->request->getAllDatas()['id'];
            $this->model->delete($id);
            if (!$this->model) {
               return new ErrorResponse();
            }
        } else {
            return new ClientErrorResponse(404);
        }
        $response = new JsonResponse();
        return $response;
    }
}
