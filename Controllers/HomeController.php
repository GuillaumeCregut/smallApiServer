<?php

namespace App\Controllers;

use App\Models\HomeModel;
use App\Services\Connector;
use App\Services\Responses\JsonResponse;
use App\Services\Responses\ErrorResponse;
use App\Kernel\RequestObject;
use App\Kernel\AbstractController;
use App\Kernel\Interfaces\ResponseInterface;
use App\Services\Responses\ClientErrorResponse;
use App\Interfaces\AuthenticationInterface;

class HomeController extends AbstractController
{

    public function __construct(AuthenticationInterface $authMiddleware)
    {
        parent::__construct($authMiddleware);
        $this->connector = new Connector();
    }
    public function index(): ResponseInterface
    {
        
        //Ici, on vérifie la méthode utilisée 
        switch ($this->request->getMethod()) {
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
                return $this->returnError(405);
        }
    }

    private function getDatas(): ResponseInterface
    {
        //example of authentication check
        // if (!$this->isUserAuth()) {
        //     return $this->returnError(401);
        // }
        $model = new HomeModel($this->connector->getConnection());
        //Ici, c'est presque le plus compliqué, car si on demande une seule donnée, il ne faut pas retourner un tableau, mais juste l'entité
        $datas = []; //Le container de données de la BDD
        if (key_exists('id', $this->request->getAllDatas())) {
            //on récupère une donnée en particulier
            $id = (int) $this->request->getAllDatas()['id'];
            
            $datas = $model->getOne($id); // On récupère la donnée en BDD
            if (empty($datas)) {
                //si la donnée n'existe pas
                $response = new ClientErrorResponse(404);
                return $response;
            }
        } else {
            //on récupère toutes les données
            $datas = $model->getAll();
        }
        $response = new JsonResponse();
        $response->setBody($datas);
        return $response;
    }

    private function addData(): ResponseInterface
    {
        //On verifie que toutes les données nécessaires sont présentes et on ajoute
        $error = false;
        $datas = $this->request->getAllDatas();
        //Si il manque des données 
        if ($error) {
            return new ClientErrorResponse(404);
        } else {
            //On ajoute la donnée
            $model = new HomeModel($this->connector->getConnection());
            $model->add($datas);
            if (!$model) {
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
            $model = new HomeModel($this->connector->getConnection());
            $model->update((int)$datas['id'], $datas);
            if (!$model) {
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
            $model = new HomeModel($this->connector->getConnection());
            $model->delete($id);
            if (!$model) {
               return new ErrorResponse();
            }
        } else {
            return new ClientErrorResponse(404);
        }
        $response = new JsonResponse();
        return $response;
    }
}
