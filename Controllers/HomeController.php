<?php

namespace App\Controllers;

use App\Services\RequestObject;
use App\Models\HomeModel;
use App\Services\Connector;
class HomeController
{
    private RequestObject $request;
    private HomeModel $model;

    public function __construct()
    {
        $connector = new Connector();
        $this->model = new HomeModel($connector->getConnection());
    }
    public function index(RequestObject $request): string
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
                return 'Méthode non supportée';
        }
    }

    private function getDatas(): string
    {
        //Ici, c'est presque le plus compliqué, car si on demande une seule donnée, il ne faut pas retourner un tableau, mais juste l'entité
        $datas = []; //Le container de données de la BDD
        if (key_exists('id', $this->request->getAllDatas())) {
            //on récupère une donnée en particulier
            $id = (int) $this->request->getAllDatas()['id'];
            $datas = $this->model->getOne($id); // On récupère la donnée en BDD
            if (empty($datas)) {
                //si la donnée n'existe pas
                header("HTTP/1.0 404 Not Found");
                return '';
            }
        } else {
            //on récupère toutes les données
            $datas = $this->model->getAll();
        }
        return json_encode($datas);
    }

    private function addData(): string
    {
        //On verifie que toutes les données nécessaires sont présentes et on ajoute
        $error = false;
        $datas = $this->request->getAllDatas();
        //Si il manque des données 
        if ($error) {
            header("HTTP/1.0 422 Unprocessable Entity");
            return '';
        } else {
            //On ajoute la donnée
            $this->model->add($datas);
            if (!$this->model) {
                header("HTTP/1.0 500 Internal Server Error");
                return '';
            }
            header("HTTP/1.0 201 Created");
            return '';
        }
    }

    private function changeData(): string
    {
        //On verifie que toutes les données nécessaires sont présentes et on modifie
        $error = false;
        $datas = $this->request->getAllDatas();
        //Si il manque des données 
        if ($error) {
            header("HTTP/1.0 422 Unprocessable Entity");
            return '';
        } else {
            //On modifie la donnée
            $this->model->update((int)$datas['id'], $datas);
            if (!$this->model) {
                header("HTTP/1.0 500 Internal Server Error");
                return '';
            }
            header("HTTP/1.0 200 OK");
            return '';
        }
    }
    private function deleteData(): string
    {
        if (key_exists('id', $this->request->getAllDatas())) {
            //on supprime une donnée en particulier
            $id = (int) $this->request->getAllDatas()['id'];
            $this->model->delete($id);
            if (!$this->model) {
                header("HTTP/1.0 500 Internal Server Error");
                return '';
            }
            header("HTTP/1.0 200 OK");
        } else {
            header("HTTP/1.0 404 Not Found");
        }
        return '';
    }
}
