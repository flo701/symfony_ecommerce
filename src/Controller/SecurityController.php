<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    #[Security('is_granted("ROLE_ADMIN")')]
    #[Route('admin/register', name: 'admin_register')]
    public function registerAdmin(Request $request, ManagerRegistry $doctrine, UserPasswordHasherInterface $passHasher): Response
    {
        // Cette route permet la création d'un compte utilisateur avec des privilèges Administrateur.

        // Pour enregistrer un compte utilisateur, nous avons besoin de l'Entity Manager :
        $entityManager = $doctrine->getManager();
        // Nous créons un formulaire interne pour l'inscription :
        $userForm = $this->createFormBuilder()
            ->add('username', TextType::class, [
                'label' => 'Nom de l\'utilisateur',
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'class' => 'w3-input w3-border w3-round w3-light-grey',
                    ]
                ],
                'second_options' => [
                    'label' => 'Confirmation du mot de passe',
                    'attr' => [
                        'class' => 'w3-input w3-border w3-round w3-light-grey',
                    ]
                ]
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Privilèges',
                'choices' => [
                    'Role: Client' => 'ROLE_CLIENT',
                    'Role: Admin' => 'ROLE_ADMIN',
                ],
                'expanded' => true,
                'multiple' => false,
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Inscription',
                'attr' => [
                    'class' => 'w3-button w3-black',
                    'style' => 'margin-top:10px',
                ]
            ])
            ->getForm();

        // On applique la requête Request sur notre formulaire :
        $userForm->handleRequest($request);
        // On se prépare le formulaire :
        if ($userForm->isSubmitted() && $userForm->isValid()) {
            // On récupère les informations de notre formulaire :
            $data = $userForm->getData();
            // Nous créons et renseignons notre Utilisateur :
            $user = new User;
            $user->setUsername($data['username']);
            $user->setRoles(['ROLE_USER', $data['roles']]);
            $user->setPassword($passHasher->hashPassword($user, $data['password']));
            // On persiste notre Entity :
            $entityManager->persist($user);
            $entityManager->flush();
            // Après le transfert de notre Entity User, on retourne sur l'index :
            return $this->redirectToRoute('app_index');
        }
        // Si notre formulaire n'est pas validé, nous le présentons à l'Utilisateur :
        return $this->render('index/dataform.html.twig', [
            'formName' => 'Inscription Utilisateur (\'ADMIN\')',
            'dataForm' => $userForm->createView(),
        ]);
    }

    // -------------------------------------------------------------------------------------------------

    #[Route('register', name: 'app_register')]
    public function registerUser(Request $request, ManagerRegistry $doctrine, UserPasswordHasherInterface $passHasher): Response
    {
        // Cette méthode permet la création d'un compte Client via un formulaire :

        // Pour enregistrer un compte utilisateur, nous avons besoin de l'Entity Manager :
        $entityManager = $doctrine->getManager();
        // Nous créons un formulaire interne pour l'inscription :
        $userForm = $this->createFormBuilder()
            ->add('username', TextType::class, [
                'label' => 'Nom de l\'utilisateur',
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ]
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'class' => 'w3-input w3-border w3-round w3-light-grey',
                    ]
                ],
                'second_options' => [
                    'label' => 'Confirmation du mot de passe',
                    'attr' => [
                        'class' => 'w3-input w3-border w3-round w3-light-grey',
                    ]
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Inscription',
                'attr' => [
                    'class' => 'w3-button w3-black',
                    'style' => 'margin-top:10px',
                ]
            ])
            ->getForm();

        // On applique la requête Request sur notre formulaire :
        $userForm->handleRequest($request);
        // On se prépare le formulaire :
        if ($userForm->isSubmitted() && $userForm->isValid()) {
            // On récupère les informations de notre formulaire :
            $data = $userForm->getData();
            // Nous créons et renseignons notre Utilisateur :
            $user = new User;
            $user->setUsername($data['username']);
            $user->setRoles(['ROLE_USER', 'ROLE_CLIENT']);
            $user->setPassword($passHasher->hashPassword($user, $data['password']));
            // On persiste notre Entity :
            $entityManager->persist($user);
            $entityManager->flush();
            // Après le transfert de notre Entity User, on retourne sur l'index :
            return $this->redirectToRoute('app_index');
        }
        // Si notre formulaire n'est pas validé, nous le présentons à l'Utilisateur :
        return $this->render('index/dataform.html.twig', [
            'formName' => 'Inscription Utilisateur',
            'dataForm' => $userForm->createView(),
        ]);
    }

    // -------------------------------------------------------------------------------------------------

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
