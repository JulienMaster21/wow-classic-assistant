<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class LoginFormType
 * @package App\Form
 */
class LoginFormType extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
            ->add('username', TextType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-user',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in your username'
                ]
            ])
            ->add('password', PasswordType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-lock',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in your password'
                ]
            ])
            ->add('login', SubmitType::class, [
                'row_attr' => ['class' => 'd-flex justify-content-center'],
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }
}
