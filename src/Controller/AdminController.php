<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Category;
use App\Form\ProductType;
use App\Entity\ProductImg;
use App\Entity\Reservation;
use App\Form\ProductImgType;
use App\Service\EcomPagination;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Security('is_granted("ROLE_ADMIN")')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_backoffice')]
    public function adminBackoffice(ManagerRegistry $doctrine, EcomPagination $pagination): Response
    {
        // Cette page affiche la liste des Products et des Tags avec la possibilité de les créer, de les modifier, et de les supprimer, totalisant ainsi les quatre fonctions du CRUD.

        // On récupère l'Entity Manager, et les Repositories de Product et Tag :
        $entityManager = $doctrine->getManager();
        $tagRepository = $entityManager->getRepository(Tag::class);

        // On récupère la liste de nos Tags :
        $tags = $tagRepository->findBy([], ['id' => 'DESC']);

        // On transmet nos Products et Tags à notre backoffice :
        return $this->render('admin/admin_backoffice.html.twig', [
            'pagination' => $pagination->generatePagination(1, 'products/'),
            'tags' => $tags,
        ]);
    }

    // -----------------------------------------------------------------------------------------------------

    #[Route('/products/page/{pageNumber}', name: 'admin_backoffice_page')]
    public function adminBackofficePages(ManagerRegistry $doctrine, EcomPagination $pagination, int $pageNumber): Response
    {
        // Cette page affiche la liste des Products et des Tags avec la possibilité de les créer, de les modifier, et de les supprimer, totalisant ainsi les quatre fonctions du CRUD.

        // On récupère l'Entity Manager, et les Repository de Product et Tag :
        $entityManager = $doctrine->getManager();
        $tagRepository = $entityManager->getRepository(Tag::class);
        // Si le numéro de page est égal ou inférieur à zéro, nous renvoyons l'utilisateur vers le Backoffice :
        $maxPagesProduct = $pagination->getMaxPages(15);
        //dd($maxPagesProduct);
        if ($pageNumber > $maxPagesProduct) {
            return $this->redirectToRoute('admin_backoffice_page', ['pageNumber' => $maxPagesProduct]);
        } else if ($pageNumber <= 0) {
            return $this->redirectToRoute('admin_backoffice');
        }
        // On récupère la liste de nos Tags :
        $tags = $tagRepository->findBy([], ['id' => 'DESC']);

        // On transmet notre Pagination de Products et nos Tags à notre backoffice :
        return $this->render('admin/admin_backoffice.html.twig', [
            'pagination' => $pagination->generatePagination($pageNumber, 'products/'),
            'tags' => $tags,
        ]);
    }

    // -----------------------------------------------------------------------------------------------------

    #[Route('/dashboard', name: 'order_dashboard_admin')]
    public function orderDashboard(ManagerRegistry $doctrine): Response
    {
        // Cette route nous présente les différentes commandes passées au sein de l'application web Symfony Ecommerce.

        // Pour pouvoir dialoguer avec la base de données et récupérer nos commandes, nous avons besoin de l'Entity Manager ainsi que du Repository de Order :
        $entityManager = $doctrine->getManager();
        $orderRepository = $entityManager->getRepository(Order::class);

        // On récupère notre commande active et la liste de nos commandes archivées :
        $activeOrders = $orderRepository->findBy(['status' => 'panier'], ['id' => 'DESC']);
        $archivedOrders = $orderRepository->findBy(['status' => 'validée'], ['id' => 'DESC']);
        // dd($archivedOrders);
        return $this->render('admin/dashboard.html.twig', [
            'activeOrders' => $activeOrders,
            'archivedOrders' => $archivedOrders,
        ]);
    }

    // -----------------------------------------------------------------------------------------------------------

    #[Route('/catalog/reload', name: 'admin_reload')]
    public function reloadCatalog(ManagerRegistry $doctrine): Response
    {
        // Cette méthode purge les trois tables Product, Tag et Category de notre base de données avant de les remplir avec une nouvelle liste de Category, de Tag et de Product factices.

        // Afin de pouvoir dialoguer avec notre bdd, nous avons besoin de l'Entity Manager :
        $entityManager = $doctrine->getManager();
        // "Purger nos tables P/T/C"-> "Ces tables ne doivent avoir aucun contenu" => "Ces tables doivent avoir leur contenu supprimé" => "Il faut récupérer tout le contenu de ces tables et les supprimer"

        // On récupère les Repositories pour récupérer le contenu à supprimer des tables :
        $productRepository = $entityManager->getRepository(Product::class);
        $categoryRepository = $entityManager->getRepository(Category::class);
        $tagRepository = $entityManager->getRepository(Tag::class);

        // On récupère également les Repositories de Order et de Reservation :
        $reservationRepository = $entityManager->getRepository(Reservation::class);
        $orderRepository = $entityManager->getRepository(Order::class);

        // On récupère tout le contenu de ces tables :
        $products = $productRepository->findAll();
        $categories = $categoryRepository->findAll();
        $tags = $tagRepository->findAll();
        $reservations = $reservationRepository->findAll();
        $orders = $orderRepository->findAll();

        // Pour chaque élément récupéré, nous plaçons une demande de remove() via l'EntityManager dans une boucle :
        foreach ($products as $product) {
            $entityManager->remove($product);
        }
        foreach ($categories as $category) {
            $entityManager->remove($category); // Les éléments OneToMany ne doivent être liés à aucun élément pour être supprimés sans erreur de contrainte de clef étrangère
        }
        foreach ($tags as $tag) {
            $entityManager->remove($tag);
        }
        foreach ($reservations as $reservation) {
            $entityManager->remove($reservation);
        }
        foreach ($orders as $order) {
            $entityManager->remove($order);
        }
        $entityManager->flush(); // On applique les demandes de suppression

        // On copie/colle la fonction load() de ProductFixtures (ne pas oublier de renommer $manager en $entityManager) :

        // On commence par créer nos différentes Categories, lesquelles seront utilisées pour classifier nos différents Products :

        // Un lorem à utiliser :
        $lorem = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip
        ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum consectetur
        adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.";

        // La liste de nos différentes catégories sous la forme d'un tableau associatif, contenant une indication du type de catégorie sous la clef et l'objet Category en valeur. Etant donné que nous allons instancier nos Categories juste après dans une boucle, la valeur actuelle de ces différentes clefs est null :
        $categoryArray = [
            'chaise' => null,
            'bureau' => null,
            'lit' => null,
            'canape' => null,
            'armoire' => null,
            'autre' => null,
        ];

        // On prépare l'instanciation de nos catégories, leur persistance et leur placement au sein de notre tableau $categoryArray :
        foreach ($categoryArray as $key => &$value) {
            // Le & avant $value est un passage en référence, ce qui signifie que nous récupérons la variable en tant que telle plutôt que sa valeur, ce qui nous permet de modifier notre tableau $categoryArray plutôt qu'une copie de $value, qui sera perdue après la boucle :
            $value = new Category; // A chaque valeur est attribué un objet Category
            $value->setName(ucfirst($key)); // Le nom est la clef capitalisée de l'entrée actuellement itérée du tableau
            $value->setDescription($lorem); // La description est le lorem ipsum que nous avions préparé
            $entityManager->persist($value); // Demande de persistance de notre nouvelle Category
        }

        // Nous allons préparer une collection de Tags :
        $tagNames = ["Bois", "Nouveau", "Promotion", "Mobilier", "Bon marché", "Occasion", "Design", "Pas cher", "Synthétique", "Au top"];
        $tags = []; // Tableau qui conservera les Tags
        foreach ($tagNames as $tagName) {
            // On crée une boucle foreach, laquelle va parcourir le tableau de tagNames, et utiliser chaque chaîne de caractères pour initialiser et renseigner un nouveau Tag, enregistré dans le tableau $tags :
            $tag = new Tag;
            $tag->setName($tagName);
            array_push($tags, $tag);
            $entityManager->persist($tag);
        }

        // Boucle de création de Products.
        // On crée une liste de catégories potentielles :
        $categories = ['chaise', 'bureau', 'lit', 'canape', 'armoire', 'autre'];
        for ($i = 0; $i < 150; $i++) {
            // On sélectionne un nom de catégorie au hasard qui servira à nommer le Product et à déterminer la clef que nous sélectionnons dans $categoryArray :
            $selectedCategory = $categories[rand(0, (count($categories) - 1))];
            $product = new Product;
            $product->setName(ucfirst($selectedCategory) . " #" . rand(1000, 9999));
            $product->setStock(rand(0, 200));
            $product->setPrice(rand(0, 200));
            $product->setDescription($lorem);
            // Nous récupérons l'objet de categoryArray tenu par la clef dont le nom est fourni par la valeur de $selectedCategory :
            $product->setCategory($categoryArray[$selectedCategory]);
            foreach ($tags as $tag) {
                if (rand(1, 10) > 8) { // 20% de chance que la condition soit remplie
                    $product->addTag($tag); // On lie le Product au Tag actuellement parcouru
                }
            }
            $entityManager->persist($product);
        }
        $entityManager->flush();

        // Une fois tout effectué, nous retournons au backoffice :
        return $this->redirectToRoute('admin_backoffice');
    }

    // -----------------------------------------------------------------------------------------------------------

    #[Route('/product/create', name: 'product_create')]
    public function createProduct(Request $request, ManagerRegistry $doctrine): Response
    {
        // Cette méthode nous permet de créer un Product grâce à un formulaire externalisé (dans Form/ProductType).

        // On commence par récupérer l'Entity Manager :
        $entityManager = $doctrine->getManager();

        // On crée un nouvel object Product que nous lions à notre formulaire ProductType :
        $product = new Product;
        $productForm = $this->createForm(ProductType::class, $product);

        // On applique l'objet Request sur notre formulaire :
        $productForm->handleRequest($request);

        // On vérifie si notre formulaire est rempli et valide, si oui, nous persistons le Product lié, qui est synchronisé avec notre formulaire :
        if ($productForm->isSubmitted() && $productForm->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();
            return $this->redirectToRoute('admin_backoffice');
        }
        // Si le formulaire n'est pas rempli, nous renvoyons l'utilisateur vers ce dernier :
        return $this->render('index/dataform.html.twig', [
            'formName' => 'Création de Produit',
            'dataForm' => $productForm->createView(),
        ]);
        // On génère la vue de notre formulaire via la méthode $productForm->createView() et dans le fichier Twig qui affichera le formulaire, on indique {{ form(dataForm) }}.
    }

    // -----------------------------------------------------------------------------------------------------------

    #[Route('/product/update{productId}', name: 'product_update')]
    public function updateProduct(ManagerRegistry $doctrine, Request $request, int $productId): Response
    {
        // Cette route nous permet de modifier les valeurs d'un Product déjà persisté dans notre base de données, ce Product nous est renseigné via son ID dans le paramètre de route.

        // On récupère l'Entity Manager ainsi que le Repository de Product :
        $entityManager = $doctrine->getManager();
        $productRepository = $entityManager->getRepository(Product::class);

        // Nous recherchons le produit dont l'ID nous a été fourni. Si celui-ci n'existe pas, nous retournons au Backoffice :
        $product = $productRepository->find($productId);
        if (!$product) {
            return $this->redirectToRoute('admin_backoffice');
        }

        // Une fois le Product récupéré, nous l'attachons à un formulaire ProductType que nous créons :
        $productForm = $this->createForm(ProductType::class, $product);

        // On applique la méthode handleRequest sur notre formulaire :
        $productForm->handleRequest($request);

        // Si le formulaire est valide et rempli, nous persistons son Product lié :
        if ($productForm->isSubmitted() && $productForm->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();
            return $this->redirectToRoute('admin_backoffice');
        }
        // Si le formulaire n'est pas rempli, nous le présentons :
        return $this->render('index/dataform.html.twig', [
            'formName' => 'Modification de Produit',
            'dataForm' => $productForm->createView(),
        ]);
    }

    // -----------------------------------------------------------------------------------------------------------

    #[Route('/product/delete{productId}', name: 'product_delete')]
    public function deleteProduct(ManagerRegistry $doctrine, int $productId): Response
    {
        // Cette route permet la suppression d'un Product dont l'ID est renseigné par notre paramètre de route.

        // On récupère l'Entity Manager et le Repository de Product :
        $entityManager = $doctrine->getManager();
        $productRepository = $entityManager->getRepository(Product::class);

        // Nous recherchons le Product dont l'ID nous a été fourni. S'il n'existe pas, nous retournons sur le Backoffice :
        $product = $productRepository->find($productId);
        if (!$product) {
            return $this->redirectToRoute('admin_backoffice');
        }

        // Si le produit existe, nous procédons à sa suppression, et nous retournons au backoffice :
        $entityManager->remove($product);
        $entityManager->flush();
        return $this->redirectToRoute('admin_backoffice');
    }

    // -----------------------------------------------------------------------------------------------------------

    #[Route('/productimg/create', name: 'productimg_create')]
    public function createProductImg(Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger): Response
    {
        // Cette méthode a pour objectif de mettre en ligne une nouvelle image qui pourra être utilisée pour illustrer nos Products.

        // On récupère l'Entity Manager :
        $entityManager = $doctrine->getManager();

        // On crée une nouvelle Entity ProductImg liée à notre nouveau formulaire :
        $productImg = new ProductImg;
        $imgForm = $this->createForm(ProductImgType::class, $productImg);
        // Nous gérons la requête et mettons en ligne l'Entity si le formulaire est validé :
        $imgForm->handleRequest($request);

        if ($imgForm->isSubmitted() && $imgForm->isValid()) {
            // Nous récupérons la valeur du champ de l'image :
            $imgFile = $imgForm->get('imagefile')->getData();
            // Si ce champ "image file" est vide, il est inutile de le mettre en ligne. Sinon :
            if ($imgFile) {
                $originalFilename = pathinfo($imgFile->getClientOriginalName(), PATHINFO_FILENAME);
                // Nous préparons le nom de fichier de manière à l'intégrer sans risque dans une URL. Nous devinons également l'extension automatiquement pour des raisons de sécurité :
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imgFile->guessExtension();

                // On déplace le fichier :
                try {
                    $imgFile->move(
                        $this->getParameter('img_directory'),
                        $newFilename,
                    );
                    $productImg->setFilename($newFilename);
                    $productImg->setFileAddress('assets/img/upload/' . $newFilename);
                } catch (FileException $e) {
                    // On capture l'erreur si un problème survient :
                    $productImg->setFilename("non défini");
                    $productImg->setFileAddress("non définie");
                }
            }
            // Persistance de ProductImg :
            $productImg->setCreationDate(new \DateTime("now"));
            $entityManager->persist($productImg);
            $entityManager->flush();
            return $this->redirectToRoute('admin_backoffice');
        }
        // Si le formulaire n'est pas rempli, nous le présentons à l'utilisateur :
        return $this->render('index/dataform.html.twig', [
            'formName' => 'Mise en ligne d\'image',
            'dataForm' => $imgForm->createView()
        ]);
    }

    // -----------------------------------------------------------------------------------------------------------

    #[Route('tag/create', name: 'tag_create')]
    public function createTags(Request $request, ManagerRegistry $doctrine): Response
    {
        // Cette route nous permet de créer jusqu'à cinq Tags via un formulaire spécialisé (donc non externalisé : il n'y a pas de dossier Form/TagType).

        // Pour dialoguer avec notre base de données, nous avons besoin de l'Entity Manager :
        $entityManager = $doctrine->getManager();

        // On récupère le Repository de Tag pour les tests de duplicata de nom de Tag :
        $tagRepository = $entityManager->getRepository(Tag::class);

        // Nous créons notre formulaire de création de Tags champ par champ :
        $tagsForm = $this->createFormBuilder()
            ->add('tag1', TextType::class, [
                'label' => 'Tag 1',
                'required' => false, // Ce champ n'est pas forcément rempli
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('tag2', TextType::class, [
                'label' => 'Tag 2',
                'required' => false, // Ce champ n'est pas forcément rempli
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('tag3', TextType::class, [
                'label' => 'Tag 3',
                'required' => false, // Ce champ n'est pas forcément rempli
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('tag4', TextType::class, [
                'label' => 'Tag 4',
                'required' => false, // Ce champ n'est pas forcément rempli
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('tag5', TextType::class, [
                'label' => 'Tag 5',
                'required' => false, // Ce champ n'est pas forcément rempli
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Valider',
                'attr' => [
                    'class' => 'w3-button w3-green',
                    'style' => 'margin-top:10px',
                ]
            ])
            ->getForm();

        // On applique l'objet Request sur notre formulaire :
        $tagsForm->handleRequest($request);
        // Si notre formulaire est rempli et valide, nous appliquons son contenu comme nous le désirons :
        if ($tagsForm->isSubmitted() && $tagsForm->isValid()) {
            // On récupère les valeurs de notre formulaire, grâce à la méthode getData() laquelle nous permet de récupérer lesdites valeurs sous forme de tableau associatif :
            $data = $tagsForm->getData();

            // dd($data); 
            // Affiche :
            //     array:5 [▼
            //     "tag1" => "bbbbbbb"
            //     "tag2" => null
            //     "tag3" => null
            //     "tag4" => null
            //     "tag5" => null
            //   ]

            // On vérifie que chaque champ est rempli :
            for ($i = 1; $i < 6; $i++) {
                if (!empty($data['tag' . $i])) { // tag1 tag2 tag3 etc
                    $persistBool = true; // Souhaitons-nous persister notre Tag ?

                    for ($j = 1; $j < $i; $j++) {
                        // Cette boucle vérifie les éléments inférieurs à $i dans le tableau, afin que nous ne persistions pas un Tag au nom indentique écrit dans un champ différent :
                        if ($data['tag' . $i] == $data['tag' . $j]) {
                            $persistBool = false;
                        }
                    }

                    if ($persistBool) { // Si nous souhaitons persister le Tag ...
                        // ...nous vérifions d'abord l'absence de duplicata dans notre base de données :
                        $tagName = $data['tag' . $i];
                        $duplicataTag = $tagRepository->findOneBy(['name' => $tagName]);
                        // Si $duplicataTag vaut null, le name de notre Tag est original, et nous pouvons donc le persister dans notre base de données :
                        if (!$duplicataTag) {
                            $tag = new Tag;
                            $tag->setName($data['tag' . $i]);
                            $entityManager->persist($tag);
                            $entityManager->flush();
                        }
                    }
                }
            }
            return $this->redirectToRoute('admin_backoffice');
        }

        // Si le formulaire n'est pas rempli, nous renvoyons l'utilisateur vers ce dernier :
        return $this->render('index/dataform.html.twig', [
            'formName' => 'Création de Tags',
            'dataForm' => $tagsForm->createView()
        ]);
    }

    // -----------------------------------------------------------------------------------------------------------

    #[Route('/tag/delete{tagId}', name: 'tag_delete')]
    public function deleteTag(ManagerRegistry $doctrine, int $tagId): Response
    {
        // Cette route permet la suppression d'un Tag dont l'ID est renseigné par notre paramètre de route.

        // On récupère l'Entity Manager et le Repository de Tag :
        $entityManager = $doctrine->getManager();
        $tagRepository = $entityManager->getRepository(Tag::class);

        // Nous recherchons le Tag dont l'ID nous a été fourni. S'il n'existe pas, nous retournons sur le Backoffice :
        $tag = $tagRepository->find($tagId);
        if (!$tag) {
            return $this->redirectToRoute('admin_backoffice');
        }

        // Si le produit existe, nous procédons à sa suppression, et nous retournons au backoffice :
        $entityManager->remove($tag);
        $entityManager->flush();
        return $this->redirectToRoute('admin_backoffice');
    }

    // -----------------------------------------------------------------------------------------------------------

    #[Route('order/validate/{orderId}', name: 'order_validate_admin')]
    public function validateOrderAdmin(ManagerRegistry $doctrine, int $orderId): Response
    {
        // Cette route récupère la commande en mode Panier et la place en mode Validée, à travers le changement de la valeur de l'attribut $status de la commande (Order) en question .

        // Afin de récupérer notre commande, nous avons besoin du ManagerRegistry pour l'Entity Manager, ainsi que du Repository de Order :
        $entityManager = $doctrine->getManager();
        $orderRepository = $entityManager->getRepository(Order::class);
        // Nous récupérons la commande en mode Panier. Si celle-ci n'existe pas, nous retournons à notre tableau de bord :
        $order = $orderRepository->findOneBy(['status' => 'panier', 'id' => $orderId], ['id' => 'DESC']);
        if (!$order) {
            return $this->redirectToRoute('order_dashboard_admin');
        }
        // On change le statut de notre commande en "Validée" avant de revenir à notre tableau de bord :
        $order->setStatus('validée');
        $entityManager->persist($order);
        $entityManager->flush();
        // Nous retournons au tableau de bord :
        return $this->redirectToRoute('order_dashboard_admin');
    }

    // -----------------------------------------------------------------------------------------------------------

    #[Route('/order/delete/{orderId}', name: 'order_delete_admin')]
    public function deleteOrderAdmin(ManagerRegistry $doctrine, int $orderId): Response
    {
        // Cette méthode supprime une commande (Order) ainsi que toutes les Reservations qui lui sont liées.

        // On récupère l'Entity Manager ainsi que le Repository de Order :
        $entityManager = $doctrine->getManager();
        $orderRepository = $entityManager->getRepository(Order::class);
        // On récupère la commande grâce à la méthode find() de Order, mais si cette entrée de table n'est pas trouvée, on revient au tableau de bord :
        $order = $orderRepository->find($orderId);
        if (!$order) {
            return $this->redirectToRoute('order_dashboard_admin');
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
        // On repart sur le tableau de bord :
        return $this->redirectToRoute('order_dashboard_admin');
    }

    // -----------------------------------------------------------------------------------------------------------

    #[Route('/reservation/delete/{reservationId}', name: 'reservation_delete_admin')]
    public function deleteReservationAdmin(ManagerRegistry $doctrine, int $reservationId): Response
    {
        // Cette méthode permet la suppression d'une Reservation (d'une commande en cours) dont nous avons renseigné l'Id via le paramètre de route.

        // On récupère l'Entity Manager ainsi que le Repository qui nous intéresse :
        $entityManager = $doctrine->getManager();
        $reservationRepository = $entityManager->getRepository(Reservation::class);
        // On récupère la Reservation dont l'ID nous est indiqué dans l'URL. Si cette Reservation n'existe pas, nous retournons au tableau de bord :
        $reservation = $reservationRepository->find($reservationId);
        if (!$reservation) {
            return $this->redirectToRoute('order_dashboard_admin');
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
        // On retourne vers notre tableau de bord :
        return $this->redirectToRoute('order_dashboard_admin');
    }

    // -----------------------------------------------------------------------------------------------------------
}
