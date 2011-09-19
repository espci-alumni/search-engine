<?php

#patchwork ../interface

$CONFIG += array(
    'annuaire_manager.DSN' => '',
    'tribes.emailDomain' => '',
    'tribes.baseUrl' => '/',
    'tribes.diplome' => '',
    'promoSql' => '',
    'email' => "IF(login,CONCAT(login,\"{$CONFIG['tribes.emailDomain']}\"),'')",
);
