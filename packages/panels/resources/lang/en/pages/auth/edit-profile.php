<?php

return [

    'label' => 'Profile',

    'form' => [

        'email' => [
            'label' => 'Email address',
        ],

        'name' => [
            'label' => 'Name',
        ],

        'password' => [
            'label' => 'New password',
            'validation_attribute' => 'password',
        ],

        'password_confirmation' => [
            'label' => 'Confirm new password',
            'validation_attribute' => 'password confirmation',
        ],

        'current_password' => [
            'label' => 'Current password',
            'below_content' => 'For security, please confirm your password to continue.',
            'validation_attribute' => 'current password',
        ],

        'actions' => [

            'save' => [
                'label' => 'Save changes',
            ],

        ],

    ],

    'notifications' => [

        'saved' => [
            'title' => 'Saved',
        ],

    ],

    'actions' => [

        'cancel' => [
            'label' => 'Cancel',
        ],

    ],

];
