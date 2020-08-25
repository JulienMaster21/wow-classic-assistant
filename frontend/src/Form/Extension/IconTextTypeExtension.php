<?php

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IconTextTypeExtension extends AbstractTypeExtension {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->setAttribute('icon', $options['icon']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options) {

        $view->vars['icon'] = $options['icon'];
    }

    public function configureOptions(OptionsResolver $resolver){

        $resolver->setDefaults(['icon' => null]);
        $resolver->setDefined(['icon']);
    }

    public static function getExtendedTypes(): iterable {

        return [TextType::class, IntegerType::class];
    }
}