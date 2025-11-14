<?php
$d = ['localhost','jokenpo','root','','utf8'];

try{
    $pdo = new PDO("mysql:host={$d[0]};dbname={$d[1]};charset={$d[4]}", $d[2], $d[3]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //echo "Conectado com sucesso!";
}catch (PDOException $e) {
    echo "Conexão falhou: " . $e->getMessage();
}
?>