<?php

namespace App\Service;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class EcomPagination
{
    // Cette classe va nous permettre de créer un système de pagination relatif à une Entity désignée.

    private $manager;
    private $request;

    private ?int $pageNumber;
    private ?int $pageRange;
    private ?int $barRange;
    private ?string $introPath;

    public function __construct(EntityManagerInterface $manager, RequestStack $requestStack)
    {
        // On récupère l'Entity Manager :
        $this->manager = $manager;
        $this->request = $requestStack->getCurrentRequest();

        // On initialise nos attributs avec pour valeur null :
        $this->pageNumber = null;
        $this->introPath = null;
        $this->pageRange = null;
        $this->barRange = null;
    }

    public function generatePagination(int $pageNumber = 1, string $introPath = "", int $pageRange = 15, int $barRange = 7): self
    {
        // On prépare les valeurs par défaut de notre pagination :
        $this->pageNumber = $pageNumber;
        $this->introPath = $introPath;
        $this->pageRange = $pageRange;
        $this->barRange = $barRange;

        return $this;
    }

    public function getPageRange(int $pageNumber = 1, int $range = 15): ?array
    {
        // On obtient grâce à cette méthode la plage de Product indiquée selon la capacité de chaque page et son nombre indiqué.
        // Si des valeurs ont été préparées, nous les utilisons :
        if ($this->pageNumber) {
            $pageNumber = $this->pageNumber;
        }
        if ($this->pageRange) {
            $range = $this->pageRange;
        }

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

    public function getMaxPages(int $range = 15): ?int
    {
        // On obtient grâce à cette méthode la plage de Product indiquée selon la capacité de chaque page et son nombre indiqué.

        // Si des valeurs ont été préparées, nous les utilisons :
        if ($this->pageRange) {
            $range = $this->pageRange;
        }

        // On récupère l'Entity Manager :
        $entityManager = $this->manager;
        // On récupère le Repository de Product :
        $productRepository = $entityManager->getRepository(Product::class);

        // Nous récupérons le nombre maximal de pages pour notre série de Product, lequel est calculé en arrondissant par excès le quotient de notre nombre d'éléments de table divisé par 15 (le nombre d'éléments que nous voulons afficher par page) :
        $maxPagesProduct = ceil($productRepository->countSize() / $range);

        // On retourne une (ou plusieurs) informations :
        return $maxPagesProduct;
    }

    public function generatePaginationBar(int $pageNumber = 1, int $range = 7, string $introPath = ""): ?string
    {
        $request = $this->request;

        // Si des valeurs ont été préparées, nous les utilisons :
        if ($this->pageNumber) {
            $pageNumber = $this->pageNumber;
        }
        if ($this->barRange) {
            $range = $this->barRange;
        }
        if ($this->introPath) {
            $introPath = $this->introPath;
        }

        // On récupère notre chemin de route et nous préparons le premier élément de nos liens hypertextes pour chaque bouton :
        $path = $request->getPathInfo();
        // dd($path);

        if (str_contains($path, 'page/')) {
            $trimmedPath = explode("page/", $path);
            // dd($trimmedPath);
            $trimmedPath = $trimmedPath[0]; // On récupère le segment du path précédent "page/"
        } else $trimmedPath = $path . $introPath; // introPath correspond à un élément possible situé entre le path actuel et le segment "/pages"

        // On prépare le nombre de boutons déterminé par $range :
        $buttons = '';
        $startRange = $pageNumber - floor($range / 2); // valeur de départ
        for ($i = $startRange; $i < ($range + $startRange); $i++) {
            if ($i > 0 && $i <= $this->getMaxPages(15)) {
                if ($i == $pageNumber) {
                    $buttons .= '<a href="' . $path . '" class="w3-button w3-green">' . $i . '</a>'; // bouton actif (vert)
                } else {
                    $buttons .= '<a href="' . $trimmedPath . 'page/' . $i . '" class="w3-button">' . $i . '</a>';
                }
            }
        }

        // Bouton précédent :
        if ($pageNumber > 1) {
            $previousButton = '<a href="
            ' . $trimmedPath . 'page/' . ($pageNumber - 1) . '
            " class="w3-bar-item w3-button">&laquo;</a>';
        } else $previousButton = "";

        // Bouton suivant :
        if ($pageNumber < $this->getMaxPages(15)) {
            $nextButton = '<a href="
            ' . $trimmedPath . 'page/' . ($pageNumber + 1) . '
            " class="w3-button">&raquo;</a>';
        } else $nextButton = '';

        $bar = '<!-- Pagination -->
        <div class="w3-center">
          <div class="w3-bar w3-border w3-round">
                ' . $previousButton . $buttons . $nextButton . '
          </div>
        </div> ';

        return $bar;
    }
}
