<?php

namespace App\Form\Entity;

use App\Entity\Reagent;
use App\Entity\Recipe;
use App\Entity\Source;
use App\Entity\Vendor;
use App\Repository\RecipeRepository;
use App\Repository\SourceRepository;
use App\Repository\VendorRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ReagentFormType
 * @package App\Form\Entity
 */
class ReagentFormType extends AbstractType {

    private SourceRepository $sourceRepository;
    private VendorRepository $vendorRepository;
    private RecipeRepository $recipeRepository;

    public function __construct(SourceRepository $sourceRepository,
                                VendorRepository $vendorRepository,
                                RecipeRepository $recipeRepository) {

        $this->sourceRepository = $sourceRepository;
        $this->vendorRepository = $vendorRepository;
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
            ->add('sources', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-times',
                'class' => Source::class,
                'choice_label' => 'name',
                'choices' => $this->sourceRepository->findBy([], ['name' => 'ASC']),
                'multiple' => true,
                'expanded' => true
            ])
            ->add('vendors', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-store',
                'class' => Vendor::class,
                'choice_label' => 'name',
                'choices' => $this->vendorRepository->findBy([], ['name' => 'ASC']),
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
            'data_class' => Reagent::class,
            'action' => '',
            'method' => ''
        ]);

        $resolver->setAllowedTypes('action', 'string');
        $resolver->setAllowedTypes('method', 'string');
        $resolver->setAllowedValues('method', ['PUT', 'PATCH']);
    }
}
