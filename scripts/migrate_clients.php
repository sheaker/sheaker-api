<?php

$clientId = 1;

$dbh_sheaker = new PDO('mysql:host=localhost;dbname=client_gym4devs', 'root', '');
$dbh_forcegym = new PDO('mysql:host=localhost;dbname=forcegym', 'root', '');

$fgMembers = $dbh_forcegym->query('SELECT * FROM miembros', PDO::FETCH_ASSOC);
foreach($fgMembers as $fgMember) {
    // Build new user properly
    $newMember = [
        'custom_id'        => $fgMember['numSocio'],
        'first_name'       => $fgMember['nombre'],
        'last_name'        => $fgMember['apellidos'],
        'password'         => '',
        'phone'            => '52'.$fgMember['celular'],
        'mail'             => $fgMember['email'],
        'birthdate'        => $fgMember['fecha_nac'],
        'address_street_1' => $fgMember['direccion'],
        'address_street_2' => '', //$fgMember[''],
        'city'             => $fgMember['ciudad'],
        'zip'              => '', //$fgMember[''],
        'gender'           => ($fgMember['genero'] == 1) ? 1 : 0,
        'photo'            => "photos/{$clientId}/" . uniqid() . '.jpg',
        'sponsor_id'       => -1,
        'comment'          => '',//$fgMember['fecha_nac'],
        'failed_logins'    => 0,
        'last_seen'        => '0000-00-00 00:00:00',
        'last_ip'          => '0.0.0.0',
        'created_at'       => $fgMember['fecha_creacion']
    ];

    $stmt = $dbh_sheaker->prepare("
    INSERT INTO users
    (created_at, birthdate, gender, photo, last_ip, last_seen, zip, address_street_2, sponsor_id, mail, phone, comment, password, failed_logins, city, address_street_1, last_name, first_name, custom_id)
    VALUES
    (:created_at, :birthdate, :gender, :photo, :last_ip, :last_seen, :zip, :address_street_2, :sponsor_id, :mail, :phone, :comment, :password, :failed_logins, :city, :address_street_1, :last_name, :first_name, :custom_id)
    ");

    $stmt->bindValue(':created_at',       $newMember['created_at']);
    $stmt->bindValue(':birthdate',        $newMember['birthdate']);
    $stmt->bindValue(':gender',           $newMember['gender']);
    $stmt->bindValue(':photo',            $newMember['photo']);
    $stmt->bindValue(':last_ip',          $newMember['last_ip']);
    $stmt->bindValue(':last_seen',        $newMember['last_seen']);
    $stmt->bindValue(':zip',              $newMember['zip']);
    $stmt->bindValue(':address_street_2', $newMember['address_street_2']);
    $stmt->bindValue(':sponsor_id',       $newMember['sponsor_id']);
    $stmt->bindValue(':mail',             $newMember['mail']);
    $stmt->bindValue(':phone',            $newMember['phone']);
    $stmt->bindValue(':comment',          $newMember['comment']);
    $stmt->bindValue(':password',         $newMember['password']);
    $stmt->bindValue(':failed_logins',    $newMember['failed_logins']);
    $stmt->bindValue(':city',             $newMember['city']);
    $stmt->bindValue(':address_street_1', $newMember['address_street_1']);
    $stmt->bindValue(':last_name',        $newMember['last_name']);
    $stmt->bindValue(':first_name',       $newMember['first_name']);
    $stmt->bindValue(':custom_id',        $newMember['custom_id']);
    $stmt->execute();

    $newMember['id'] = $dbh_sheaker->lastInsertId();
    echo "User {$newMember['id']} created";

    $fgMemberships = $dbh_forcegym->prepare('SELECT * FROM membresias WHERE numSocio = :numSocio');
    $fgMemberships->execute(array(':numSocio' => $newMember['custom_id']));
    $membership = $fgMemberships->fetch();

    $stmt = $dbh_sheaker->prepare("
    INSERT INTO users_payments (user_id, days, start_date, end_date, comment, price, method, created_at)
    VALUES
    (:user_id, :days, :start_date, :end_date, :comment, :price, :method, :created_at)
    ");

    $datetimeStart = strtotime($membership['fecha_ini']);
    $datetimeEnd = strtotime($membership['fecha_fin']);

    $stmt->bindValue(':user_id',    $newMember['id']);
    $stmt->bindValue(':days',       ($datetimeEnd - $datetimeStart) / 86400);
    $stmt->bindValue(':start_date', $membership['fecha_ini']);
    $stmt->bindValue(':end_date',   $membership['fecha_fin']);
    $stmt->bindValue(':comment',    $membership['descripcion']);
    $stmt->bindValue(':price',      $membership['precio']);
    $stmt->bindValue(':method',     $membership['tipo_pago']);
    $stmt->bindValue(':created_at', $membership['fecha_reg']);
    $stmt->execute();
    echo ", his membership too";

    $originalPhoto = "photo/{$newMember['custom_id']}.jpg";
    $prefixNewPhoto = '../api/public/';

    if (!copy($originalPhoto, $prefixNewPhoto.$newMember['photo'])) {
        echo "ERROR: while copying photo\n";
    }

    echo ", and his photo.\n";
}
