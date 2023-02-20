<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/order')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'order_dashboard')]
    public function orderDashboard(ManagerRegistry $doctrine): Response
    {
        // Cette route nous présente les différentes commandes que nous avons passées au sein de l'application web Symfony Ecommerce.

        // Pour pouvoir dialoguer avec la base de données et récupérer nos commandes, nous avons besoin de l'Entity Manager ainsi que du Repository de Order :
        $entityManager = $doctrine->getManager();
        $orderRepository = $entityManager->getRepository(Order::class);

        // On récupère la liste de nos commandes :
        $orders = $orderRepository->findAll();

        return $this->render('order/dashboard.html.twig', [
            'orders' => $orders,
        ]);
    }

    // -----------------------------------------------------------------------------------------------------------
}
