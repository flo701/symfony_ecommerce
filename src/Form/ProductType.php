<?php

namespace App\Form;

use App\Entity\Tag;
use App\Entity\Product;
use App\Entity\Category;
use App\Entity\ProductImg;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du Produit',
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('category', EntityType::class, [
                'label' => 'Categorie',
                'class' => Category::class, // Classe Entity utilisée pour notre champ 
                'choice_label' => 'name', // Attribut utilisé pour représenter l'Entity
                'expanded' => false, // Affichage d'un menu déroulant (avec true, les options auraient été les unes à côté des autres)
                'multiple' => false, // On ne peut PAS choisir plusieurs Categories
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du Produit',
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock disponible',
                'attr' => [
                    'min' => 0, // Valeur minimale du champ
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix du Produit',
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('tags', EntityType::class, [
                'label' => 'Tags',
                'class' => Tag::class, // Classe Entity utilisée pour notre champ 
                'choice_label' => 'name', // Attribut utilisé pour représenter l'Entity
                'expanded' => true, // Les options sont les unes à côté des autres
                'multiple' => true, // On peut choisir plusieurs Tags 
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('productImg', EntityType::class, [
                'label' => 'Image liée',
                'class' => ProductImg::class, // Classe Entity utilisée pour notre champ 
                'choice_label' => 'name', // Attribut utilisé pour représenter l'Entity
                'required' => false,
                'expanded' => false, // Affichage d'un menu déroulant 
                'multiple' => false, // On ne peut PAS choisir plusieurs Images
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
