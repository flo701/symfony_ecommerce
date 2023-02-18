<?php

namespace App\DataFixtures;

use App\Entity\Tag;
use App\Entity\Product;
use App\Entity\Category;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // A lancer avec php bin/console doctrine:fixtures:load

        // On commence par créer nos différentes Categories, lesquelles seront utilisées pour classifier nos différents Products :

        // Un lorem à utiliser :
        $lorem = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip
        ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum consectetur
        adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.";

        // La liste de nos différentes catégories sous la forme d'un tableau associatif, contenant une indication du type de catégorie sous la clef et l'objet Category en valeur. Etant donné que nous allons instancier nos Categories plus tard dans une boucle, la valeur actuelle de ces différentes clefs est null :
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
            $manager->persist($value); // Demande de persistance de notre nouvelle Category
        }

        // Nous allons préparer une collection de Tags :
        $tagNames = ["Bois", "Nouveau", "Promotion", "Mobilier", "Bon marché", "Occasion", "Design", "Pas cher", "Synthétique", "Au top"];
        $tags = []; // Tableau qui conservera les Tags
        foreach ($tagNames as $tagName) {
            // On crée une boucle foreach laquelle va parcourir le tableau de Tags, et utiliser chaque chaine de caractères pour initialiser et renseigner un nouveau Tag, enregistré dans le tableau $tags :
            $tag = new Tag;
            $tag->setName($tagName);
            array_push($tags, $tag);
            $manager->persist($tag);
        }

        // Boucle de création de Products :
        // On crée une liste de catégories potentielles :
        $categories = ['chaise', 'bureau', 'lit', 'canape', 'armoire', 'autre'];
        for ($i = 0; $i < 50; $i++) {
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
            $manager->persist($product);
        }
        $manager->flush();
    }
}
