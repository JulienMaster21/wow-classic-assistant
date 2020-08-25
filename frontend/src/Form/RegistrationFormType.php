<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class RegistrationFormType
 * @package App\Form
 */
class RegistrationFormType extends AbstractType {

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
            ->add('email', EmailType::class, [
                'row_attr' => ['class' => 'form-group'],
                'icon' => 'fa-envelope',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please fill in your email'
                ]
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Password',
                    'row_attr' => ['class' => 'form-group'],
                    'icon' => 'fa-lock',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Please fill in your password'
                    ]
                ],
                'second_options' => [
                    'label' => 'Confirm Password',
                    'row_attr' => ['class' => 'form-group'],
                    'icon' => 'fa-lock',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Please confirm in your password'
                    ]
                ]
            ])
            ->add('register', SubmitType::class, [
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
