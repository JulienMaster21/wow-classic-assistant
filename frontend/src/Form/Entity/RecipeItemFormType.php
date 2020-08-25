<?php

namespace App\Form\Entity;

use App\Entity\Profession;
use App\Entity\RecipeItem;
use App\Repository\ProfessionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class RecipeItemFormType
 * @package App\Form\Entity
 */
class RecipeItemFormType extends AbstractType {

    private ProfessionRepository $professionRepository;

    public function __construct(ProfessionRepository $professionRepository) {

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
            ->add('requiredSkillLevel', IntegerType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-thermometer-empty',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in the required skill level'
                ]
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
            'data_class' => RecipeItem::class,
            'action' => '',
            'method' => ''
        ]);

        $resolver->setAllowedTypes('action', 'string');
        $resolver->setAllowedTypes('method', 'string');
        $resolver->setAllowedValues('method', ['PUT', 'PATCH']);
    }
}
