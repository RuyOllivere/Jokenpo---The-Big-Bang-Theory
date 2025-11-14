<?php
session_start();

spl_autoload_register(function ($c) {
    require_once "$c.class.php";
});

$player = new Player();
$jkp = new Jokenpo();

//Fazer login
if(isset($_POST['user'])){
	$u = $_POST['user'];
	$s = $_POST['pass'];
	$player->fazerLogin($u,$s);
}

//Fazer logout
if(isset($_GET['logout'])){
	$player->fazerLogout();
}

//Entrar na sala
if(isset($_GET['sala'])){
	$jkp->entrarSala($_GET['sala']);
}

//-> Verifica convite
if(isset($_GET['convite'])){
	$jkp->entrarSala($_GET['convite']);
}

//-> Sai da sala
if(isset($_GET['sairsala'])){
    $jkp->SairSala($_GET['sair']);
}

//-> Efetua jogada
if(isset($_GET['jogada'])){
	if($_GET['jogada'] != ""){
		$jkp->fazerJogada($_GET['jogada']);
	}
}

//-> Nova Rodada
if(isset($_GET['novarodada'])){
	$jkp->novaRodada($_GET['novarodada']);
}

$jkp->iniciarJogo();
?>