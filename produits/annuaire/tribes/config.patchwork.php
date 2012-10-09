<?php

#patchwork ../interface

$CONFIG += array(
//    'annuaire_manager.dsn' => array(...),
    'tribes.emailDomain' => '',
    'tribes.baseUrl' => '/',
    'tribes.diplome' => '',
    'promoSql' => '',
    'emailSql' => !empty($CONFIG['tribes.emailDomain']) ? "IF(login,CONCAT(login,\"{$CONFIG['tribes.emailDomain']}\"),'')" : '',
);
