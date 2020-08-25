<?php

namespace App\Form\Entity;

use App\Entity\Character;
use App\Entity\CraftableItem;
use App\Entity\Profession;
use App\Entity\Reagent;
use App\Entity\Recipe;
use App\Entity\RecipeItem;
use App\Entity\Trainer;
use App\Repository\CharacterRepository;
use App\Repository\CraftableItemRepository;
use App\Repository\ProfessionRepository;
use App\Repository\ReagentRepository;
use App\Repository\RecipeItemRepository;
use App\Repository\TrainerRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class RecipeFormType
 * @package App\Form\Entity
 */
class RecipeFormType extends AbstractType {

    private RecipeItemRepository $recipeItemRepository;
    private CraftableItemRepository $craftableItemRepository;
    private ProfessionRepository $professionRepository;
    private ReagentRepository $reagentRepository;
    private TrainerRepository $trainerRepository;
    private CharacterRepository $characterRepository;

    public function __construct(RecipeItemRepository $recipeItemRepository,
                                CraftableItemRepository $craftableItemRepository,
                                ProfessionRepository $professionRepository,
                                ReagentRepository $reagentRepository,
                                TrainerRepository $trainerRepository,
                                CharacterRepository $characterRepository) {

        $this->recipeItemRepository = $recipeItemRepository;
        $this->craftableItemRepository = $craftableItemRepository;
        $this->professionRepository = $professionRepository;
        $this->reagentRepository = $reagentRepository;
        $this->trainerRepository = $trainerRepository;
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
            ->add('recipeLinkUrl', UrlType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-link',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in the url to the recipe'
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
            ->add('difficultyRequirement', IntegerType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-thermometer-empty',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in the difficulty requirement'
                ]
            ])
            ->add('difficultyCategory1', IntegerType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-thermometer-quarter',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in the difficulty category 1'
                ]
            ])
            ->add('difficultyCategory2', IntegerType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-thermometer-half',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in the difficulty category 2'
                ]
            ])
            ->add('difficultyCategory3', IntegerType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-thermometer-three-quarters',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in the difficulty category 3'
                ]
            ])
            ->add('difficultyCategory4', IntegerType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-thermometer-full',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in the difficulty category 4'
                ]
            ])
            ->add('minimumAmountCreated', IntegerType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-cube',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in the minimum amount created'
                ]
            ])
            ->add('maximumAmountCreated', IntegerType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-cubes',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in the maximum amount created'
                ]
            ])
            ->add('recipeItem', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-scroll',
                'class' => RecipeItem::class,
                'choice_label' => 'name',
                'choices' => $this->recipeItemRepository->findBy([], ['name' => 'ASC']),
                'placeholder' => 'Please choose a recipe item',
                'multiple' => false,
                'expanded' => false
            ])
            ->add('craftableItem', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-cube',
                'class' => CraftableItem::class,
                'choice_label' => 'name',
                'choices' => $this->craftableItemRepository->findBy([], ['name' => 'ASC']),
                'placeholder' => 'Please choose a craftable item',
                'multiple' => false,
                'expanded' => false
            ])
            ->add('profession', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-tools',
                'class' => Profession::class,
                'choice_label' => 'name',
                'choices' => $this->professionRepository->findBy([], ['name' => 'ASC']),
                'placeholder' => 'Please choose a profession',
                'multiple' => false,
                'expanded' => false
            ])
            ->add('reagents', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-vial',
                'class' => Reagent::class,
                'choice_label' => 'name',
                'choices' => $this->reagentRepository->findBy([], ['name' => 'ASC']),
                'multiple' => true,
                'expanded' => true
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
            ->add('trainingCost', IntegerType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-coins',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in the training cost'
                ]
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
            'data_class' => Recipe::class,
            'action' => '',
            'method' => ''
        ]);

        $resolver->setAllowedTypes('action', 'string');
        $resolver->setAllowedTypes('method', 'string');
        $resolver->setAllowedValues('method', ['PUT', 'PATCH']);
    }
}
