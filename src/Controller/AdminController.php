<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_backoffice')]
    public function adminBackoffice(ManagerRegistry $doctrine): Response
    {
        // Cette page affiche la liste des Products et des Tags avec la possibilité de les créer, de les modifier, et de les supprimer, totalisant ainsi les quatre fonctions du CRUD.

        // On récupère l'Entity Manager, et les Repositories de Product et Tag :
        $entityManager = $doctrine->getManager();
        $productRepository = $entityManager->getRepository(Product::class);
        $tagRepository = $entityManager->getRepository(Tag::class);

        // On récupère la liste de nos Products et Tags (deux façons différentes possibles) :
        $products = array_reverse($productRepository->findAll());
        $tags = $tagRepository->findBy([], ['id' => 'DESC']);

        // On transmet nos Products et Tags à notre backoffice :
        return $this->render('admin/admin_backoffice.html.twig', ['products' => $products, 'tags' => $tags,]);
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
}
