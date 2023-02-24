<?php

namespace App\Service;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

class EcomPagination
{
    // Cette classe va nous permettre de créer un système de pagination relatif à une Entity désignée.

    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        // On récupère l'Entity Manager :
        $this->manager = $manager;
    }

    public function getPageRange(int $pageNumber, int $range): ?array
    {
        // On obtient grâce à cette méthode la plage de Product indiquée selon la capacité de chaque page et son nombre indiqué.

        // On récupère l'Entity Manager :
        $entityManager = $this->manager;
        // On récupère le Repository de Product :
        $productRepository = $entityManager->getRepository(Product::class);

        // On prépare les Products à récupérer.
        // Les paramètres de findBy sont les critères, order, limite d'éléments à récupérer, et offset (décalage) :
        $products = $productRepository->findBy([], ['name' => 'ASC'], $range, $range * ($pageNumber - 1));

        // On renvoie le tableau de Product récupéré :
        return $products;
    }

    public function getMaxPages(int $range): ?int
    {
        // On obtient grâce à cette méthode la plage de Product indiquée selon la capacité de chaque page et son nombre indiqué.

        // On récupère l'Entity Manager :
        $entityManager = $this->manager;
        // On récupère le Repository de Product :
        $productRepository = $entityManager->getRepository(Product::class);

        // Nous récupérons le nombre maximal de pages pour notre série de Product, lequel est calculé en arrondissant par excès le quotient de notre nombre d'éléments de table divisé par 15 (le nombre d'éléments que nous voulons afficher par page) :
        $maxPagesProduct = ceil($productRepository->countSize() / $range);

        // On retourne une (ou plusieurs) informations :
        return $maxPagesProduct;
    }

    public function generatePaginationBar(int $pageNumber, int $range): ?string
    {
        // On prépare le nombre de boutons déterminé par $range :
        $buttons = '';
        for ($i = $pageNumber; $i < ($range + $pageNumber); $i++) {
            if ($i == $pageNumber) {
                $buttons .= '<a href="products/page/' . $i . '" class="w3-button w3-green">' . $i . '</a>';
            } else {
                $buttons .= '<a href="products/page/' . $i . '" class="w3-button">' . $i . '</a>';
            }
        }

        $bar = '<!-- Pagination -->
        <div class="w3-center">
          <div class="w3-bar w3-border w3-round">
              <a href="#" class="w3-bar-item w3-button">&laquo;</a>'
            . $buttons .
            ' <a href="#" class="w3-button">&raquo;</a>
          </div>
        </div> ';

        return $bar;
    }
}
