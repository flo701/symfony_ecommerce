<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\Product;
use App\Entity\Category;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(ManagerRegistry $doctrine): Response
    {
        // Afficher la liste de tous nos produits.

        // Afin de pouvoir récupérer les Products de notre BDD, nous avons besoin de l'Entity Manager ainsi que du Repository de Product :
        $entityManager = $doctrine->getManager();
        $productRepository = $entityManager->getRepository(Product::class);
        // On récupère également le Repository de Category :
        $categoryRepository = $entityManager->getRepository(Category::class);
        // On récupère la liste des Categories :
        $categories = $categoryRepository->findAll();

        // On récupère les Products pour notre page d'accueil.
        // On utilise findBy() pour récupérer tous les éléments du plus récent au plus ancien : contrairement à findAll(), findBy() nous permet d'indiquer des critères et un ordre de résultat :
        // findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
        $products = $productRepository->findBy([], ['id' => 'DESC']);

        shuffle($products); // On change l'ordre des différents products à chaque rechargement de page (on mélange l'ordre du tableau).
        array_splice($products, 15); // 15 éléments maximun seront affichés (5 rangées).

        // On envoie nos products vers la page index.html.twig :
        return $this->render('index/index.html.twig', [
            'categories' => $categories,
            'products' => $products,
        ]);
    }

    // -----------------------------------------------------------------------------------------------------------

    #[Route('/category/{categoryName}', name: 'index_category')]
    public function indexCategory(ManagerRegistry $doctrine, string $categoryName): Response
    {
        // Cette méthode affiche tous les Products liés à la Catégory renseignée via l'URL.

        // Nous avons besoin de l'Entity Manager, ainsi que du Repository de Category :
        $entityManager = $doctrine->getManager();
        $categoryRepository = $entityManager->getRepository(Category::class);

        // On récupère la liste des categories :
        $categories = $categoryRepository->findAll();

        // Via le Repository, nous recherchons la Category qui nous intéresse. Si celle-ci n'existe pas, nous retournons à l'index :
        $category = $categoryRepository->findOneBy(['name' => $categoryName]);
        if (!$category) {
            return $this->redirectToRoute('app_index');
        }

        // Maintenant que nous avons notre Category, nous récupérons les Products qui lui sont associés :
        $products = $category->getProducts(); // Méthode de Entity/Category.php

        // Nous redéfinissons la page d'accueil grâce à notre tableau associatif pageTheme :
        $pageTheme = [
            'title' => $category->getName(), // Méthode de Entity/Category.php
            'description' => $category->getDescription(), // Méthode de Entity/Category.php
        ];

        // Nous renvoyons les Products à notre page index.html.twig :
        return $this->render('index/index.html.twig', [
            'pageTheme' => $pageTheme,
            'categories' => $categories,
            'products' => $products,
        ]);
    }

    // -----------------------------------------------------------------------------------------------------------

    #[Route('/tag/{tagId}', name: 'index_tag')]
    public function indexTag(int $tagId, ManagerRegistry $doctrine): Response
    {
        // Cette méthode publie la liste des Products liés à un Tag donné dont l'ID nous a été renseigné via l'URL.

        // On récupère l'Entity Manager ainsi que le Repository de Tag :
        $entityManager = $doctrine->getManager();
        $tagRepository = $entityManager->getRepository(Tag::class);

        // On récupère la liste des categories :
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();

        // On récupère le Tag auquel nous avons fait référence, s'il n'existe pas, on retourne à l'index :
        $tag = $tagRepository->find($tagId);
        if (!$tag) {
            return $this->redirectToRoute('app_index');
        }

        // Puisque nous avons le Tag, nous récupérons la liste des Products liés à ce dernier :
        $products = $tag->getProducts();

        // Nous redéfinissons la page d'accueil grâce à notre tableau associatif pageTheme :
        $pageTheme = [
            'title' => $tag->getName(),
            'description' => "Ceci est la liste des Produits liés à ce Tag.",
        ];

        // On transmet la liste de nos Products liés à notre page Twig :
        return $this->render('index/index.html.twig', ['pageTheme' => $pageTheme, 'categories' => $categories, 'products' => $products,]);
    }

    // -----------------------------------------------------------------------------------------------------------

    #[Route('/product/display/{productId}', name: 'product_display')]
    public function displayProduct(ManagerRegistry $doctrine, int $productId): Response
    {
        // Cette route nous affiche un product donné ainsi que ses caractéristiques selon l'ID du product qui nous a été transmis via le paramètre de route.

        // Afin de pouvoir récupérer notre Product, nous avons besoin de l'Entity Manager ainsi que du Repository de Product :
        $entityManager = $doctrine->getManager();
        $productRepository = $entityManager->getRepository(Product::class);

        // On récupère la liste des Categories (juste pour pouvoir afficher la liste déroulante du header)
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();

        // On recherche le Product via son ID. S'il n'est pas trouvé, nous retournons à l'index :
        $product = $productRepository->find($productId);
        if (!$product) {
            return $this->redirectToRoute('app_index');
        }

        // dd($product->getCategory()->getName()); // On obtient "Canape" (par ex)

        // Si nous avons notre Product, nous l'envoyons sur la page Twig dédiée :
        return $this->render('index/product_display.html.twig', ['categories' => $categories, 'product' => $product,]);
    }

    // -----------------------------------------------------------------------------------------------------------

    #[Route('/product/buy/{productId}', name: 'product_buy')]
    public function buyProduct(ManagerRegistry $doctrine, int $productId)
    {
        // Cette méthode simule un processus d'achat en retranchant de la valeur au stock d'un Product dont l'ID nous est communiqué dans l'URL.

        // On a besoin de l'Entity Manager et du Repository de Product afin de récupérer notre Product :
        $entityManager = $doctrine->getManager();
        $productRepository = $entityManager->getRepository(Product::class);

        // Nous recherchons le Product afin de le manipuler. S'il n'existe pas, nous retournons à l'index :
        $product = $productRepository->find($productId);
        if (!$product) {
            return $this->redirectToRoute('app_index');
        }

        // dd($product);
        // dd($product->getStock()); 

        // Si le Product existe ET que son stock est supérieur à 0, il faudra donc modifier son stock et faire en sorte que cette modification persiste dans la base de données :
        if ($product->getStock() > 0) {
            $product->setStock($product->getStock() - 1);
            // On persiste la nouvelle valeur de notre Product :
            $entityManager->persist($product);
            $entityManager->flush();
        }
        // On retourne vers la fiche produit concernée :
        return $this->redirectToRoute('product_display', ['productId' => $product->getId()]);
    }

    // -----------------------------------------------------------------------------------------------------------

}
