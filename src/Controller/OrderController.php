<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Reservation;
use App\Service\EcomToolbox;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Security('is_granted("ROLE_USER")')]
#[Route('/dashboard')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'order_dashboard')]
    public function orderDashboard(ManagerRegistry $doctrine): Response
    {
        // Cette route nous présente les différentes commandes que nous avons passées au sein de l'application web Symfony Ecommerce.

        // Nous récupérons l'Utilsateur :
        $user = $this->getUser();

        // Pour pouvoir dialoguer avec la base de données et récupérer nos commandes, nous avons besoin de l'Entity Manager ainsi que du Repository de Order :
        $entityManager = $doctrine->getManager();
        $orderRepository = $entityManager->getRepository(Order::class);

        // On récupère notre commande active et la liste de nos commandes archivées :
        $activeOrder = $orderRepository->findOneBy(['status' => 'panier', 'user' => $user], ['id' => 'DESC']);
        $archivedOrders = $orderRepository->findBy(['status' => 'validée', 'user' => $user], ['id' => 'DESC']);
        // dd($activeOrder);
        return $this->render('order/dashboard.html.twig', [
            'activeOrder' => $activeOrder,
            'archivedOrders' => $archivedOrders,
        ]);
    }

    // -----------------------------------------------------------------------------------------------------------

    #[Route('order/validate', name: 'order_validate')]
    public function validateOrder(ManagerRegistry $doctrine, EcomToolbox $toolbox): Response
    {
        // Cette route récupère la commande en mode Panier et la place en mode Validée, à travers le changement de la valeur de l'attribut $status de la commande (Order) en question, SEULEMENT si nous sommes connectés avec le compte User lié à la commande.

        // Nous récupérons l'Utilisatur actuel pour effectuer ce contrôle :
        $user = $this->getUser();

        // Afin de récupérer notre commande, nous avons besoin du ManagerRegistry pour l'Entity Manager, ainsi que du Repository de Order :
        $entityManager = $doctrine->getManager();
        $orderRepository = $entityManager->getRepository(Order::class);
        // Nous récupérons la commande en mode Panier. Si celle-ci n'existe pas, nous retournons à notre tableau de bord :
        $order = $orderRepository->findOneBy(['status' => 'panier'], ['id' => 'DESC']);
        if (!$order || ($user != $order->getUser())) {
            if ($order && $user != $order->getUser()) {
                // Création du flashbag :
                $toolbox->generateFlashbag(
                    [
                        'Impossible de valider cette commande.',
                    ],
                    'Commande',
                    'red'
                );
            }
            return $this->redirectToRoute('order_dashboard');
        }
        // On change le statut de notre commande en "Validée" avant de revenir à notre tableau de bord :
        $order->setStatus('validée');
        $entityManager->persist($order);
        $entityManager->flush();
        // Création du flashbag :
        $toolbox->generateFlashbag(
            [
                'Votre commande a bien été validée.',
            ],
            'Commande',
            'green'
        );
        // Nous retournons au tableau de bord :
        return $this->redirectToRoute('order_dashboard');
    }

    // -----------------------------------------------------------------------------------------------------------

    #[Route('/order/delete/{orderId}', name: 'order_delete')]
    public function deleteOrder(ManagerRegistry $doctrine, int $orderId, EcomToolbox $toolbox): Response
    {
        // Cette méthode supprime une commande (Order) ainsi que toutes les Reservations qui lui sont liées.

        // Nous récupérons l'Utilisateur actuel pour le controle :
        $user = $this->getUser();

        // On récupère l'Entity Manager ainsi que le Repository de Order :
        $entityManager = $doctrine->getManager();
        $orderRepository = $entityManager->getRepository(Order::class);
        // On récupère la commande grâce à la méthode find() de Order, mais si cette entrée de table n'est pas trouvée, on revient au tableau de bord :
        $order = $orderRepository->find($orderId);
        if (!$order || ($order->getUser() != $user)) {
            if ($order && $user != $order->getUser()) {
                // Création du flashbag :
                $toolbox->generateFlashbag(
                    [
                        'Impossible de supprimer cette commande.',
                    ],
                    'Commande',
                    'red'
                );
            }
            return $this->redirectToRoute('order_dashboard');
        }

        // Si notre commande est active (en mode panier), nous supprimons chacune de ses Reservations :
        if ($order->getStatus() == 'panier') {
            foreach ($order->getReservations() as $reservation) {
                $product = $reservation->getProduct();
                $product->setStock($reservation->getQuantity() + $product->getStock());
                $entityManager->persist($product);
                // Nous supprimons la Reservation :
                $entityManager->remove($reservation);
            }
        }
        // Une fois que toutes nos Reservations ont reçu une requête de suppression, nous pouvons demander à supprimer notre commande :
        $entityManager->remove($order);
        $entityManager->flush();
        // Création du flashbag :
        $toolbox->generateFlashbag(
            [
                'Votre commande a bien été supprimée.',
            ],
            'Commande',
            'yellow'
        );
        // On repart sur le tableau de bord :
        return $this->redirectToRoute('order_dashboard');
    }

    // -----------------------------------------------------------------------------------------------------------

    #[Route('/reservation/delete/{reservationId}', name: 'reservation_delete')]
    public function deleteReservation(ManagerRegistry $doctrine, int $reservationId, EcomToolbox $toolbox): Response
    {
        // Cette méthode permet la suppression d'une Reservation (d'une commande en cours) dont nous avons renseigné l'Id via le paramètre de route, SEULEMENT si nous sommes l'Utilsateur associé à la commande.

        // On récupère le User actuel :
        $user = $this->getUser();

        // On récupère l'Entity Manager ainsi que le Repository qui nous intéresse :
        $entityManager = $doctrine->getManager();
        $reservationRepository = $entityManager->getRepository(Reservation::class);
        // On récupère la Reservation dont l'ID nous est indiqué dans l'URL. Si cette Reservation n'existe pas, nous retournons au tableau de bord :
        $reservation = $reservationRepository->find($reservationId);
        if (!$reservation || ($reservation->getClientOrder()->getUser()) != $user) {
            if ($reservation && ($reservation->getClientOrder()->getUser() != $user)) {
                // Création du flashbag :
                $toolbox->generateFlashbag(
                    [
                        'Impossible de supprimer ce produit.',
                    ],
                    'Commande',
                    'red'
                );
            }
            return $this->redirectToRoute('order_dashboard');
        }

        // Une fois notre élément récupéré, nous procédons à sa suppression (si la commande est en cours).
        // Le premier élément du if est une sécurité permettant de s'assurer que getClientOrder() ne rende pas "null", si cette première condition est invalidée, la seconde n'est pas vérifiée :
        if ($reservation->getClientOrder() && $reservation->getClientOrder()->getStatus() == 'panier') {
            // On récupère le Product afin de lui restituer la quantité empruntée :
            $product = $reservation->getProduct();
            $product->setStock($reservation->getQuantity() + $product->getStock());
            $entityManager->persist($product);
            // Nous vérifions si notre Reservation est bien le dernier élément de notre commande (Order) :
            $order = $reservation->getClientOrder();
            $order->removeReservation($reservation);
            // Si le tableau de $reservations de Order est vide, ceci signifie que Order n'a plus de Reservation et que nous pouvons supprimer cet objet :
            // toArray() transform une Collection Symfony en tableau PHP :
            if (!$order->getReservations()->toArray()) {
                $entityManager->remove($order);
            }
            // Suppression de le Reservation :
            $entityManager->remove($reservation);
            $entityManager->flush();
        }
        // Création du flashbag :
        $toolbox->generateFlashbag(
            [
                'Votre produit a bien été supprimé.',
            ],
            'Commande',
            'yellow'
        );
        // On retourne vers notre tableau de bord :
        return $this->redirectToRoute('order_dashboard');
    }

    // -----------------------------------------------------------------------------------------------------------
}
