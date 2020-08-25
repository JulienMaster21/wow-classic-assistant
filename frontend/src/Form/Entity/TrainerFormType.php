<?php

namespace App\Form\Entity;

use App\Entity\Location;
use App\Entity\Profession;
use App\Entity\Recipe;
use App\Entity\Trainer;
use App\Repository\LocationRepository;
use App\Repository\ProfessionRepository;
use App\Repository\RecipeRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class TrainerFormType
 * @package App\Form\Entity
 */
class TrainerFormType extends AbstractType {

    private LocationRepository $locationRepository;
    private RecipeRepository $recipeRepository;
    private ProfessionRepository $professionRepository;

    public function __construct(LocationRepository $locationRepository,
                                RecipeRepository $recipeRepository,
                                ProfessionRepository $professionRepository) {

        $this->locationRepository = $locationRepository;
        $this->recipeRepository = $recipeRepository;
        $this->professionRepository = $professionRepository;
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
            ->add('trainerLinkUrl', UrlType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-link',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in the url to the trainer'
                ]
            ])
            ->add('reactionToAlliance', ChoiceType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-heart',
                'attr' => [
                    'class' => 'form-control'
                ],
                'choices' => [
                    'Friendly'  => 'Friendly',
                    'Hostile'   => 'Hostile'
                ],
                'placeholder' => 'Please choose a reaction'
            ])
            ->add('reactionToHorde', ChoiceType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-heart',
                'attr' => [
                    'class' => 'form-control'
                ],
                'choices' => [
                    'Friendly'  => 'Friendly',
                    'Hostile'   => 'Hostile'
                ],
                'placeholder' => 'Please choose a reaction'
            ])
            ->add('location', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-map-marker-alt',
                'class' => Location::class,
                'choice_label' => 'name',
                'choices' => $this->locationRepository->findBy([], ['name' => 'ASC']),
                'placeholder' => 'Please choose a location',
                'multiple' => false,
                'expanded' => false
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
            ->add('professions', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-tools',
                'class' => Profession::class,
                'choice_label' => 'name',
                'choices' => $this->professionRepository->findBy([], ['name' => 'ASC']),
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
            'data_class' => Trainer::class,
            'action' => '',
            'method' => ''
        ]);

        $resolver->setAllowedTypes('action', 'string');
        $resolver->setAllowedTypes('method', 'string');
        $resolver->setAllowedValues('method', ['PUT', 'PATCH']);
    }
}
