<?php

namespace App\Form\Entity;

use App\Entity\Character;
use App\Entity\Profession;
use App\Entity\Recipe;
use App\Entity\RecipeItem;
use App\Entity\Trainer;
use App\Repository\CharacterRepository;
use App\Repository\RecipeItemRepository;
use App\Repository\RecipeRepository;
use App\Repository\TrainerRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ProfessionFormType
 * @package App\Form\Entity
 */
class ProfessionFormType extends AbstractType {

    private TrainerRepository $trainerRepository;
    private RecipeRepository $recipeRepository;
    private RecipeItemRepository $recipeItemRepository;
    private CharacterRepository $characterRepository;

    public function __construct(TrainerRepository $trainerRepository,
                                RecipeRepository $recipeRepository,
                                RecipeItemRepository $recipeItemRepository,
                                CharacterRepository $characterRepository) {

        $this->trainerRepository = $trainerRepository;
        $this->recipeRepository = $recipeRepository;
        $this->recipeItemRepository = $recipeItemRepository;
        $this->characterRepository = $characterRepository;
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
            ->add('professionLinkUrl', UrlType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-link',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in the url to the profession'
                ]
            ])
            ->add('iconLinkUrl', UrlType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-link',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in the url to the icon'
                ]
            ])
            ->add('isMainProfession', ChoiceType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-briefcase',
                'attr' => [
                    'class' => 'form-control',
                ],
                'choices' => [
                    'Yes'   => true,
                    'No'    => false
                ],
                'placeholder' => 'Please choose if the profession is a main profession',
                'help' =>   'A main profession is a profession that takes up one of the 2 available slots for a character.'
            ])
            ->add('trainers', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-chalkboard-teacher',
                'class' => Trainer::class,
                'choice_label' => 'name',
                'choices' => $this->trainerRepository->findBy([], ['name' => 'ASC']),
                'multiple' => true,
                'expanded' => true
            ])
            ->add('recipes', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-scroll',
                'class' => Recipe::class,
                'choice_label' => 'name',
                'choices' => $this->recipeRepository->findBy([], ['name' => 'ASC']),
                'multiple' => true,
                'expanded' => true
            ])
            ->add('recipeItems', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-scroll',
                'class' => RecipeItem::class,
                'choice_label' => 'name',
                'choices' => $this->recipeItemRepository->findBy([], ['name' => 'ASC']),
                'multiple' => true,
                'expanded' => true
            ])
            ->add('characters', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-users',
                'class' => Character::class,
                'choice_label' => 'name',
                'choices' => $this->characterRepository->findBy([], ['name' => 'ASC']),
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
            'data_class' => Profession::class,
            'action' => '',
            'method' => ''
        ]);

        $resolver->setAllowedTypes('action', 'string');
        $resolver->setAllowedTypes('method', 'string');
        $resolver->setAllowedValues('method', ['PUT', 'PATCH']);
    }
}
