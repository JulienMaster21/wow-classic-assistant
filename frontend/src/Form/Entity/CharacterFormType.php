<?php

namespace App\Form\Entity;

use App\Entity\Character;
use App\Entity\Faction;
use App\Entity\PlayableClass;
use App\Entity\Profession;
use App\Entity\Recipe;
use App\Entity\User;
use App\Repository\FactionRepository;
use App\Repository\PlayableClassRepository;
use App\Repository\ProfessionRepository;
use App\Repository\RecipeRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class CharacterFormType
 * @package App\Form\Entity
 */
class CharacterFormType extends AbstractType {

    private UserRepository $userRepository;
    private FactionRepository $factionRepository;
    private PlayableClassRepository $playableClassRepository;
    private ProfessionRepository $professionRepository;
    private RecipeRepository $recipeRepository;

    public function __construct(UserRepository $userRepository,
                                FactionRepository $factionRepository,
                                PlayableClassRepository $playableClassRepository,
                                ProfessionRepository $professionRepository,
                                RecipeRepository $recipeRepository) {

        $this->userRepository = $userRepository;
        $this->factionRepository = $factionRepository;
        $this->playableClassRepository = $playableClassRepository;
        $this->professionRepository = $professionRepository;
        $this->recipeRepository = $recipeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
            ->setAction($options['action'])
            ->setMethod($options['method'])
            ->add('name', TextType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-signature',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in a name'
                ]
            ])
            ->add('user', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-user',
                'class' => User::class,
                'choice_label' => 'username',
                'choices' => $this->userRepository->findBy([], ['username' => 'ASC']),
                'placeholder' => 'Please choose a user',
                'multiple' => false,
                'expanded' => false
            ])
            ->add('faction', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-crown',
                'class' => Faction::class,
                'choice_label' => 'name',
                'choices' => $this->factionRepository->findBy([], ['name' => 'ASC']),
                'placeholder' => 'Please choose a faction',
                'multiple' => false,
                'expanded' => false
            ])
            ->add('playableClass', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-shield-alt',
                'class' => PlayableClass::class,
                'choice_label' => 'name',
                'choices' => $this->playableClassRepository->findBy([], ['name' => 'ASC']),
                'placeholder' => 'Please choose a class',
                'multiple' => false,
                'expanded' => false
            ])
            ->add('professions', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-tools',
                'class' => Profession::class,
                'choice_label' => 'name',
                'choices' => $this->professionRepository->findBy([], ['name' => 'ASC']),
                'multiple' => true,
                'expanded' => true
            ])
            ->add('recipes', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-scroll',
                'class' => Recipe::class,
                'choice_label' => 'name',
                'choices' => $this->recipeRepository->findBy([], ['profession' => 'ASC', 'name' => 'ASC']),
                'group_by' => function($choice) {
                    return $choice->getProfession()->getName();
                },
                'multiple' => true,
                'expanded' => true
            ])
            ->add('Submit', SubmitType::class, [
                'row_attr' => ['class' => 'd-flex justify-content-center'],
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) {

        $resolver->setDefaults([
            'data_class' => Character::class,
            'action' => '',
            'method' => ''
        ]);

        $resolver->setAllowedTypes('action', 'string');
        $resolver->setAllowedTypes('method', 'string');
        $resolver->setAllowedValues('method', ['PUT', 'PATCH']);
    }
}
