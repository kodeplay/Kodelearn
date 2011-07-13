<?php defined('SYSPATH') or die('No direct script access.');

return array(
    'email' => array(
        'not_empty' => 'Please enter an email address',
        'email'     => 'Email not valid',
        'Model_User::email_unique'  => 'Email Already Exists',
    ),
    'email_parent' => array(
        'not_empty' => 'Please enter an email address',
        'email'     => 'Email not valid',
    ),
    'firstname' => array(
        'not_empty' => 'Please enter your firstname',
    ),
    'lastname' => array(
        'not_empty' => 'Please enter your lastname',
    ),    
    'password' => array(
        'not_empty' => 'Please enter your password.',
    ),
    'confirm_password' => array(
        'matches'  => 'Both passwords must match',
    ),
    'agree' => array(
        'not_empty' => 'You must agree to the privacy policy',
    ),
);