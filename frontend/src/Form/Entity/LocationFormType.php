<?php

namespace App\Form\Entity;

use App\Entity\Location;
use App\Entity\Trainer;
use App\Entity\Vendor;
use App\Repository\TrainerRepository;
use App\Repository\VendorRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class LocationFormType
 * @package App\Form\Entity
 */
class LocationFormType extends AbstractType {

    private TrainerRepository $trainerRepository;
    private VendorRepository $vendorRepository;

    public function __construct(TrainerRepository $trainerRepository,
                                VendorRepository $vendorRepository) {
        $this->trainerRepository = $trainerRepository;
        $this->vendorRepository = $vendorRepository;
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
            ->add('locationLinkUrl', UrlType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-link',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in the url to the location'
                ]
            ])
            ->add('factionStatus', ChoiceType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-crown',
                'attr' => [
                    'class' => 'form-control'
                ],
                'choices' => [
                    'Alliance'  => 'Alliance',
                    'Horde'     => 'Horde',
                    'Contested' => 'Contested',
                    'PvP'       => 'PvP'
                ],
                'placeholder' => 'Please choose a faction status'
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
            ->add('vendors', EntityType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-store',
                'attr' => [
                    'class' => 'd-flex flex-wrap align-items-baseline align-content-between'
                ],
                'class' => Vendor::class,
                'choice_label' => 'name',
                'choices' => $this->vendorRepository->findBy([], ['name' => 'ASC']),
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
            'data_class' => Location::class,
            'action' => '',
            'method' => ''
        ]);

        $resolver->setAllowedTypes('action', 'string');
        $resolver->setAllowedTypes('method', 'string');
        $resolver->setAllowedValues('method', ['PUT', 'PATCH']);
    }
}
