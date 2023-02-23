<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class EcomToolbox
{
    // Notre classe Service, boîte à outils pour notre app Symfony eCommerce.

    private $request;

    public function __construct(RequestStack $requestStack)
    {
        // Nous récupérons l'instance de la classe RequestStack afin de pouvoir instancier l'objet Request dans notre classe Service.
        // Pour récupérer l'objet Request, nous devons utiliser la méthode getCurrentRequest() :
        $this->request = $requestStack->getCurrentRequest();
    }

    public function generateFlashbag(array $messages, string $title = "", string $status = ""): void
    {
        // Cette méthode aura pour but de préparer des Flashbags selon les éléments passés en paramètres :

        // Tout d'abord, on récupère l'élément Request :
        $request = $this->request;
        // On active le Panel :
        $request->getSession()->set('infopanel', true);
        // Nous renseignons notre flasbag avec les messages passés en paramètre :
        foreach ($messages as $message) {
            $request->getSession()->getFlashbag()->add('info', $message);
        }
        // Si des informations supplémentaires concernant les variables de session sont passées via les paramètres de notre méthode, nous les indiquons :
        if ($title) {
            $request->getSession()->set('message_title', $title);
        }
        if ($status) {
            $request->getSession()->set('status', $status);
        }
    }

    public function tellTime(): \DateTime
    {
        // Cette méthode retourne l'heure actuelle sous la forme d'un objet DateTime :
        return new \DateTime("now");
    }
}
