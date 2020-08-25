<?php

namespace App\Form\Entity;

use App\Entity\Location;
use App\Entity\Reagent;
use App\Entity\Vendor;
use App\Repository\LocationRepository;
use App\Repository\ReagentRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class VendorFormType
 * @package App\Form\Entity
 */
class VendorFormType extends AbstractType {

    private LocationRepository $locationRepository;
    private ReagentRepository $reagentRepository;

    public function __construct(LocationRepository $locationRepository,
                                ReagentRepository $reagentRepository) {

        $this->locationRepository = $locationRepository;
        $this->reagentRepository = $reagentRepository;
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
            ->add('vendorLinkUrl', UrlType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-link',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in the url to the vendor'
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
            ->add('locations', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-map-marker-alt',
                'class' => Location::class,
                'choice_label' => 'name',
                'choices' => $this->locationRepository->findBy([], ['name' => 'ASC']),
                'multiple' => true,
                'expanded' => true
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
            'data_class' => Vendor::class,
            'action' => '',
            'method' => ''
        ]);

        $resolver->setAllowedTypes('action', 'string');
        $resolver->setAllowedTypes('method', 'string');
        $resolver->setAllowedValues('method', ['PUT', 'PATCH']);
    }
}
