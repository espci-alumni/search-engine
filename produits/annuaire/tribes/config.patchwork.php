<?php

#patchwork ../interface

$CONFIG += array(
//    'annuaire_manager.dsn' => array(...),
    'tribes.emailDomain' => '',
    'tribes.baseUrl' => '/',
    'tribes.diplome' => '',
    'nomSql' => 'CONCAT_WS(" ",
        IF(prenom_usuel!="", prenom_usuel, prenom_civil),
        IF( nom_usuel!="", nom_usuel, nom_civil),
        IF(nom_etudiant!="" AND nom_etudiant!=IF(nom_usuel!="",nom_usuel,nom_civil), CONCAT("(",nom_etudiant,")"), NULL)
    )',
    'promoSql' => '',
    'emailSql' => !empty($CONFIG['tribes.emailDomain']) ? "IF(login,CONCAT(login,\"{$CONFIG['tribes.emailDomain']}\"),'')" : '',
);
