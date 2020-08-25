<?php

namespace App\Form\Entity;

use App\Entity\CraftableItem;
use App\Entity\Recipe;
use App\Repository\RecipeRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class CraftableItemFormType
 * @package App\Form\Entity
 */
class CraftableItemFormType extends AbstractType {

    private RecipeRepository $recipeRepository;

    public function __construct(RecipeRepository $recipeRepository) {

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
            ->add('itemLinkUrl', UrlType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-link',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in the url to the item'
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
            ->add('itemSlot', ChoiceType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-square',
                'attr' => [
                    'class' => 'form-control'
                ],
                'choices' => [
                    'Not equipable' => 'Not equipable',
                    'Head'          => 'Head',
                    'Neck'          => 'Neck',
                    'Shoulder'      => 'Shoulder',
                    'Chest'         => 'Chest',
                    'Shirt'         => 'Shirt',
                    'Back'          => 'Back',
                    'Wrist'         => 'Wrist',
                    'Hands'         => 'Hands',
                    'Waist'         => 'Waist',
                    'Legs'          => 'Legs',
                    'Feet'          => 'Feet',
                    'Trinket'       => 'Trinket',
                    'Two-Hand'      => 'Two-Hand',
                    'One-Hand'      => 'One-Hand',
                    'Main Hand'     => 'Main Hand',
                    'Shield'        => 'Shield',
                    'Ranged'        => 'Ranged',
                    'Projectile'    => 'Projectile',
                    'Bag'           => 'Bag'
                ],
                'placeholder' => 'Please choose an item slot'
            ])
            ->add('sellPrice', IntegerType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-coins',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in the sell price in coppers'
                ]
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
            'data_class' => CraftableItem::class,
            'action' => '',
            'method' => ''
        ]);

        $resolver->setAllowedTypes('action', 'string');
        $resolver->setAllowedTypes('method', 'string');
        $resolver->setAllowedValues('method', ['PUT', 'PATCH']);
    }
}
